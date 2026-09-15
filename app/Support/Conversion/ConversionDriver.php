<?php

namespace App\Support\Conversion;

use App\Models\Resource;

interface ConversionDriver
{
    public function convert(Resource $resource): ConversionResult;
}
