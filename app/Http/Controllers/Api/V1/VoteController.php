<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreVoteRequest;
use App\Http\Requests\UpdateVoteRequest;
use App\Models\Vote;
use App\Utils\DB_Utils;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blocks = DB_Utils::getXmlBlocks("//votes");

        if (!$blocks) {
            return response('<error>No votes found</error>', 404)
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
    public function store(StoreVoteRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id, ?int $idAccount = null)
    {
        try {
            if ($idAccount) {
                $voteXml = DB_Utils::getXmlBlocks("//vote[(@idTopic='$id' and @idAccount='$idAccount')]")[0] ?? null;

                if ($voteXml === null) {
                    return response('<error>Vote not found</error>', 404)
                        ->header('Content-Type', 'application/xml');
                }

                $response = "<votes>" . PHP_EOL;
                $response .= $voteXml;
                $response .= PHP_EOL . "</votes>";

                return response($response, 200)
                    ->header('Content-Type', 'application/xml');
            } else {

                $voteXml = DB_Utils::getXmlBlocks("//vote[@idTopic='$id']");
                log::debug($voteXml);
                if ($voteXml === null) {
                    return response('<error>Vote not found</error>', 404)
                        ->header('Content-Type', 'application/xml');
                }

                $response = "<votes>" . PHP_EOL;
                $response .= implode(PHP_EOL, $voteXml);
                $response .= PHP_EOL . "</votes>";

                return response($response, 200)
                    ->header('Content-Type', 'application/xml');
            }
        } catch (NotFoundHttpException $e) {
            return response('<error>Vote not found</error>', 404)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vote $vote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id, int $idAccount)
    {
        try {
            $vote = DB_Utils::getXmlBlocks("//vote[(@idTopic='$id' and @idAccount='$idAccount')]")[0] ?? null;
            if (!$vote) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            $oldXml = new \SimpleXMLElement($vote);

            $updatedVote = request()->getContent();

            if (empty($updatedVote)) {
                return response('<error>No update data provided</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            try {
                $newXml = new \SimpleXMLElement($updatedVote);
                if (isset($newXml->isPositive)) {
                    $oldXml->isPositive = (string) $newXml->isPositive;
                }
                $updatedVote = "<vote idTopic=\"$id\" idAccount=\"$idAccount\">\n" .
                    "    <isPositive>{$oldXml->isPositive}</isPositive>\n" .
                    "</vote>";

                DB_Utils::editBlock("//vote[(@idTopic='$id' and @idAccount='$idAccount')]", $updatedVote);
                return response($updatedVote, 200)
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id, int $idAccount)
    {
        try {
            $voteXml = DB_Utils::getXmlBlocks("//vote[(@idTopic='$id' and @idAccount='$idAccount')]")[0] ?? null;
            log::debug($voteXml);
            if (!$voteXml) {
                return response('<error>Vote not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            DB_Utils::removeBlock("//vote[(@idTopic='$id' and @idAccount='$idAccount')]");

            return response(204)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            error_log("Error deleting Vote $id: " . $e->getMessage());
            return response('<error>Failed to process delete request</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }
}
