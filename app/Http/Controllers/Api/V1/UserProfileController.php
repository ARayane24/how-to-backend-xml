<?php

// namespace App\Http\Controllers\Api\V1;

// use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\Log;
// use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
// use App\Http\Requests\StoreUserProfileRequest;
// use App\Http\Requests\UpdateUserProfileRequest;
// use App\Http\Resources\V1\ProfileCollection;
// use App\Http\Resources\V1\ProfileResource;
// use App\Models\UserProfile;
// use App\Utils\DB_Utils;

// class UserProfileController extends Controller
// {
/**
 * Display a listing of the resource.
 */
// public function index()
// {
//     $blocks = DB_Utils::getXmlBlocks("//userProfiles");

//     if (!$blocks) {
//         return response('<error>No user found</error>', 404)
//             ->header('Content-Type', 'application/xml');
//     }

//     return response(implode("\n", $blocks), 200)
//         ->header('Content-Type', 'application/xml');
// }


// public function show(int $id)
// {
//     try {
//         // Access JWT payload from request attributes
//         $jwtPayload = request()->attributes->get('jwtPayload');
//         Log::debug('JWT Payload:', $jwtPayload ?? ['No JWT payload found']);
//         $account = DB_Utils::getXmlBlocks("//userProfile[@id='$id']")[0] ?? null;

//         if (!$account) {
//             return response('<error>Account not found</error>', 404)
//                 ->header('Content-Type', 'application/xml');
//         }

//         return response($account, 200)
//             ->header('Content-Type', 'application/xml');
//     } catch (NotFoundHttpException $e) {
//         return response('<error>Account not found</error>', 404)
//             ->header('Content-Type', 'application/xml');
//     }
// }

/**
 * Show the form for creating a new resource.
 */
// public function create()
// {
//     //
// }

/**
 * Store a newly created resource in storage.
 */
// public function store(int $idAccount)
// {
//     $newProfile = request()->getContent();

//     $jwtPayload = request()->attributes->get('jwtPayload');
//     Log::debug('JWT Payload:', $jwtPayload ?? ['No JWT payload found']);
//     if (!$newProfile) {
//         return response('<error>No data provided</error>', 400)
//             ->header('Content-Type', 'application/xml');
//     }

//     try {
//         $xml = new \SimpleXMLElement($newProfile);

//         if (!isset($xml->firstName) || !isset($xml->lastName) || !isset($xml->bio)) {
//             return response('<error>Missing required fields</error>', 400)
//                 ->header('Content-Type', 'application/xml');
//         }

//         $account = DB_Utils::getXmlBlocks("//account[@id='$jwtPayload[sub]']")[0] ?? null;

//         if (!$account) {
//             return response('<error>Account not found</error>', 404)
//                 ->header('Content-Type', 'application/xml');
//         }
//         $blocks = DB_Utils::getXmlBlocks("//userProfile");
//         $newId = count($blocks) + 1;

//         $newProfile = "<userProfile id=\"$newId\" idAccount=\"$idAccount\">\n" .
//             "    <firstName>{$xml->firstName}</firstName>\n" .
//             "    <lastName>{$xml->lastName}</lastName>\n" .
//             "    <profilePicture>{$xml->profilePicture}</profilePicture>\n" .
//             "    <bio>{$xml->bio}</bio>\n" .
//             "    <joined>" . date('Y-m-d') . "</joined>\n" .
//             "    </userProfile>";

//         if (DB_Utils::addBlock('/db/userProfiles', $newProfile)) {
//             return response($newProfile, 201)
//                 ->header('Content-Type', 'application/xml');
//         }

//         return response("<error>Could not create the new user</error>", 500)
//             ->header('Content-Type', 'application/xml');
//     } catch (\Exception $e) {
//         return response('<error>Invalid XML format</error>', 400)
//             ->header('Content-Type', 'application/xml');
//     }
// }


/**
 * Display the specified resource.
 */
// public function show(UserProfile $userProfile)
// {
//     return new ProfileResource($userProfile);
// }

/**
 * Show the form for editing the specified resource.
 */
// public function edit(UserProfile $userProfile)
// {
//     //
// }


/**
 * Update the specified resource in storage.
 */
// public function update(int $id)
// {
//     try {
//         $profileXmlString = DB_Utils::getXmlBlocks("//userProfile[@id='$id']")[0] ?? null;
//         error_log($profileXmlString);
//         if (!$profileXmlString) {
//             return response('<error>User profile not found</error>', 404)
//                 ->header('Content-Type', 'application/xml');
//         }

//         $oldXml = new \SimpleXMLElement($profileXmlString);
//         $idAccount = (string) $oldXml['idAccount'];

//         $updateData = request()->getContent();
//         if (empty($updateData)) {
//             return response('<error>No update data provided</error>', 400)
//                 ->header('Content-Type', 'application/xml');
//         }

