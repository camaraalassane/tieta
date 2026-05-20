<?php

namespace App\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogSlowQuery
{
    public function handle(QueryExecuted $event): void
    {
        $sql = $event->sql;
        $time = $event->time;
        $bindings = $event->bindings;

        if ($time > 100) {
            foreach ($bindings as $i => $binding) {
                if ($binding instanceof \DateTime) {
                    $bindings[$i] = $binding->format('Y-m-d H:i:s');
                } elseif (is_string($binding)) {
                    $bindings[$i] = "'$binding'";
                } elseif (is_null($binding)) {
                    $bindings[$i] = 'NULL';
                }
            }
            $sql = str_replace(['%', '?'], ['%%', '%s'], $sql);
            $sql = vsprintf($sql, $bindings);

            Log::channel('slow_queries')->warning("Requête SQL lente ({$time}ms)", [
                'sql' => $sql,
                'time_ms' => $time,
                'url' => request()->fullUrl(),
                'user_id' => Auth::id(),
            ]);
        }
    }
}
