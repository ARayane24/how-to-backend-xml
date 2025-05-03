<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

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

    private function createTemporaryToken(Account $account)
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'sub' => $account->id,
            'username' => $account->username,
            'email' => $account->email,
            'iat' => time(),
            'exp' => time() + 36000000,
        ];

        // Encode header and payload
        $encodedHeader = base64_encode(json_encode($header));
        $encodedPayload = base64_encode(json_encode($payload));

        // Create signature
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, env('APP_KEY', 'secret'));

        // Return the complete token
        return $encodedHeader . '.' . $encodedPayload . '.' . $signature;
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $credentials = $request->getContent();

        if (!$credentials) {
            return response('<error>No data provided</error>', 400)
                ->header('Content-Type', 'application/xml');
        }

        try {
            // Parse XML input
            $credXml = new \SimpleXMLElement($credentials);

            if ((!isset($credXml->userName) && !isset($credXml->email)) || !isset($credXml->password)) {
                return response('<error>Missing required fields</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            // Search by username or email
            $identifier = isset($credXml->userName) ? 'username' : 'email';
            $value = isset($credXml->userName) ? (string)$credXml->userName : (string)$credXml->email;
            $password = (string)$credXml->password;

            // Log authentication attempt (remove in production)
            Log::info("Auth attempt with $identifier: $value");

            // Find the account
            $account = Account::where($identifier, $value)->first();

            if (!$account) {
                Log::info("Account not found for $identifier: $value");
                return response('<error>Invalid credentials</error>', 401)
                    ->header('Content-Type', 'application/xml');
            }

            // For debugging - log the found account details (remove in production)
            Log::info("Account found: " . $account->id . ", Password hash: " . substr($account->password, 0, 10) . "...");

            // Check if password is already hashed
            $passwordMatches = false;

            if (strlen($account->password) < 60) {
                // For development/testing: Plain text password comparison
                // WARNING: Remove this in production - all passwords should be hashed!
                $passwordMatches = $password === $account->password;
                Log::info("Using plain text password comparison: " . ($passwordMatches ? "Match" : "No Match"));

                // Consider hashing the password now for security
                if ($passwordMatches) {
                    $account->password = Hash::make($password);
                    $account->save();
                    Log::info("Updated password hash for account " . $account->id);
                }
            } else {
                // Proper hashed password comparison
                $passwordMatches = Hash::check($password, $account->password);
                Log::info("Using hashed password comparison: " . ($passwordMatches ? "Match" : "No Match"));
            }

            if (!$passwordMatches) {
                return response('<error>Invalid credentials</error>', 401)
                    ->header('Content-Type', 'application/xml');
            }

            // Generate JWT token
            try {
                // $token = Auth::login($account);
                $token = $this->createTemporaryToken($account);
                Log::info("Generated token for user " . $account->id);

                if (!$token) {
                    Log::error("Failed to generate token for user " . $account->id);
                    return response('<error>Authentication failed</error>', 401)
                        ->header('Content-Type', 'application/xml');
                }
            } catch (\Exception $e) {
                Log::error("JWT error: " . $e->getMessage());
                return response('<error>Authentication system error: ' . $e->getMessage() . '</error>', 500)
                    ->header('Content-Type', 'application/xml');
            }

            $refreshToken = $this->createTemporaryToken($account);
            // Return token in XML format
            $responseXml = new \SimpleXMLElement('<response></response>');
            $responseXml->addChild('token', $token);
            $responseXml->addChild('tokenType', 'bearer');
            $responseXml->addChild('expiresIn', Auth::factory()->getTTL() * 60);

            $accountNode = $responseXml->addChild('account');
            $accountNode->addAttribute('id', $account->id);
            $accountNode->addChild('userName', $account->username);
            $accountNode->addChild('email', $account->email);

            return response($responseXml->asXML(), 200)
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
            Log::error("Login error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }
    // public function login(Request $request)
    // {
    //     $credentials = $request->getContent();

    //     if (!$credentials) {
    //         return response('<error>No data provided</error>', 400)
    //             ->header('Content-Type', 'application/xml');
    //     }

    //     try {
    //         // Parse XML input
    //         $credXml = new \SimpleXMLElement($credentials);

    //         if ((!isset($credXml->userName) && !isset($credXml->email)) || !isset($credXml->password)) {
    //             return response('<error>Missing required fields</error>', 400)
    //                 ->header('Content-Type', 'application/xml');
    //         }

    //         // Search by username or email
    //         $identifier = isset($credXml->userName) ? 'username' : 'email';
    //         $value = isset($credXml->userName) ? (string)$credXml->userName : (string)$credXml->email;

    //         // Find the account
    //         $account = Account::where($identifier, $value)->first();

    //         // Check if account exists and password is correct
    //         if (!$account || !Hash::check((string)$credXml->password, $account->password)) {
    //             return response('<error>Invalid credentials</error>', 401)
    //                 ->header('Content-Type', 'application/xml');
    //         }

    //         // Generate JWT token
    //         $token = Auth::login($account);

    //         if (!$token) {
    //             return response('<error>Unauthorized</error>', 401)
    //                 ->header('Content-Type', 'application/xml');
    //         }

    //         // Return token in XML format
    //         $responseXml = new \SimpleXMLElement('<response></response>');
    //         $responseXml->addChild('token', $token);
    //         $responseXml->addChild('tokenType', 'bearer');
    //         $responseXml->addChild('expiresIn', Auth::factory()->getTTL() * 60);

    //         $accountNode = $responseXml->addChild('account');
    //         $accountNode->addAttribute('id', $account->id);
    //         $accountNode->addChild('userName', $account->username);
    //         $accountNode->addChild('email', $account->email);

    //         // Add profile data if available
    //         if ($account->userProfile) {
    //             $profileNode = $accountNode->addChild('profile');
    //             $profileNode->addAttribute('id', $account->userProfile->id);
    //             $profileNode->addChild('firstName', $account->userProfile->first_name ?? '');
    //             $profileNode->addChild('lastName', $account->userProfile->last_name ?? '');
    //             $profileNode->addChild('profilePicture', $account->userProfile->profile_picture ?? '');
    //             $profileNode->addChild('bio', $account->userProfile->bio ?? '');
    //             $profileNode->addChild('joined', $account->userProfile->joined_date ?? '');
    //         }

    //         return response($responseXml->asXML(), 200)
    //             ->header('Content-Type', 'application/xml');
    //     } catch (\Exception $e) {
    //         return response('<error>' . $e->getMessage() . '</error>', 400)
    //             ->header('Content-Type', 'application/xml');
    //     }
    // }

    /**
     * Get the authenticated Account.
     *
     * @return \Illuminate\Http\Response
     */
    public function me()
    {
        $account = Auth::user();

        if (!$account) {
            return response('<error>Unauthorized</error>', 401)
                ->header('Content-Type', 'application/xml');
        }

        // Create XML response
        $xml = new \SimpleXMLElement('<account></account>');
        $xml->addAttribute('id', $account->id);
        $xml->addChild('userName', $account->username);
        $xml->addChild('email', $account->email);

        // Load and add user profile if it exists
        $account->load('userProfile');
        if ($account->userProfile) {
            $profileNode = $xml->addChild('profile');
            $profileNode->addAttribute('id', $account->userProfile->id);
            $profileNode->addChild('firstName', $account->userProfile->first_name ?? '');
            $profileNode->addChild('lastName', $account->userProfile->last_name ?? '');
            $profileNode->addChild('profilePicture', $account->userProfile->profile_picture ?? '');
            $profileNode->addChild('bio', $account->userProfile->bio ?? '');
            $profileNode->addChild('joined', $account->userProfile->joined_date ?? '');
        }

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        Auth::logout();

        return response('<response>Successfully logged out</response>', 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\Response
     */
    public function refresh()
    {
        try {
            $token = Auth::refresh();

            $responseXml = new \SimpleXMLElement('<response></response>');
            $responseXml->addChild('token', $token);
            $responseXml->addChild('tokenType', 'bearer');
            $responseXml->addChild('expiresIn', Auth::factory()->getTTL() * 60);

            return response($responseXml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>Unable to refresh token</error>', 401)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Register a new account.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $credentials = $request->getContent();

        if (!$credentials) {
            return response('<error>No data provided</error>', 400)
                ->header('Content-Type', 'application/xml');
        }

        try {
            // Parse XML input
            $credXml = new \SimpleXMLElement($credentials);

            if (!isset($credXml->userName) || !isset($credXml->password) || !isset($credXml->email)) {
                return response('<error>Missing required fields</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            // Check if email already exists
            if (Account::where('email', (string)$credXml->email)->exists()) {
                return response('<error>Email already registered</error>', 409)
                    ->header('Content-Type', 'application/xml');
            }

            // Check if username already exists
            if (Account::where('username', (string)$credXml->userName)->exists()) {
                return response('<error>Username already taken</error>', 409)
                    ->header('Content-Type', 'application/xml');
            }

            // Create new account
            $account = Account::create([
                'username' => (string)$credXml->userName,
                'password' => Hash::make((string)$credXml->password),
                'email' => (string)$credXml->email,
            ]);

            // Create basic profile
            $account->userProfile()->create([
                'joined_date' => now(),
            ]);

            // Generate JWT token
            $token = Auth::login($account);

            // Return token in XML format
            $responseXml = new \SimpleXMLElement('<response></response>');
            $responseXml->addChild('token', $token);
            $responseXml->addChild('tokenType', 'bearer');
            $responseXml->addChild('expiresIn', Auth::factory()->getTTL() * 60);

            $accountNode = $responseXml->addChild('account');
            $accountNode->addAttribute('id', $account->id);
            $accountNode->addChild('userName', $account->username);
            $accountNode->addChild('email', $account->email);

            return response($responseXml->asXML(), 201)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 400)
                ->header('Content-Type', 'application/xml');
        }
    }
}