//         try {
//             $newXml = new \SimpleXMLElement($updateData);

//             if (isset($newXml->firstName)) {
//                 $oldXml->firstName = (string) $newXml->firstName;
//             }
//             if (isset($newXml->lastName)) {
//                 $oldXml->lastName = (string) $newXml->lastName;
//             }
//             if (isset($newXml->profilePicture)) {
//                 $oldXml->profilePicture = (string) $newXml->profilePicture;
//             }
//             if (isset($newXml->bio)) {
//                 $oldXml->bio = (string) $newXml->bio;
//             }

//             $updatedProfile = "<userProfile id=\"$id\" idAccount=\"$idAccount\">\n" .
//                 "    <firstName>{$oldXml->firstName}</firstName>\n" .
//                 "    <lastName>{$oldXml->lastName}</lastName>\n" .
//                 "    <profilePicture>{$oldXml->profilePicture}</profilePicture>\n" .
//                 "    <bio>{$oldXml->bio}</bio>\n" .
//                 "    <joined>{$oldXml->joined}</joined>\n" .
//                 "</userProfile>";

//             DB_Utils::editBlock("//userProfile[@id='$id']", $updatedProfile);

//             return response($updatedProfile, 200)
//                 ->header('Content-Type', 'application/xml');
//         } catch (\Exception $e) {
//             error_log("Error parsing update XML for profile $id: " . $e->getMessage());
//             return response('<error>Invalid XML format in update data</error>', 400)
//                 ->header('Content-Type', 'application/xml');
//         }
//     } catch (\Exception $e) {
//         error_log("Error processing update request for profile $id: " . $e->getMessage());
//         return response('<error>Failed to process update request</error>', 500)
//             ->header('Content-Type', 'application/xml');
//     }
// }

/**
 * Remove the specified resource from storage.
 */
// public function destroy(int $id)
// {
//     try {
//         $profileExists = DB_Utils::getXmlBlocks("//userProfile[@id='$id']")[0] ?? null;

//         if (!$profileExists) {
//             return response('<error>User profile not found</error>', 404)
//                 ->header('Content-Type', 'application/xml');
//         }

//         DB_Utils::removeBlock("//userProfile[@id='$id']");
//         return response(204)
//             ->header('Content-Type', 'application/xml');
//     } catch (\Exception $e) {
//         error_log("Error deleting user profile $id: " . $e->getMessage());
//         return response('<error>Failed to process delete request</error>', 500)
//             ->header('Content-Type', 'application/xml');
//     }
// }
// }

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Account;
use Illuminate\Http\Request;
use SimpleXMLElement;

