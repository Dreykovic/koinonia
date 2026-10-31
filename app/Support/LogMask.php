<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/**
 * Masquage des données personnelles avant écriture dans les logs.
 */
class LogMask
{
    /**
     * Masque la partie locale d'une adresse : "jean.dupont@example.com" => "j***@example.com".
     * Une valeur sans "@", ou dont le domaine n'est pas un nom d'hôte plausible
     * (caractères de contrôle, retours à la ligne : injection de lignes de log), est
     * entièrement masquée ; null reste null.
     */
    public static function email(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }

        $email = (string) preg_replace('/\p{C}/u', '', mb_scrub($email, 'UTF-8'));
        $at = strrpos($email, '@');

        if ($at === false || $at === 0) {
            return '***';
        }

        $domain = substr($email, $at + 1);

        if (! preg_match('/^[A-Za-z0-9.-]{1,253}$/', $domain)) {
            return '***';
        }

        return mb_substr($email, 0, 1).'***@'.$domain;
    }

    /**
     * Retire d'un texte libre (message d'erreur SMTP, d'exception, URL, stack trace) :
     * tokens de réinitialisation, paramètres sensibles, hash bcrypt et adresses e-mail.
     */
    public static function text(string $value): string
    {
        $patterns = [
            // Lien de réinitialisation de mot de passe : le token est dans le chemin.
            '#(reset-password/)[^/\s?\#:"\'<>]+#i' => '$1[redacted]',
            // Paramètres de requête sensibles (token, signature, password…).
            '/([?&][^=&\s#]*(?:token|signature|password|secret|key|code|email)[^=&\s#]*=)[^&\s#"\'<>]*/i' => '$1[redacted]',
            // Hash de mot de passe (bcrypt / argon).
            '/\$2[abxy]?\$\d{2}\$[.\/A-Za-z0-9]{53}/' => '[hash]',
            '/\$argon2[a-z]*\$[^\s,\'")]+/' => '[hash]',
            // Adresses e-mail : "jean@example.com" => "j***@example.com".
            '/\b([A-Za-z0-9])[A-Za-z0-9._%+-]*@([A-Za-z0-9.-]+\.[A-Za-z]{2,})\b/' => '$1***@$2',
        ];

        return (string) preg_replace(array_keys($patterns), array_values($patterns), $value);
    }

    /**
     * Message d'exception journalisable. Pour une QueryException, le message de Laravel
     * contient le SQL avec ses valeurs liées (e-mails, téléphones, hash de mot de passe) :
     * on garde le message du driver, le SQL avec ses "?" et le code SQLSTATE.
     */
    public static function exceptionMessage(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            $driver = $e->getPrevious()?->getMessage() ?: 'query failed';

            return self::text($driver).' (SQL: '.$e->getSql().')';
        }

        return self::text($e->getMessage());
    }

    /**
     * Contexte d'erreur standard (classe, message masqué, fichier:ligne, exception pour la trace).
     * Une QueryException est remplacée par une copie sans valeurs liées (même trace).
     *
     * @return array<string, mixed>
     */
    public static function exceptionContext(Throwable $e): array
    {
        $context = [
            'exception_class' => get_class($e),
            'error' => self::exceptionMessage($e),
            'location' => $e->getFile().':'.$e->getLine(),
        ];

        if ($e instanceof QueryException) {
            $context['sql'] = $e->getSql();
            $context['sql_state'] = $e->errorInfo[0] ?? $e->getCode();
        }

        return $context + ['exception' => self::loggableException($e)];
    }

    /**
     * L'exception elle-même si son message est sûr, sinon une copie (RuntimeException)
     * au message masqué qui conserve fichier, ligne et stack trace d'origine.
     */
    public static function loggableException(Throwable $e): Throwable
    {
        if (! $e instanceof QueryException) {
            return $e;
        }

        $copy = new RuntimeException('['.get_class($e).'] '.self::exceptionMessage($e), (int) $e->getCode());

        foreach (['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()] as $property => $value) {
            (new ReflectionProperty(\Exception::class, $property))->setValue($copy, $value);
        }

        return $copy;
    }
}
