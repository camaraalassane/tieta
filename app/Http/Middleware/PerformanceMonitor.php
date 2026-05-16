<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PerformanceMonitor
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        $end = microtime(true);
        $endMemory = memory_get_usage();

        $duration = round(($end - $start) * 1000, 2);
        $memoryUsed = round(($endMemory - $startMemory) / 1024, 2);

        if ($duration > 500) {
            Log::channel('performance')->warning('Requête lente détectée', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'memory_kb' => $memoryUsed,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        if ($duration > 2000) {
            Log::channel('performance')->critical('Requête très lente !', [
                'url' => $request->fullUrl(),
                'duration_ms' => $duration,
            ]);
        }

        return $response;
    }
}
