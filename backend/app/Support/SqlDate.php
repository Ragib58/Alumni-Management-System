<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Small helper to produce a driver-appropriate "group by month" expression so
 * analytics queries work on both PostgreSQL (primary) and SQLite (tests).
 */
class SqlDate
{
    /**
     * SQL expression that yields a 'YYYY-MM' string for the given column.
     */
    public static function month(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql'  => "to_char(date_trunc('month', $column), 'YYYY-MM')",
            'mysql'  => "DATE_FORMAT($column, '%Y-%m')",
            'sqlite' => "strftime('%Y-%m', $column)",
            default  => "to_char($column, 'YYYY-MM')",
        };
    }

    /**
     * SQL expression that yields the 4-digit year for the given column.
     */
    public static function year(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql'  => "EXTRACT(YEAR FROM $column)::int",
            'mysql'  => "YEAR($column)",
            'sqlite' => "CAST(strftime('%Y', $column) AS INTEGER)",
            default  => "EXTRACT(YEAR FROM $column)",
        };
    }
}