class UserProfileController extends Controller
{
    /**
     * Display a listing of all user profiles.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // Get all user profiles with their related accounts
            $profiles = UserProfile::with('account')->get();

            // Create XML response
            $xml = new SimpleXMLElement('<userProfiles></userProfiles>');

            foreach ($profiles as $profile) {
                $profileNode = $xml->addChild('userProfile');
                $profileNode->addAttribute('id', $profile->id);
                $profileNode->addAttribute('idAccount', $profile->account_id);
                $profileNode->addChild('firstName', $profile->first_name ?? '');
                $profileNode->addChild('lastName', $profile->last_name ?? '');
                $profileNode->addChild('profilePicture', $profile->profile_picture ?? '');
                $profileNode->addChild('bio', $profile->bio ?? '');
                $profileNode->addChild('joined', $profile->joined_date ?? '');
            }

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display the specified user profile.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            // Find the profile
            $profile = UserProfile::with('account')->find($id);

            if (!$profile) {
                return response('<error>User profile not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Create XML response
            $xml = new SimpleXMLElement('<userProfile></userProfile>');
            $xml->addAttribute('id', $profile->id);
            $xml->addAttribute('idAccount', $profile->account_id);
            $xml->addChild('firstName', $profile->first_name ?? '');
            $xml->addChild('lastName', $profile->last_name ?? '');
            $xml->addChild('profilePicture', $profile->profile_picture ?? '');
            $xml->addChild('bio', $profile->bio ?? '');
            $xml->addChild('joined', $profile->joined_date ?? '');

            // Add account information
            if ($profile->account) {
                $accountNode = $xml->addChild('account');
                $accountNode->addAttribute('id', $profile->account->id);
                $accountNode->addChild('userName', $profile->account->username);
                $accountNode->addChild('email', $profile->account->email);
            }

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Store a newly created user profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Parse XML input
            $xmlInput = simplexml_load_string($request->getContent());

            if (!isset($xmlInput['idAccount'])) {
                return response('<error>Missing required account ID</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            $accountId = (int)$xmlInput['idAccount'];

            // Check if account exists
            $account = Account::find($accountId);
            if (!$account) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Check if profile already exists for this account
            $existingProfile = UserProfile::where('account_id', $accountId)->first();
            if ($existingProfile) {
                return response('<error>Profile already exists for this account</error>', 409)
                    ->header('Content-Type', 'application/xml');
            }

            // Create new profile
            $profile = new UserProfile();
            $profile->account_id = $accountId;
            $profile->first_name = isset($xmlInput->firstName) ? (string)$xmlInput->firstName : '';
            $profile->last_name = isset($xmlInput->lastName) ? (string)$xmlInput->lastName : '';
            $profile->profile_picture = isset($xmlInput->profilePicture) ? (string)$xmlInput->profilePicture : '';
            $profile->bio = isset($xmlInput->bio) ? (string)$xmlInput->bio : '';
            $profile->joined_date = isset($xmlInput->joined) ? (string)$xmlInput->joined : now()->format('Y-m-d');
            $profile->save();

            // Create XML response
            $xml = new SimpleXMLElement('<userProfile></userProfile>');
            $xml->addAttribute('id', $profile->id);
            $xml->addAttribute('idAccount', $profile->account_id);
            $xml->addChild('firstName', $profile->first_name);
            $xml->addChild('lastName', $profile->last_name);
            $xml->addChild('profilePicture', $profile->profile_picture);
            $xml->addChild('bio', $profile->bio);
            $xml->addChild('joined', $profile->joined_date);

            return response($xml->asXML(), 201)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Update the specified user profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the profile
            $profile = UserProfile::find($id);

            if (!$profile) {
                return response('<error>User profile not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Parse XML input
            $xmlInput = simplexml_load_string($request->getContent());

            // Update profile fields
            if (isset($xmlInput->firstName)) {
                $profile->first_name = (string)$xmlInput->firstName;
            }

            if (isset($xmlInput->lastName)) {
                $profile->last_name = (string)$xmlInput->lastName;
            }

            if (isset($xmlInput->profilePicture)) {
                $profile->profile_picture = (string)$xmlInput->profilePicture;
            }

            if (isset($xmlInput->bio)) {
                $profile->bio = (string)$xmlInput->bio;
            }

            // Don't allow changing account_id or joined_date after creation
            // for security and data integrity reasons

            $profile->save();

            // Create XML response
            $xml = new SimpleXMLElement('<userProfile></userProfile>');
            $xml->addAttribute('id', $profile->id);
            $xml->addAttribute('idAccount', $profile->account_id);
            $xml->addChild('firstName', $profile->first_name);
            $xml->addChild('lastName', $profile->last_name);
            $xml->addChild('profilePicture', $profile->profile_picture);
            $xml->addChild('bio', $profile->bio);
            $xml->addChild('joined', $profile->joined_date);

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Remove the specified user profile.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $profile = UserProfile::find($id);

            if (!$profile) {
                return response('<error>User profile not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            $profile->delete();

            return response('<success>Profile deleted successfully</success>', 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Get profile by account ID.
     *
     * @param  int  $accountId
     * @return \Illuminate\Http\Response
     */
    public function getByAccountId($accountId)
    {
        try {
            // Find profile by account ID
            $profile = UserProfile::where('account_id', $accountId)->first();

            if (!$profile) {
                return response('<error>Profile not found for this account</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Create XML response
            $xml = new SimpleXMLElement('<userProfile></userProfile>');
            $xml->addAttribute('id', $profile->id);
            $xml->addAttribute('idAccount', $profile->account_id);
            $xml->addChild('firstName', $profile->first_name ?? '');
            $xml->addChild('lastName', $profile->last_name ?? '');
            $xml->addChild('profilePicture', $profile->profile_picture ?? '');
            $xml->addChild('bio', $profile->bio ?? '');
            $xml->addChild('joined', $profile->joined_date ?? '');

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Search for profiles by name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        try {
            $query = $request->query('q');

            if (!$query) {
                return response('<error>Search query is required</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            // Search by first or last name
            $profiles = UserProfile::where('first_name', 'ILIKE', "%$query%")
                ->orWhere('last_name', 'ILIKE', "%$query%")
                ->get();

            // Create XML response
            $xml = new SimpleXMLElement('<userProfiles></userProfiles>');

            foreach ($profiles as $profile) {
                $profileNode = $xml->addChild('userProfile');
                $profileNode->addAttribute('id', $profile->id);
                $profileNode->addAttribute('idAccount', $profile->account_id);
                $profileNode->addChild('firstName', $profile->first_name ?? '');
                $profileNode->addChild('lastName', $profile->last_name ?? '');
                $profileNode->addChild('profilePicture', $profile->profile_picture ?? '');
                $profileNode->addChild('bio', $profile->bio ?? '');
                $profileNode->addChild('joined', $profile->joined_date ?? '');
            }

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }
}
