<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ourMiddleGuy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authorizationHeader = $request->header('Authorization');
        if ($authorizationHeader && str_starts_with($authorizationHeader, 'Bearer ')) {
            $token = substr($authorizationHeader, 7);
            try {
                $tokenParts = explode('.', $token);
                if (count($tokenParts) === 3) {
                    // Decode the payload (the second part)
                    // URL-safe base64 decoding
                    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
                    if ($payload !== false) {
                        $decodedPayload = json_decode($payload, true); // Decode as associative array
                        if (json_last_error() === JSON_ERROR_NONE) {
                            // You can now use $decodedPayload
                            Log::info('Decoded JWT Payload:', $decodedPayload);
                            // Optionally add the decoded payload to the request for later use
                            // $request->attributes->add(['jwt_payload' => $decodedPayload]);
                        } else {
                            Log::warning('Failed to JSON decode JWT payload: ' . json_last_error_msg());
                        }
                    } else {
                        Log::warning('Failed to base64 decode JWT payload.');
                    }
                } else {
                    Log::warning('Invalid JWT structure: Incorrect number of segments.');
                }
            } catch (\Exception $e) {
                Log::error('Error decoding JWT: ' . $e->getMessage());
            }
        } else {
            Log::warning('Authorization header missing or not in Bearer format.');
        }
        // Note: The line below this comment in the original code ($response = json_decode(...))
        // likely needs to be removed or adjusted as the Authorization header is typically not JSON.

        // $response = json_decode($authorizationHeader);
        return $next($request);
    }
}
