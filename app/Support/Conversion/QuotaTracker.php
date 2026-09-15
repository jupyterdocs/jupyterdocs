<?php

namespace App\Support\Conversion;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks how many CloudConvert conversions have actually been used today,
 * so ConversionManager knows when to stop calling CloudConvert and fail
 * over to Gotenberg instead. A cache counter is enough here — no dedicated
 * table needed — since CACHE_STORE=database already makes it durable.
 */
class QuotaTracker
{
    public function remaining(): int
    {
        return max(0, config('conversion.daily_limit') - $this->used());
    }

    public function hasQuota(): bool
    {
        return $this->remaining() > 0;
    }

    public function used(): int
    {
        return (int) Cache::get($this->key(), 0);
    }

    public function recordUsage(): void
    {
        $key = $this->key();

        if (! Cache::has($key)) {
            // Expire at the end of the UTC day the counter started in.
            Cache::put($key, 0, now()->endOfDay());
        }

        Cache::increment($key);
    }

    public function exhaustQuotaForToday(): void
    {
        Cache::put($this->key(), config('conversion.daily_limit'), now()->endOfDay());
    }

    private function key(): string
    {
        return 'cloudconvert:quota:'.now()->format('Y-m-d');
    }
}
