<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AccountCollection;
use App\Http\Resources\V1\AccountResource;
use App\Utils\DB_Utils;

class AccountController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blocks = DB_Utils::getXmlBlocks("//account");

        if (empty($blocks)) {
            return response('<error>No accounts found</error>', 404)
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
     * Display the specified resource.
     */
    public function show(int $id)
    {
        //
    }
}
