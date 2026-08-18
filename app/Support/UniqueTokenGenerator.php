<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;


class UniqueTokenGenerator
{
   
    public static function make(callable $candidate, callable $exists, int $maxAttempts = 20): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $value = $candidate();

            if (! $exists($value)) {
                return $value;
            }
        }

        throw new RuntimeException(
            "Gagal menghasilkan nilai unik setelah {$maxAttempts} kali percobaan."
        );
    }
}