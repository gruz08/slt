<?php
/**
 * validation.php
 * Wspólne funkcje sanityzacji i walidacji danych wejściowych dla API.
 * Dołączać po config.php (nie wymaga auth.php).
 */

declare(strict_types=1);

/**
 * Przycina, usuwa znaczniki HTML i ogranicza długość tekstu.
 * Nie robimy tu escapowania pod HTML — to zadanie warstwy, która dane
 * WYŚWIETLA (frontend), zgodnie z zasadą "escapuj przy wypisywaniu,
 * nie przy zapisie". Tutaj chronimy tylko przed zapisaniem znaczników,
 * które mogłyby coś nadpisać/wstrzyknąć, gdyby dane trafiły np. do e-maila.
 */
function sanitize_text(string $value, int $maxLength = 255): string
{
    $value = trim(strip_tags($value));
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? $value;
    if (function_exists('mb_substr')) {
        $value = mb_substr($value, 0, $maxLength);
    } else {
        $value = substr($value, 0, $maxLength);
    }
    return $value;
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 254;
}

function is_valid_date(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

function is_valid_time(string $time): bool
{
    return (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time);
}

/**
 * Wyprowadza klucz miesiąca (używany przez filtr kalendarza: marzec,
 * kwiecien, maj, ...) BEZPOŚREDNIO z daty meczu, zamiast ufać wartości
 * przysłanej z formularza — dzięki temu data i miesiąc nigdy się nie
 * rozjadą, niezależnie od tego, co wyśle klient.
 */
function month_key_from_date(string $date): string
{
    $months = [
        1 => 'styczen', 2 => 'luty', 3 => 'marzec', 4 => 'kwiecien',
        5 => 'maj', 6 => 'czerwiec', 7 => 'lipiec', 8 => 'sierpien',
        9 => 'wrzesien', 10 => 'pazdziernik', 11 => 'listopad', 12 => 'grudzien',
    ];
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if ($parsed === false) {
        return '';
    }
    return $months[(int)$parsed->format('n')] ?? '';
}

/**
 * Waliduje wartość względem listy dozwolonych opcji (enum).
 */
function is_one_of(string $value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}
