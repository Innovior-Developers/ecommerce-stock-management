<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * SanitizeInput middleware
 *
 * Trims strings, strips HTML tags, and normalizes whitespace for all request inputs.
 */
class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $data = $request->all();

        $sanitized = $this->sanitize($data);

        // Merge sanitized input back into the request
        $request->replace($sanitized);

        // If JSON, also update the internal json bag to keep consistency
        if ($request->isJson()) {
            $request->setJson(new ParameterBag($sanitized));
        }

        return $next($request);
    }

    /**
     * Recursively sanitize input values.
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->sanitize($v);
            }
            return $value;
        }

        if (is_string($value)) {
            $value = trim($value);
            $value = strip_tags($value);
            $value = preg_replace('/\s+/u', ' ', $value);
            return $value;
        }

        return $value;
    }
}