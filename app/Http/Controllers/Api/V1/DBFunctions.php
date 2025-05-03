<?php

namespace App\Http\Controllers\Api\V1;

// use App\Http\Requests\StoreAccountRequest;
// use App\Http\Requests\UpdateAccountRequest;
// use App\Models\Account;
use App\Http\Controllers\Controller;
// use App\Http\Resources\V1\AccountCollection;
// use App\Http\Resources\V1\AccountResource;
use App\Utils\DB_Utils;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Http\Response;
use Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function PHPUnit\Framework\throwException;
use Illuminate\Support\Facades\DB;
// use Illuminate\Http\Response;

class DBFunctions extends Controller
{

    //*Fetching all of a specific thing */
    public static function getAll($path)
    {
        try {
            // Using PostgreSQL with native XML support
            $xpath = "//db/$path";

            $result = DB::table('dbxml')
                ->selectRaw("xpath('$xpath', info::xml) AS xml")
                ->where('id', 1)
                ->first();

            if (!$result || empty($result->xml)) {
                return response("<error>No $path found</error>", 404)
                    ->header('Content-Type', 'application/xml');
            }

            // PostgreSQL returns XML fragments in a format we need to process
            $xmlFragments = $result->xml;
            if (is_array($xmlFragments)) {
                // Join the fragments with proper formatting
                $xmlContent = implode("\n", array_map(function ($fragment) {
                    return str_replace('\"', '"', trim($fragment, '{}"'));
                }, $xmlFragments));

                // Need to wrap in a root element for valid XML
            } else {
                // Single element case
                $xmlContent = str_replace('\"', '"', trim($xmlFragments, '{}"'));
            }

            return response($xmlContent, 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }


    // *Fetching a specific thing */
    public static function getOne($path, $id, ?int $idAccount = null)
    {
        if ($idAccount) {
            $path = $path . "[(@idTopic=$id and @idAccount=$idAccount)]";
        } else {
            if (str_contains($path, 'vote'))
                $path = $path . "[(@idTopic=$id)]";
            else
                $path = $path . "[@id=$id]";
        }
        $xml = DB::table('dbxml')
            ->selectRaw("xpath('//{$path}',info::xml) AS xml")
            ->first();

        if (!$xml) {
            return response('<error>Not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }
        $xml = (array) $xml->xml;

        $xml = implode('\n', $xml);
        $xml = str_replace(['"', '{', '}', ','], '', $xml);
        $xml = str_replace('\\', '"', $xml);


        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    //* Adding a new thing */

    public static function store($entityType, $data)
    {
        // Get the XML from database
        $dbXml = DB::table('dbxml')->where('id', 1)->first();

        // If no database record exists yet, create the initial structure
        if (!$dbXml) {
            // Create initial XML structure with all required root elements
            $initialXml = '<?xml version="1.0" encoding="UTF-8"?>
                            <db xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="schema.xsd">
                            <accounts></accounts>
                            <userProfiles></userProfiles>
                            <topics></topics>
                            <votes></votes>
                            </db>';

            // Insert the initial XML structure
            DB::table('dbxml')->insert([
                'id' => 1,
                'info' => $initialXml,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Retrieve the newly created record
            $dbXml = DB::table('dbxml')->where('id', 1)->first();
        }

        try {
            // Load the XML
            $xml = new \SimpleXMLElement($dbXml->info);

            // Find the parent node (create if doesn't exist)
            if (!isset($xml->{$entityType})) {
                $xml->addChild($entityType);
            }

            // Generate new ID based on existing elements
            $nodes = $xml->{$entityType}->children();
            $maxId = 0;

            // Find highest ID
            foreach ($nodes as $node) {
                if ((int)$node['id'] > $maxId) {
                    $maxId = (int)$node['id'];
                }
            }

            $newId = $maxId + 1;

            // Create the new entity based on entity type
            switch ($entityType) {
                case 'accounts':
                    $newEntity = $xml->accounts->addChild('account');
                    $newEntity->addAttribute('id', $newId);
                    $newEntity->addChild('userName', htmlspecialchars($data->userName));
                    $newEntity->addChild('password', htmlspecialchars($data->password));
                    $newEntity->addChild('email', htmlspecialchars($data->email));
                    break;

                case 'userProfiles':
                    $newEntity = $xml->userProfiles->addChild('userProfile');
                    $newEntity->addAttribute('id', $newId);
                    $newEntity->addAttribute('idAccount', $data->idAccount);
                    $newEntity->addChild('firstName', htmlspecialchars($data->firstName));
                    $newEntity->addChild('lastName', htmlspecialchars($data->lastName));
                    $newEntity->addChild('profilePicture', $data->profilePicture ?? '');
                    $newEntity->addChild('bio', htmlspecialchars($data->bio));
                    $newEntity->addChild('joined', date('Y-m-d')); // Current date
                    break;

                case 'topics':
                    $newEntity = $xml->topics->addChild('topic');
                    $newEntity->addAttribute('id', $newId);
                    $newEntity->addAttribute('idAccount', $data->idAccount);
                    $newEntity->addChild('problem', htmlspecialchars($data->problem));
                    $newEntity->addChild('description', htmlspecialchars($data->description));
                    $newEntity->addChild('createdAt', date('Y-m-d'));
                    $newEntity->addChild('updatedAt', date('Y-m-d'));
                    $newEntity->addChild('steps');
                    break;

                case 'steps':
                    // Find the parent topic
                    $found = false;
                    foreach ($xml->topics->topic as $topic) {
                        if ((int)$topic['id'] === (int)$data->idTopic) {
                            if (!isset($topic->steps)) {
                                $topic->addChild('steps');
                            }

                            $newEntity = $topic->steps->addChild('step');
                            $newEntity->addAttribute('id', $newId);
                            $newEntity->addAttribute('idTopic', $data->idTopic);
                            $newEntity->addChild('title', htmlspecialchars($data->title));
                            $newEntity->addChild('description', htmlspecialchars($data->description));
                            $newEntity->addChild('updatedAt', date('Y-m-d'));
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        return response('<error>Parent topic not found</error>', 404)
                            ->header('Content-Type', 'application/xml');
                    }
                    break;

                case 'votes':
                    $newEntity = $xml->votes->addChild('vote');
                    $newEntity->addAttribute('idTopic', $data->idTopic);
                    $newEntity->addAttribute('idAccount', $data->idAccount);
                    $newEntity->addChild('isPositive', $data->isPositive ? 'true' : 'false');
                    break;

                default:
                    return response("<error>Unknown entity type: $entityType</error>", 400)
                        ->header('Content-Type', 'application/xml');
            }

            // Save the updated XML back to the database
            DB::table('dbxml')->where('id', 1)->update([
                'info' => $xml->asXML(),
                'updated_at' => now()
            ]);

            // Create response XML
            $response = new \SimpleXMLElement("<$entityType></$entityType>");

            // Format depends on entity type
            switch ($entityType) {
                case 'accounts':
                    $entity = $response->addChild('account');
                    $entity->addAttribute('id', $newId);
                    $entity->addChild('userName', $data->userName);
                    $entity->addChild('password', $data->password);
                    $entity->addChild('email', $data->email);
                    break;

                    // Add cases for other entity types...
            }

            return response($response->asXML(), 201)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }

        // Retrieve the XML from the database
        // $xmlData = DB::table('dbxml')->where('id', 1)->value('info');
        // if (!$xmlData) {
        //     return response('<error>XML not found</error>', 404)
        //         ->header('Content-Type', 'application/xml');
        // }
        // $root = new \SimpleXMLElement($xmlData);

        // // Find the parent node (accounts for example)
        // $parentNode = $root->xpath("//$parent")[0] ?? null;
        // if (!$parentNode) {
        //     return response('<error>Parent node not found</error>', 404)
        //         ->header('Content-Type', 'application/xml');
        // }

        // // Generate a new ID for the account
        // $newId = count($parentNode->account) + 1;

        // // Add the new account
        // switch ($parent) {
        //     case 'accounts':
        //         $newAccountNode = $parentNode->addChild('account');
        //         $newAccountNode->addAttribute('id', $newId);
        //         $newAccountNode->addChild('userName', $data->userName);
        //         $newAccountNode->addChild('password', $data->password);
        //         $newAccountNode->addChild('email', $data->email);
        //         break;
        // }

        // // Save the updated XML back to the database
        // $updatedXml = $root->asXML();
        // DB::table('dbxml')->where('id', 1)->update(['info' => $updatedXml]);

        // return response($newAccountNode->asXML(), 201)
        //     ->header('Content-Type', 'application/xml');
    }

    // * Updating a specific thing */
    public static function update($parent, $id, $data)
    {
        // Retrieve the XML from the database
        $xmlData = DB::table('dbxml')->where('id', 1)->value('info');
        if (!$xmlData) {
            return response('<error>XML not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }
        $root = new \SimpleXMLElement($xmlData);

        // Find the parent node (account)
        $parentNode = $root->xpath("//{$parent}[@id='$id']")[0] ?? null;
        if (!$parentNode) {
            return response('<error>Parent node not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }

        // Update the account
        switch ($parent) {
            case 'account':
                if (isset($data->userName))
                    $parentNode->userName = $data->userName;
                if (isset($data->password))
                    $parentNode->password = $data->password;
                if (isset($data->email))
                    $parentNode->email = $data->email;
                break;
            case 'userProfile':
                if (isset($data->firstName))
                    $parentNode->firstName = $data->firstName;
                if (isset($data->lastName))
                    $parentNode->lastName = $data->lastName;
                if (isset($data->profilePicture))
                    $parentNode->profilePicture = $data->profilePicture;
                if (isset($data->bio))
                    $parentNode->bio = $data->bio;
                break;
            case 'topic':
                if (isset($data->problem))
                    $parentNode->problem = $data->problem;
                if (isset($data->description))
                    $parentNode->description = $data->description;
                if (isset($data->steps->step)) {
                    unset($parentNode->steps);
                    $parentNode->addChild('steps');
                    foreach ($data->steps->step as $index => $step) {
                        $stepId = count($parentNode->step) + (int)$index;
                        $newStepNode = $parentNode->steps->addChild('step');
                        $newStepNode->addAttribute('id', $stepId);
                        $newStepNode->addAttribute('idTopic', $id);
                        if (isset($step->title))
                            $newStepNode->title = $step->title;
                        if (isset($step->description))
                            $newStepNode->description = $step->description;
                        if (isset($step->updatedAt))
                            $newStepNode->updatedAt = date('Y-m-d');
                    }
                }
                break;
            case 'vote':
                //(!Important)
                //The only thing editable in the vote is the text inside the isPositive tag
                if (isset($data->isPositive)) {
                    $parentNode->isPositive = $data->isPositive;
                }
                break;
        }

        // Save the updated XML back to the database
        $updatedXml = $root->asXML();
        DB::table('dbxml')->where('id', 1)->update(['info' => $updatedXml]);

        return response($parentNode->asXML(), 200)
            ->header('Content-Type', 'application/xml');
    }

    // * Deleting a specific thing */
    public static function destroy($parent, $id, ?int $idAccount = null)
    {
        // Retrieve the XML from the database
        $xmlData = DB::table('dbxml')->where('id', 1)->value('info');
        if (!$xmlData) {
            return response('<error>XML not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }
        $root = new \SimpleXMLElement($xmlData);

        // Find the parent node (account for example)
        if ($idAccount) {
            $parent = $parent . "[(@idTopic=$id and @idAccount=$idAccount)]";
        } else {
            $parent = $parent . "[@id=$id]";
        }
        $parentNode = $root->xpath("//{$parent}")[0] ?? null;
        if (!$parentNode) {
            return response('<error>Parent node not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }

        // Delete the account
        $dom = dom_import_simplexml($parentNode);
        $dom->parentNode->removeChild($dom);

        // Save the updated XML back to the database
        $updatedXml = $root->asXML();
        DB::table('dbxml')->where('id', 1)->update(['info' => $updatedXml]);

        return response('<success>Deleted successfully</success>', 200)
            ->header('Content-Type', 'application/xml');
    }
}
