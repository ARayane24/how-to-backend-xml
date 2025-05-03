<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Utils\DB_Utils;

class ourMiddleGuy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // Note: this just decode the token it doesnt even check the validity of token 
    public function handle(Request $request, Closure $next): Response
    {

        $authorizationHeader = $request->header('Authorization');

        $uri = $request->path();
        $method = $request->method();

        // Log the route information
        Log::debug("Request to: {$method} {$uri} ");

        if ($authorizationHeader && str_starts_with($authorizationHeader, 'Bearer ')) {
            $token = substr($authorizationHeader, 7);
            try {
                $tokenParts = explode('.', $token);
                if (count($tokenParts) === 3) {
                    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
                    if ($payload !== false) {
                        $decodedPayload = json_decode($payload, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            // Log::info('Decoded JWT Payload:', $decodedPayload);
                            $username = $decodedPayload['userName'] ?? null;
                            $email = $decodedPayload['email'] ?? null;
                            $exp = $decodedPayload['exp'] ?? null;
                            if ($exp && time() > $exp) {
                                Log::warning('JWT token has expired.');
                                return response('<error>JWT token has expired</error>', 401)
                                    ->header('Content-Type', 'application/xml');
                            }
                            if ($username && $email) {
                                $account = DB_Utils::getXmlBlocks("//account[userName='$username' and email='$email']")[0] ?? null;
                                if ($account) {
                                    Log::info("User authenticated: $username ($email)");
                                    $request->attributes->set('jwtPayload', $decodedPayload);
                                } else {

                                    return response('<error>Invalide JWT token</error>', 401)
                                        ->header('Content-Type', 'application/xml');
                                }
                            }
                        } else {
                            Log::warning('Failed to decode JWT payload: ' . json_last_error_msg());
                            return response('<error>Failed to decode JWT payload </error>', 401)
                                ->header('Content-Type', 'application/xml');
                        }
                    } else {
                        Log::warning('Failed to base64 decode JWT payload.');
                        return response('<error>Failed to decode JWT payload </error>', 401)
                            ->header('Content-Type', 'application/xml');
                    }
                } else {
                    Log::warning('Invalid JWT structure: Incorrect number of segments.');
                    return response('<error>Failed to decode JWT payload </error>', 401)
                        ->header('Content-Type', 'application/xml');
                }
            } catch (\Exception $e) {
                Log::error('Error decoding JWT: ' . $e->getMessage());
                return response('<error>Failed to decode JWT payload </error>', 401)
                    ->header('Content-Type', 'application/xml');
            }
        } else {
            Log::warning('Authorization header missing or not in Bearer format.');
            return response('<error>Failed to decode JWT payload </error>', 401)
                ->header('Content-Type', 'application/xml');
        }
        return $next($request);
    }
}
