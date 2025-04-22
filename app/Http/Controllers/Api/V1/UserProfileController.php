<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserProfileRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Http\Resources\V1\ProfileCollection;
use App\Http\Resources\V1\ProfileResource;
use App\Models\UserProfile;
use App\Utils\DB_Utils;

class UserProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blocks = DB_Utils::getXmlBlocks("//userProfiles");

        if (!$blocks) {
            return response('<error>No user found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }

        return response(implode("\n", $blocks), 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(int $idAccount)
    {
        $newProfile = request()->getContent();

        if (!$newProfile) {
            return response('<error>No data provided</error>', 400)
                ->header('Content-Type', 'application/xml');
        }

        try {
            $xml = new \SimpleXMLElement($newProfile);

            if (!isset($xml->firstName) || !isset($xml->lastName) || !isset($xml->bio)) {
                return response('<error>Missing required fields</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            $account = DB_Utils::getXmlBlocks("//account[@id='$idAccount']")[0] ?? null;

            if (!$account) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }
            $blocks = DB_Utils::getXmlBlocks("//userProfile");
            $newId = count($blocks) + 1;

            $newProfile = "<userProfile id=\"$newId\" idAccount=\"$idAccount\">\n" .
                "    <firstName>{$xml->firstName}</firstName>\n" .
                "    <lastName>{$xml->lastName}</lastName>\n" .
                "    <profilePicture>{$xml->profilePicture}</profilePicture>\n" .
                "    <bio>{$xml->bio}</bio>\n" .
                "    <joined>" . date('Y-m-d') . "</joined>\n" .
                "    </userProfile>";

            if (DB_Utils::addBlock('/db/userProfiles', $newProfile)) {
                return response($newProfile, 201)
                    ->header('Content-Type', 'application/xml');
            }

            return response("<error>Could not create the new user</error>", 500)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>Invalid XML format</error>', 400)
                ->header('Content-Type', 'application/xml');
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(UserProfile $userProfile)
    {
        return new ProfileResource($userProfile);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserProfile $userProfile)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(int $id)
    {
        try {
            $profileXmlString = DB_Utils::getXmlBlocks("//userProfile[@id='$id']")[0] ?? null;
            error_log($profileXmlString);
            if (!$profileXmlString) {
                return response('<error>User profile not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            $oldXml = new \SimpleXMLElement($profileXmlString);
            $idAccount = (string) $oldXml['idAccount'];

            $updateData = request()->getContent();
            if (empty($updateData)) {
                return response('<error>No update data provided</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            try {
                $newXml = new \SimpleXMLElement($updateData);

                if (isset($newXml->firstName)) {
                    $oldXml->firstName = (string) $newXml->firstName;
                }
                if (isset($newXml->lastName)) {
                    $oldXml->lastName = (string) $newXml->lastName;
                }
                if (isset($newXml->profilePicture)) {
                    $oldXml->profilePicture = (string) $newXml->profilePicture;
                }
                if (isset($newXml->bio)) {
                    $oldXml->bio = (string) $newXml->bio;
                }

                $updatedProfile = "<userProfile id=\"$id\" idAccount=\"$idAccount\">\n" .
                    "    <firstName>{$oldXml->firstName}</firstName>\n" .
                    "    <lastName>{$oldXml->lastName}</lastName>\n" .
                    "    <profilePicture>{$oldXml->profilePicture}</profilePicture>\n" .
                    "    <bio>{$oldXml->bio}</bio>\n" .
                    "    <joined>{$oldXml->joined}</joined>\n" .
                    "</userProfile>";

                DB_Utils::editBlock("//userProfile[@id='$id']", $updatedProfile);

                return response($updatedProfile, 200)
                    ->header('Content-Type', 'application/xml');


            } catch (\Exception $e) {
                error_log("Error parsing update XML for profile $id: " . $e->getMessage());
                return response('<error>Invalid XML format in update data</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

        } catch (\Exception $e) {
            error_log("Error processing update request for profile $id: " . $e->getMessage());
            return response('<error>Failed to process update request</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {
            $profileExists = DB_Utils::getXmlBlocks("//userProfile[@id='$id']")[0] ?? null;

            if (!$profileExists) {
                return response('<error>User profile not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            DB_Utils::removeBlock("//userProfile[@id='$id']");
            return response(null, 204)
                ->header('Content-Type', 'application/xml');

        } catch (\Exception $e) {
            error_log("Error deleting user profile $id: " . $e->getMessage());
            return response('<error>Failed to process delete request</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }
}
