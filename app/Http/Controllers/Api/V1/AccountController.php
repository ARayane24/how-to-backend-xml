<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use SimpleXMLElement;

class AccountController extends Controller
{
    /**
     * Display all the accounts
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all accounts with their profiles
        $accounts = Account::with('userProfile')->get();

        // Create XML response
        $xml = new SimpleXMLElement('<accounts></accounts>');

        foreach ($accounts as $account) {
            $accountNode = $xml->addChild('account');
            $accountNode->addAttribute('id', $account->id);
            $accountNode->addChild('userName', $account->username);
            // Don't include the password in responses for security
            // $accountNode->addChild('password', $account->password);
            $accountNode->addChild('email', $account->email);

            if ($account->userProfile) {
                $profileNode = $accountNode->addChild('profile');
                $profileNode->addAttribute('id', $account->userProfile->id);
                $profileNode->addChild('firstName', $account->userProfile->first_name ?? '');
                $profileNode->addChild('lastName', $account->userProfile->last_name ?? '');
                $profileNode->addChild('profilePicture', $account->userProfile->profile_picture ?? '');
                $profileNode->addChild('bio', $account->userProfile->bio ?? '');
                $profileNode->addChild('joined', $account->userProfile->joined_date ?? '');
            }
        }

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Display a specific account
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Find the account
        $account = Account::with('userProfile')->find($id);

        if (!$account) {
            return response('<error>Account not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }

        // Create XML response
        $xml = new SimpleXMLElement('<account></account>');
        $xml->addAttribute('id', $account->id);
        $xml->addChild('userName', $account->username);
        // Don't include password in responses
        $xml->addChild('email', $account->email);

        // Add profile information if available
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
     * Store a new account
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Parse XML input
            $xmlInput = simplexml_load_string($request->getContent());

            if (!isset($xmlInput->userName) || !isset($xmlInput->password) || !isset($xmlInput->email)) {
                return response('<error>Missing required fields</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            // Check for existing email
            $existingAccount = Account::where('email', (string)$xmlInput->email)->first();
            if ($existingAccount) {
                return response('<error>Email already in use</error>', 409)
                    ->header('Content-Type', 'application/xml');
            }

            // Create the account
            $account = Account::create([
                'username' => (string)$xmlInput->userName,
                'password' => Hash::make((string)$xmlInput->password), // Hash password for security
                'email' => (string)$xmlInput->email
            ]);

            // Create a basic user profile
            $profile = new UserProfile([
                'joined_date' => now()
            ]);

            // Save the profile and associate with account
            $account->userProfile()->save($profile);

            // Create XML response
            $xml = new SimpleXMLElement('<account></account>');
            $xml->addAttribute('id', $account->id);
            $xml->addChild('userName', $account->username);
            // Don't include password in response
            $xml->addChild('email', $account->email);

            // Add profile info
            $profileNode = $xml->addChild('profile');
            $profileNode->addAttribute('id', $profile->id);
            $profileNode->addChild('firstName', '');
            $profileNode->addChild('lastName', '');
            $profileNode->addChild('profilePicture', '');
            $profileNode->addChild('bio', '');
            $profileNode->addChild('joined', $profile->joined_date->format('Y-m-d'));

            return response($xml->asXML(), 201)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 400)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Update an existing account
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the account
            $account = Account::find($id);

            if (!$account) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Parse XML input
            $xmlInput = simplexml_load_string($request->getContent());

            // Update account fields if provided
            if (isset($xmlInput->userName)) {
                $account->username = (string)$xmlInput->userName;
            }

            if (isset($xmlInput->password)) {
                $account->password = Hash::make((string)$xmlInput->password);
            }

            if (isset($xmlInput->email)) {
                // Check if email is already used by another account
                $existingAccount = Account::where('email', (string)$xmlInput->email)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingAccount) {
                    return response('<error>Email already in use</error>', 409)
                        ->header('Content-Type', 'application/xml');
                }

                $account->email = (string)$xmlInput->email;
            }

            // Save account changes
            $account->save();

            // Get or create profile
            $profile = $account->userProfile;
            if (!$profile) {
                $profile = new UserProfile([
                    'account_id' => $account->id,
                    'joined_date' => now()
                ]);
                $profile->save();
            }

            // Update profile fields if provided
            if (isset($xmlInput->profile)) {
                if (isset($xmlInput->profile->firstName)) {
                    $profile->first_name = (string)$xmlInput->profile->firstName;
                }

                if (isset($xmlInput->profile->lastName)) {
                    $profile->last_name = (string)$xmlInput->profile->lastName;
                }

                if (isset($xmlInput->profile->profilePicture)) {
                    $profile->profile_picture = (string)$xmlInput->profile->profilePicture;
                }

                if (isset($xmlInput->profile->bio)) {
                    $profile->bio = (string)$xmlInput->profile->bio;
                }

                // Save profile changes
                $profile->save();
            }

            // Create XML response
            $xml = new SimpleXMLElement('<account></account>');
            $xml->addAttribute('id', $account->id);
            $xml->addChild('userName', $account->username);
            $xml->addChild('email', $account->email);

            // Add profile info
            $profileNode = $xml->addChild('profile');
            $profileNode->addAttribute('id', $profile->id);
            $profileNode->addChild('firstName', $profile->first_name ?? '');
            $profileNode->addChild('lastName', $profile->last_name ?? '');
            $profileNode->addChild('profilePicture', $profile->profile_picture ?? '');
            $profileNode->addChild('bio', $profile->bio ?? '');
            $profileNode->addChild('joined', $profile->joined_date->format('Y-m-d'));

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 400)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Delete an account
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $account = Account::find($id);

        if (!$account) {
            return response('<error>Account not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }

        info($account);
        -
        // Account deletion will cascade to profile due to foreign key constraints
        $account->delete();

        return response('<success>Account deleted successfully</success>', 200)
            ->header('Content-Type', 'application/xml');
    }
}
