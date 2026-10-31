<?php

namespace App\Services;

use App\Models\BulletinEdition;
use App\Models\User;
use App\Traits\LogsErrors;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class BirthdayService
{
    use LogsErrors;

    /**
     * Anniversaires de la semaine du bulletin (ou de la semaine courante).
     *
     * Ne lève jamais d'exception : en cas d'échec, l'erreur est journalisée
     * et une collection vide est renvoyée pour ne pas casser la page appelante.
     */
    public function getWeekBirthdays(?BulletinEdition $bulletin): Collection
    {
        try {
            return $this->loadWeekBirthdays($bulletin);
        } catch (Throwable $e) {
            $this->logError('birthday.week.failed', $e, ['bulletin_id' => $bulletin?->id]);

            return collect();
        }
    }

    private function loadWeekBirthdays(?BulletinEdition $bulletin): Collection
    {
        // Un bulletin sans dates retombe sur la semaine courante.
        if (! $bulletin || ! $bulletin->week_start || ! $bulletin->week_end) {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();
        } else {
            $weekStart = $bulletin->week_start;
            $weekEnd = $bulletin->week_end;
        }

        $dates = collect();
        $current = $weekStart->copy();
        while ($current->lte($weekEnd)) {
            $dates->push($current->copy());
            $current->addDay();
        }

        $monthDayPairs = $dates->map(fn ($d) => [$d->month, $d->day]);

        return User::active()
            ->whereNotNull('birthday')
            ->where(function ($query) use ($monthDayPairs) {
                foreach ($monthDayPairs as [$month, $day]) {
                    $query->orWhere(function ($q) use ($month, $day) {
                        $q->whereMonth('birthday', $month)->whereDay('birthday', $day);
                    });
                }
            })
            ->get()
            ->map(function ($user) use ($weekStart) {
                $birthday = Carbon::parse($user->birthday);
                $birthdayThisYear = $birthday->copy()->year($weekStart->year);
                $user->birthday_day = $birthdayThisYear->locale('fr')->isoFormat('dddd D');

                return $user;
            })
            ->sortBy(fn ($user) => Carbon::parse($user->birthday)->format('m-d'));
    }
}
