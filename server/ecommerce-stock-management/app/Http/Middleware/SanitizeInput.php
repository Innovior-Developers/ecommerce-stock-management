<?php
// filepath: server/ecommerce-stock-management/app/Http/Middleware/SanitizeInput.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\QuerySanitizer;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get all input
        $input = $request->all();

        // Sanitize recursively
        $sanitizedInput = $this->sanitizeRecursive($input);

        // Replace request input with sanitized version
        $request->merge($sanitizedInput);

        return $next($request);
    }

    /**
     * Recursively sanitize input
     * 
     * @param mixed $input
     * @return mixed
     */
    private function sanitizeRecursive($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeRecursive'], $input);
        }

        if (is_string($input)) {
            return QuerySanitizer::sanitize($input);
        }

        return $input;
    }
}