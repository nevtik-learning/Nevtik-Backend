<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;

trait MengukurWaktu
{
    protected function ukurMulai(): float
    {
        if (config('app.debug')) {
            DB::flushQueryLog();
            DB::enableQueryLog();
        }

        return microtime(true);
    }

    // hanya muncul kalau APP_DEBUG=true
    protected function debug(float $mulai): array
    {
        if (! config('app.debug')) {
            return [];
        }

        $log = collect(DB::getQueryLog());

        return ['debug' => [
            'total_ms'     => round((microtime(true) - $mulai) * 1000),
            'query_ms'     => round($log->sum('time')),
            'jumlah_query' => $log->count(),
            'daftar_query' => $log->pluck('query'),
        ]];
    }
}