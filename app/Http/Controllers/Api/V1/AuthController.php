<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\Utils\DB_Utils;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        Auth::shouldUse('api');
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request()->getContent();

        if (!$credentials) {
            return response('<error>No data provided</error>', 400)
                ->header('Content-Type', 'application/xml');
        }

        try {
            $xml = new \SimpleXMLElement($credentials);

            if ((!isset($xml->userName) && !isset($xml->email)) || !isset($xml->password)) {
                return response('<error>Missing required fields</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            $account = isset($xml->userName)
                ? DB_Utils::getXmlBlocks(".//account[userName='$xml->userName' and password='$xml->password']")[0] ?? null
                : DB_Utils::getXmlBlocks(".//account[email='$xml->email' and password='$xml->password']")[0] ?? null;

            if (!$account) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            $xml = new \SimpleXMLElement($account);
            $current_time = time();

            // Access token
            $accessToken = base64_encode(json_encode([
                'id' => (string) $xml->attributes()['id'],
                'email' => $xml->userName ?? $xml->email,
                'timestamp' => $current_time
            ]));

            // Refresh token
            $refreshToken = base64_encode(json_encode([
                'id' => (string) $xml->attributes()['id'],
                'email' => $xml->userName ?? $xml->email,
                'timestamp' => $current_time,
                'expires_at' => $current_time + (60 * 60),
                'type' => 'refresh'
            ]));

            $response = json_decode($this->respondWithToken($accessToken)->getContent(), true);

            $auth = "<auth accountId=\"" . $xml->attributes()['id'] . "\">\n" .
                "    <auth-token>{$response['access_token']}</auth-token>\n" .
                "    <expires>{$response['expires_in']}</expires>\n" .
                // Removed refresh token from XML
                "</auth>";

            return response($auth, 200)
                ->header('Content-Type', 'application/xml')
                ->cookie(
                    cookie(
                        'refresh_token',           // Name
                        $refreshToken,             // Value
                        60,                        // Minutes
                        '/api/refresh',            // Path (optional)
                        null,                      // Domain
                        true,                      // Secure
                        true,                      // HttpOnly
                        false,                     // Raw
                        'Strict'                   // SameSite
                    )
                );
        } catch (\Exception $e) {
            return response('<error>Invalid XML format</error>', 400)
                ->header('Content-Type', 'application/xml');
        }
    }



    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        // Optional: Validate the access token header
        $header = request()->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response('<error>Missing or invalid token</error>', 401)
                ->header('Content-Type', 'application/xml');
        }

        $refreshToken = request()->cookie('refresh_token');

        if (!$refreshToken) {
            return response('<error>Missing refresh token</error>', 400)
                ->header('Content-Type', 'application/xml');
        }

        try {
            $decoded = json_decode(base64_decode($refreshToken), true);

            if (!$decoded || $decoded['type'] !== 'refresh') {
                return response('<error>Invalid refresh token</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            $current_time = time();

            $accessToken = base64_encode(json_encode([
                'id' => $decoded['id'],
                'email' => $decoded['userName'] ?? $decoded['email'],
                'timestamp' => $current_time
            ]));

            $newRefreshToken = base64_encode(json_encode([
                'id' => $decoded['id'],
                'email' => $decoded['userName'] ?? $decoded['email'],
                'timestamp' => $current_time,
                'expires_at' => $current_time + (60 * 60),
                'type' => 'refresh'
            ]));

            $response = json_decode($this->respondWithToken($accessToken)->getContent(), true);

            $auth = "<auth accountId=\"" . $decoded['id'] . "\">\n" .
                "    <auth-token>{$response['access_token']}</auth-token>\n" .
                "    <expires>{$response['expires_in']}</expires>\n" .
                "</auth>";

            return response($auth, 200)
                ->header('Content-Type', 'application/xml')
                ->cookie(
                    cookie(
                        'refresh_token',
                        $newRefreshToken,
                        60, // minutes
                        '/api/refresh',
                        null,
                        true, // Secure
                        true, // HttpOnly
                        false,
                        'Strict'
                    )
                );
        } catch (\Exception $e) {
            return response('<error>Invalid refresh token format</error>', 400)
                ->header('Content-Type', 'application/xml');
        }
    }




    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60
        ]);
    }
}
