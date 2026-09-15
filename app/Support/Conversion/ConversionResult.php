<?php

namespace App\Support\Conversion;

class ConversionResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $pdfContents,
        public readonly ?string $driver,
        public readonly ?string $errorMessage,
    ) {}

    public static function success(string $driver, string $pdfContents): self
    {
        return new self(true, $pdfContents, $driver, null);
    }

    public static function failure(?string $errorMessage = null): self
    {
        return new self(false, null, null, $errorMessage);
    }
}
