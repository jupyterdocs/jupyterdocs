<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Limits free text by its number of words rather than characters, which is
 * how people actually think about "how much can I write here". Words are
 * runs of non-whitespace, matching the live counter on the upload form.
 */
class WordCount implements ValidationRule
{
    public function __construct(private int $min, private int $max)
    {
    }

    public static function count(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $words = self::count((string) $value);

        if ($words < $this->min) {
            $fail("The :attribute needs to be at least {$this->min} words.");
        } elseif ($words > $this->max) {
            $fail("The :attribute can be at most {$this->max} words (it has {$words}).");
        }
    }
}
