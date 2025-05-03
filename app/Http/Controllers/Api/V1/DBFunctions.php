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
        $xml = DB::table('dbxml')
            ->selectRaw("xpath('//$path',info::xml) AS xml")
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

    public static function store($parent, $data, ?int $parentId = null)
    {

        // Retrieve the XML from the database
        $xmlData = DB::table('dbxml')->where('id', 1)->value('info');
        if (!$xmlData) {
            return response('<error>XML not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }
        $root = new \SimpleXMLElement($xmlData);

        // Find the parent node (accounts for example)
        $parentNode = $root->xpath("//$parent")[0] ?? null;
        if (!$parentNode) {
            return response('<error>Parent node not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }

        // Generate a new ID for the account
        $newId = count($parentNode->account) + 1;

        // Add the new account
        switch ($parent) {
            case 'accounts':
                $newId = count($parentNode->account) + 1;
                $newAccountNode = $parentNode->addChild('account');
                $newAccountNode->addAttribute('id', $newId);
                $newAccountNode->addChild('userName', $data->userName);
                $newAccountNode->addChild('password', $data->password);
                $newAccountNode->addChild('email', $data->email);
                break;
            case 'userProfile':
                $newId = count($parentNode->userProfile) + 1;
                $parentId = $root->xpath("//account[@id='$parentId']")[0] ?? null;
                $newProfileNode = $parentNode->addChild('userProfile');
                $newProfileNode->addAttribute('id', $newId);
                $newProfileNode->addAttribute('idAccount', $parentId);
                $newProfileNode->addChild('firstName', $data->firstName);
                $newProfileNode->addChild('lastName', $data->lastName);
                $newProfileNode->addChild('profilePicture', $data->profilePicture);
                $newProfileNode->addChild('bio', $data->bio);
                $newProfileNode->addChild('joined', date('Y-m-d'));
                break;
            case 'topic':
                $newId = count($parentNode->topic) + 1;
                $newTopicNode = $parentNode->addChild('topic');
                $newTopicNode->addAttribute('id', $newId);
                $newTopicNode->addAttribute('idAccount', $parentId);
                $newTopicNode->addChild('problem', $data->problem);
                $newTopicNode->addChild('description', $data->description);
                $newTopicNode->addChild('createdAt', date('Y-m-d'));
                $newTopicNode->addChild('updatedAt', date('Y-m-d'));
                $newTopicNode->addChild('steps');
                if (isset($data->steps->step)) {
                    foreach ($data->steps->step as $index => $step) {
                        $stepId = count($parentNode->step) + (int)$index;
                        $newStepNode = $newTopicNode->steps->addChild('step');
                        $newStepNode->addAttribute('id', $stepId);
                        $newStepNode->addAttribute('idTopic', $newId);
                        $newStepNode->addChild('title', $step->title);
                        $newStepNode->addChild('description', $step->description);
                        $newStepNode->addChild('updatedAt', date('Y-m-d'));
                    }
                }
                break;
            case 'vote': 
                //(!Important)
                //Probably needs to change depending on the actual structure of the vote and what is sent
                $parentId = $root->xpath("//topic[@id='$parentId']")[0] ?? null;
                $newVoteNode = $parentNode->addChild('vote');
                $newVoteNode->addAttribute('idTopic', $parentId);
                $newVoteNode->addAttribute('idAccount', $data->idAccount);
                $newVoteNode->addChild('isPositive', $data->isPositive);
                break;
        }

        // Save the updated XML back to the database
        $updatedXml = $root->asXML();
        DB::table('dbxml')->where('id', 1)->update(['info' => $updatedXml]);

        return response($newAccountNode->asXML(), 201)
            ->header('Content-Type', 'application/xml');
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
