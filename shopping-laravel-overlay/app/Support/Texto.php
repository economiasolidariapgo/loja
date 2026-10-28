<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Pequenos helpers de texto (equivalentes a slugify/clean_phone/safe_link do app.py). */
final class Texto
{
    public static function slugify(string $t): string
    {
        $t = strtolower(Str::ascii($t));
        $t = trim((string) preg_replace('/[^a-z0-9]+/', '-', $t), '-');

        return $t !== '' ? $t : 'loja';
    }

    /** Devolve só os dígitos (10 ou 11, sem o 55) ou null se inválido. */
    public static function cleanPhone(?string $v): ?string
    {
        $d = (string) preg_replace('/\D/', '', (string) $v);
        if (str_starts_with($d, '55') && in_array(strlen($d), [12, 13], true)) {
            $d = substr($d, 2);
        }

        return in_array(strlen($d), [10, 11], true) ? $d : null;
    }

    /** Link de banner: só http(s):// ou caminho interno ("/algo", nunca "//host"). */
    public static function safeLink(string $v): bool
    {
        return (bool) preg_match('~^(?:https?://|/(?!/))[^\s<>"\']{0,290}\z~', $v);
    }

    /** trim + limite de caracteres (o `.strip()[:n]` do Python). */
    public static function cut(?string $v, int $max): string
    {
        return mb_substr(trim((string) $v), 0, $max);
    }
}
