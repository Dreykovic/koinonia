<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleSetting;
use App\Traits\LogsErrors;
use Illuminate\Http\Request;
use Throwable;

class VehicleSettingController extends Controller
{
    use LogsErrors;

    /**
     * Show the settings edit form.
     */
    public function edit()
    {
        $settings = VehicleSetting::getSettings();

        return view('admin.vehicle-settings.edit', compact('settings'));
    }

    /**
     * Update the settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'opening_hour' => ['required', 'date_format:H:i'],
            'closing_hour' => ['required', 'date_format:H:i', 'after:opening_hour'],
            'max_future_days' => ['required', 'integer', 'min:1', 'max:365'],
        ], [
            'opening_hour.required' => 'L\'heure d\'ouverture est obligatoire.',
            'opening_hour.date_format' => 'L\'heure d\'ouverture doit etre au format HH:MM.',
            'closing_hour.required' => 'L\'heure de fermeture est obligatoire.',
            'closing_hour.date_format' => 'L\'heure de fermeture doit etre au format HH:MM.',
            'closing_hour.after' => 'L\'heure de fermeture doit etre apres l\'heure d\'ouverture.',
            'max_future_days.required' => 'Le nombre de jours maximum est obligatoire.',
            'max_future_days.integer' => 'Le nombre de jours doit etre un nombre entier.',
            'max_future_days.min' => 'Le nombre de jours doit etre au moins 1.',
            'max_future_days.max' => 'Le nombre de jours ne peut pas depasser 365.',
        ]);

        $new = [
            'opening_hour' => $validated['opening_hour'].':00',
            'closing_hour' => $validated['closing_hour'].':00',
            'max_future_days' => (int) $validated['max_future_days'],
        ];

        try {
            // Lecture hors cache : l'instance en cache peut etre perimee.
            $settings = VehicleSetting::first() ?? VehicleSetting::getSettings();
            $old = $settings->only(array_keys($new));

            $settings->update($new);
        } catch (Throwable $e) {
            $this->logError('vehicle_settings.update.failed', $e, ['new' => $new]);

            return back()->withInput()
                ->with('error', 'Les parametres de reservation n\'ont pas pu etre enregistres. Veuillez reessayer.');
        } finally {
            // Toujours vider le cache : en cas d'echec, on evite de servir une valeur incoherente.
            $this->clearSettingsCache();
        }

        // Regles de reservation pour tous les membres : pas d'ActivityLog, trace dans le canal audit.
        $this->logInfo('vehicle_settings.updated', ['old' => $old, 'new' => $new], 'audit');

        return redirect()->route('admin.vehicle-settings.edit')
            ->with('success', 'Parametres de reservation mis a jour avec succes.');
    }

    /**
     * API endpoint to get settings for JavaScript.
     */
    public function getSettings()
    {
        $settings = VehicleSetting::getSettings();

        return response()->json([
            'opening_hour' => $settings->opening_hour_formatted,
            'closing_hour' => $settings->closing_hour_formatted,
            'opening_hour_int' => $settings->opening_hour_int,
            'closing_hour_int' => $settings->closing_hour_int,
            'max_future_days' => $settings->max_future_days,
            'max_reservation_date' => $settings->max_reservation_date,
        ]);
    }

    /**
     * Vide le cache des parametres sans jamais faire echouer la requete :
     * un cache non vide expire de lui-meme (CACHE_TTL).
     */
    private function clearSettingsCache(): void
    {
        try {
            VehicleSetting::clearCache();
        } catch (Throwable $e) {
            $this->logWarning('vehicle_settings.cache_clear.failed', [
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'ttl_seconds' => VehicleSetting::CACHE_TTL,
            ]);
        }
    }
}
