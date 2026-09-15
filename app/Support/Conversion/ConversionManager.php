<?php

namespace App\Support\Conversion;

use App\Models\Resource;
use App\Support\Conversion\Drivers\CloudConvertDriver;
use App\Support\Conversion\Drivers\GotenbergDriver;

class ConversionManager
{
    public function __construct(
        private readonly CloudConvertDriver $cloudConvert,
        private readonly GotenbergDriver $gotenberg,
    ) {}

    public function convert(Resource $resource): ConversionResult
    {
        $result = $this->cloudConvert->convert($resource);

        if ($result->success) {
            return $result;
        }

        $fallback = $this->gotenberg->convert($resource);

        if ($fallback->success) {
            return $fallback;
        }

        return ConversionResult::failure($fallback->errorMessage ?? $result->errorMessage);
    }
}
