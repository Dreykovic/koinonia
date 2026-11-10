<?php

namespace App\Http\Controllers;

use App\Models\MailRecipient;
use App\Traits\LogsErrors;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class MailRecipientController extends Controller
{
    use LogsErrors;

    public function index()
    {
        $recipients = MailRecipient::orderBy('email')->get()->groupBy('type');

        return view('mail-recipients.index', [
            'recipients' => $recipients,
            'types' => MailRecipient::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(MailRecipient::TYPES))],
            'email' => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('mail_recipients')->where('type', $request->input('type')),
            ],
            'name' => ['nullable', 'string', 'max:255'],
        ], [
            'email.unique' => 'Cette adresse est déjà configurée pour ce type de mail.',
        ]);

        try {
            $recipient = MailRecipient::create($validated);
        } catch (Throwable $e) {
            $this->logHandledError('mail_recipient.store.failed', $e, ['type' => $validated['type']]);

            return back()->withInput()->with('error', 'Le destinataire n\'a pas pu être ajouté. Réessayez.');
        }

        $this->logInfo('mail_recipient.created', $this->context($recipient), 'audit');

        return back()->with('success', 'Destinataire ajouté avec succès.');
    }

    public function update(Request $request, MailRecipient $mailRecipient)
    {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        try {
            $mailRecipient->update($validated);
        } catch (Throwable $e) {
            $this->logHandledError('mail_recipient.update.failed', $e, $this->context($mailRecipient));

            return back()->with('error', 'Le destinataire n\'a pas pu être mis à jour. Réessayez.');
        }

        $this->logInfo('mail_recipient.updated', $this->context($mailRecipient), 'audit');

        return back()->with('success', 'Destinataire mis à jour avec succès.');
    }

    public function destroy(MailRecipient $mailRecipient)
    {
        $context = $this->context($mailRecipient);

        try {
            $mailRecipient->delete();
        } catch (Throwable $e) {
            $this->logHandledError('mail_recipient.delete.failed', $e, $context);

            return back()->with('error', 'Le destinataire n\'a pas pu être supprimé. Réessayez.');
        }

        $this->logInfo('mail_recipient.deleted', $context, 'audit');

        return back()->with('success', 'Destinataire supprimé avec succès.');
    }

    private function context(MailRecipient $recipient): array
    {
        return [
            'mail_recipient_id' => $recipient->id,
            'type' => $recipient->type,
            'active' => $recipient->active,
        ];
    }
}
