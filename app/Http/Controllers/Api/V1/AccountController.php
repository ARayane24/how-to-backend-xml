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
// use Illuminate\Http\Response;

class AccountController extends Controller
{
    /**
     * Display all the accounts 
     */
    public function index()
    {
        $blocks = DB_Utils::getXmlBlocks("//account");

        if (!$blocks) {
            return response('<error>No accounts found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }

        return response(implode("\n", $blocks), 200)
            ->header('Content-Type', 'application/xml');
    }


    /**
     * Add a new Account 
     */
    public function store()
    {
        $newAccount = request()->getContent();

        if (!$newAccount) {
            return response('<error>No data provided</error>', 400)
                ->header('Content-Type', 'application/xml');
        }

        try {
            $xml = new \SimpleXMLElement($newAccount);

            if (!isset($xml->userName) || !isset($xml->password) || !isset($xml->email)) {
                return response('<error>Missing required fields</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            $blocks = DB_Utils::getXmlBlocks("//account");
            $newId = count($blocks) + 1;

            $newAccount = "<account id=\"$newId\">\n" .
                "    <userName>{$xml->userName}</userName>\n" .
                "    <password>{$xml->password}</password>\n" .
                "    <email>{$xml->email}</email>\n" .
                "</account>";

            if (DB_Utils::addBlock('/db/accounts', $newAccount)) {
                return response($newAccount, 201)
                    ->header('Content-Type', 'application/xml');
            }

            return response("<error>Could not create the new account</error>", 500)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>Invalid XML format</error>', 400)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display a specified account 
     */
    public function show(int $id)
    {
        try {
            $account = DB_Utils::getXmlBlocks("//account[@id='$id']")[0] ?? null;

            if (!$account) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            return response($account, 200)
                ->header('Content-Type', 'application/xml');
        } catch (NotFoundHttpException $e) {
            return response('<error>Account not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }
    }

    public function update(int $id)
    {
        try {
            $account = DB_Utils::getXmlBlocks("//account[@id='$id']")[0] ?? null;
            if (!$account) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            $oldXml = new \SimpleXMLElement($account);

            $updatedAccount = request()->getContent();
            if (empty($updatedAccount)) {
                return response('<error>No update data provided</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            try {
                $newXml = new \SimpleXMLElement($updatedAccount);
                if (isset($newXml->userName)) {
                    $oldXml->userName = (string) $newXml->userName;
                }

                if (isset($newXml->password)) {
                    $oldXml->password = (string) $newXml->password;
                }

                if (isset($newXml->email)) {
                    $oldXml->email = (string) $newXml->email;
                }

                $updatedAccount = "<account id=\"$id\">\n" .
                    "    <userName>{$oldXml->userName}</userName>\n" .
                    "    <password>{$oldXml->password}</password>\n" .
                    "    <email>{$oldXml->email}</email>\n" .
                    "</account>";

                DB_Utils::editBlock("//account[@id='$id']", $updatedAccount);
                return response($updatedAccount, 200)
                    ->header('Content-Type', 'application/xml');
            } catch (\Exception $e) {
                return response('<error>Invalid XML format in update data</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

        } catch (\Exception $e) {
            return response('<error>Failed to process update request</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }
    public function destroy(int $id)
    {
        try {
            $accountExists = DB_Utils::getXmlBlocks("//account[@id='$id']")[0] ?? null;

            if (!$accountExists) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            DB_Utils::removeBlock("//account[@id='$id']");

            return response(null, 204)
                ->header('Content-Type', 'application/xml');

        } catch (\Exception $e) {
            error_log("Error deleting account $id: " . $e->getMessage());
            return response('<error>Failed to process delete request</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }
}