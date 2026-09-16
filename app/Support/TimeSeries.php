<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimeSeries
{
    /**
     * SQL expression to format a datetime column as 'YYYY-MM-DD', portable
     * across the drivers this app runs on (pgsql in dev, mysql in
     * production, sqlite in tests).
     */
    public static function dayExpression(string $column = 'created_at'): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m-%d')",
        };
    }

    /**
     * Same as dayExpression() but formatted as 'YYYY-MM'.
     */
    public static function monthExpression(string $column = 'created_at'): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    /**
     * SQL expression that extracts the year as an integer. EXTRACT(YEAR
     * FROM ...) works on pgsql/mysql but SQLite has no EXTRACT().
     */
    public static function yearExpression(string $column = 'created_at'): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "CAST(strftime('%Y', {$column}) AS INTEGER)",
            default => "EXTRACT(YEAR FROM {$column})",
        };
    }

    /**
     * Turn a "year => total" collection into a gap-filled series from the
     * earliest year seen through the current year.
     */
    public static function fillYears(Collection $rows, string $yearKey = 'year', string $totalKey = 'total'): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $minYear = (int) $rows->min($yearKey);
        $maxYear = max(now()->year, (int) $rows->max($yearKey));

        return collect(range($minYear, $maxYear))->map(function ($year) use ($rows, $yearKey, $totalKey) {
            $row = $rows->first(fn ($r) => (int) $r->{$yearKey} === $year);

            return ['label' => (string) $year, 'value' => $row ? (int) $row->{$totalKey} : 0];
        });
    }

    /**
     * Build a gap-filled daily series for the trailing $days days.
     * $countsByDay is keyed by 'Y-m-d'.
     */
    public static function fillDays(Collection $countsByDay, int $days = 30): Collection
    {
        return collect(range(0, $days - 1))->map(function ($i) use ($countsByDay, $days) {
            $date = now()->subDays($days - 1 - $i);
            $key = $date->format('Y-m-d');

            return ['label' => $date->format('M j'), 'value' => (int) ($countsByDay[$key] ?? 0)];
        });
    }

    /**
     * Build a gap-filled monthly series for the trailing $months months.
     * $countsByMonth is keyed by 'Y-m'.
     */
    public static function fillMonths(Collection $countsByMonth, int $months = 12): Collection
    {
        return collect(range(0, $months - 1))->map(function ($i) use ($countsByMonth, $months) {
            $date = now()->subMonths($months - 1 - $i);
            $key = $date->format('Y-m');

            return ['label' => $date->format("M 'y"), 'value' => (int) ($countsByMonth[$key] ?? 0)];
        });
    }
}
