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

    public static function store($parent, $data)
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
                $newAccountNode = $parentNode->addChild('account');
                $newAccountNode->addAttribute('id', $newId);
                $newAccountNode->addChild('userName', $data->userName);
                $newAccountNode->addChild('password', $data->password);
                $newAccountNode->addChild('email', $data->email);
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
        }

        // Save the updated XML back to the database
        $updatedXml = $root->asXML();
        DB::table('dbxml')->where('id', 1)->update(['info' => $updatedXml]);

        return response($parentNode->asXML(), 200)
            ->header('Content-Type', 'application/xml');
    }

    // * Deleting a specific thing */
    public static function destroy($parent, $id)
    {
        // Retrieve the XML from the database
        $xmlData = DB::table('dbxml')->where('id', 1)->value('info');
        if (!$xmlData) {
            return response('<error>XML not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }
        $root = new \SimpleXMLElement($xmlData);

        // Find the parent node (accounts)
        $parentNode = $root->xpath("//{$parent}[@id='$id']")[0] ?? null;
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
