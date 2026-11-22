<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyDeviceKey
{
    public function handle(Request $request, Closure $next)
    {
        $key = (string) config('services.gate.key');

        if ($key === '' || ! hash_equals($key, (string) $request->header('X-Gate-Key'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
