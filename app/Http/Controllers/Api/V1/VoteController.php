<?php

// namespace App\Http\Controllers\Api\V1;

// use App\Http\Controllers\Controller;

// use Illuminate\Support\Facades\Log;
// use App\Http\Requests\StoreVoteRequest;
// use App\Http\Requests\UpdateVoteRequest;
// use App\Models\Vote;
// use App\Utils\DB_Utils;
// use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// class VoteController extends Controller
// {
//     /**
//      * Display a listing of the resource.
//      */
//     public function index()
//     {
//         $blocks = DB_Utils::getXmlBlocks("//votes");

//         if (!$blocks) {
//             return response('<error>No votes found</error>', 404)
//                 ->header('Content-Type', 'application/xml');
//         }

//         return response(implode("\n", $blocks), 200)
//             ->header('Content-Type', 'application/xml');
//     }


//     /**
//      * Show the form for creating a new resource.
//      */
//     public function create()
//     {
//         //
//     }

//     /**
//      * Store a newly created resource in storage.
//      */
//     public function store(StoreVoteRequest $request)
//     {
//         //
//     }

//     /**
//      * Display the specified resource.
//      */
//     public function show(int $id, ?int $idAccount = null)
//     {
//         try {
//             if ($idAccount) {
//                 $voteXml = DB_Utils::getXmlBlocks("//vote[(@idTopic='$id' and @idAccount='$idAccount')]")[0] ?? null;

//                 if ($voteXml === null) {
//                     return response('<error>Vote not found</error>', 404)
//                         ->header('Content-Type', 'application/xml');
//                 }

//                 $response = "<votes>" . PHP_EOL;
//                 $response .= $voteXml;
//                 $response .= PHP_EOL . "</votes>";

//                 return response($response, 200)
//                     ->header('Content-Type', 'application/xml');
//             } else {

//                 $voteXml = DB_Utils::getXmlBlocks("//vote[@idTopic='$id']");
//                 log::debug($voteXml);
//                 if ($voteXml === null) {
//                     return response('<error>Vote not found</error>', 404)
//                         ->header('Content-Type', 'application/xml');
//                 }

//                 $response = "<votes>" . PHP_EOL;
//                 $response .= implode(PHP_EOL, $voteXml);
//                 $response .= PHP_EOL . "</votes>";

//                 return response($response, 200)
//                     ->header('Content-Type', 'application/xml');
//             }
//         } catch (NotFoundHttpException $e) {
//             return response('<error>Vote not found</error>', 404)
//                 ->header('Content-Type', 'application/xml');
//         }
//     }

//     /**
//      * Show the form for editing the specified resource.
//      */
//     public function edit(Vote $vote)
//     {
//         //
//     }

//     /**
//      * Update the specified resource in storage.
//      */
//     public function update(int $id, int $idAccount)
//     {
//         try {
//             $vote = DB_Utils::getXmlBlocks("//vote[(@idTopic='$id' and @idAccount='$idAccount')]")[0] ?? null;
//             if (!$vote) {
//                 return response('<error>Account not found</error>', 404)
//                     ->header('Content-Type', 'application/xml');
//             }

//             $oldXml = new \SimpleXMLElement($vote);

//             $updatedVote = request()->getContent();

//             if (empty($updatedVote)) {
//                 return response('<error>No update data provided</error>', 400)
//                     ->header('Content-Type', 'application/xml');
//             }

//             try {
//                 $newXml = new \SimpleXMLElement($updatedVote);
//                 if (isset($newXml->isPositive)) {
//                     $oldXml->isPositive = (string) $newXml->isPositive;
//                 }
//                 $updatedVote = "<vote idTopic=\"$id\" idAccount=\"$idAccount\">\n" .
//                     "    <isPositive>{$oldXml->isPositive}</isPositive>\n" .
//                     "</vote>";

//                 DB_Utils::editBlock("//vote[(@idTopic='$id' and @idAccount='$idAccount')]", $updatedVote);
//                 return response($updatedVote, 200)
//                     ->header('Content-Type', 'application/xml');
//             } catch (\Exception $e) {
//                 return response('<error>Invalid XML format in update data</error>', 400)
//                     ->header('Content-Type', 'application/xml');
//             }
//         } catch (\Exception $e) {
//             return response('<error>Failed to process update request</error>', 500)
//                 ->header('Content-Type', 'application/xml');
//         }
//     }

//     /**
//      * Remove the specified resource from storage.
//      */
//     public function destroy(int $id, int $idAccount)
//     {
//         try {
//             $voteXml = DB_Utils::getXmlBlocks("//vote[(@idTopic='$id' and @idAccount='$idAccount')]")[0] ?? null;
//             log::debug($voteXml);
//             if (!$voteXml) {
//                 return response('<error>Vote not found</error>', 404)
//                     ->header('Content-Type', 'application/xml');
//             }

//             DB_Utils::removeBlock("//vote[(@idTopic='$id' and @idAccount='$idAccount')]");

//             return response(204)
//                 ->header('Content-Type', 'application/xml');
//         } catch (\Exception $e) {
//             error_log("Error deleting Vote $id: " . $e->getMessage());
//             return response('<error>Failed to process delete request</error>', 500)
//                 ->header('Content-Type', 'application/xml');
//         }
//     }
// }

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vote;
use App\Models\Topic;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleXMLElement;

class VoteController extends Controller
{
    /**
     * Cast or update a vote on a topic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $topicId
     * @return \Illuminate\Http\Response
     */
    public function vote(Request $request, $topicId)
    {
        try {
            // Find the topic
            $topic = Topic::find($topicId);

            if (!$topic) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Parse XML input
            $xmlInput = simplexml_load_string($request->getContent());

            // Get account ID from XML or authenticated user
            $accountId = null;
            if (isset($xmlInput['idAccount'])) {
                $accountId = (int)$xmlInput['idAccount'];
                // Verify account exists
                $account = Account::find($accountId);
                if (!$account) {
                    return response('<error>Account not found</error>', 404)
                        ->header('Content-Type', 'application/xml');
                }
            } else if (Auth::check()) {
                $accountId = Auth::id();
            } else {
                return response('<error>Account ID is required</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            // Check if isPositive value is provided
            if (!isset($xmlInput->isPositive)) {
                return response('<error>Vote value (isPositive) is required</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            $isPositive = ((string)$xmlInput->isPositive === 'true');

            // Check if user has already voted on this topic
            $existingVote = Vote::where('topic_id', $topicId)
                ->where('account_id', $accountId)
                ->first();

            if ($existingVote) {
                // Update existing vote
                $existingVote->is_positive = $isPositive;
                $existingVote->save();
                $message = "Vote updated";
                $vote = $existingVote;
            } else {
                // Create new vote
                $vote = new Vote();
                $vote->topic_id = $topicId;
                $vote->account_id = $accountId;
                $vote->is_positive = $isPositive;
                $vote->save();
                $message = "Vote recorded";
            }

            // Get updated vote counts
            $upvotes = Vote::where('topic_id', $topicId)->where('is_positive', true)->count();
            $downvotes = Vote::where('topic_id', $topicId)->where('is_positive', false)->count();

            // Create XML response
            $xml = new SimpleXMLElement('<response></response>');
            $xml->addChild('message', $message);
            $xml->addChild('status', 'success');

            $voteNode = $xml->addChild('vote');
            $voteNode->addAttribute('id', $vote->id);
            $voteNode->addAttribute('idTopic', $vote->topic_id);
            $voteNode->addAttribute('idAccount', $vote->account_id);
            $voteNode->addChild('isPositive', $vote->is_positive ? 'true' : 'false');

            $countsNode = $xml->addChild('voteCounts');
            $countsNode->addChild('upVotes', $upvotes);
            $countsNode->addChild('downVotes', $downvotes);

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Get all votes for a specific topic.
     *
     * @param  int  $topicId
     * @return \Illuminate\Http\Response
     */
    public function getVotesForTopic($topicId)
    {
        try {
            // Find the topic
            $topic = Topic::find($topicId);

            if (!$topic) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Get votes for this topic with account information
            $votes = Vote::with('account')->where('topic_id', $topicId)->get();

            // Count up and down votes
            $upvotes = $votes->where('is_positive', true)->count();
            $downvotes = $votes->where('is_positive', false)->count();

            // Create XML response
            $xml = new SimpleXMLElement('<votes></votes>');
            $xml->addAttribute('topicId', $topicId);

            $countsNode = $xml->addChild('voteCounts');
            $countsNode->addChild('upVotes', $upvotes);
            $countsNode->addChild('downVotes', $downvotes);
            $countsNode->addChild('total', count($votes));

            foreach ($votes as $vote) {
                $voteNode = $xml->addChild('vote');
                $voteNode->addAttribute('id', $vote->id);
                $voteNode->addAttribute('idTopic', $vote->topic_id);
                $voteNode->addAttribute('idAccount', $vote->account_id);
                $voteNode->addChild('isPositive', $vote->is_positive ? 'true' : 'false');
                $voteNode->addChild('createdAt', $vote->created_at->format('Y-m-d H:i:s'));

                if ($vote->account) {
                    $accountNode = $voteNode->addChild('account');
                    $accountNode->addAttribute('id', $vote->account->id);
                    $accountNode->addChild('userName', $vote->account->username);
                }
            }

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Get votes cast by a specific account.
     *
     * @param  int  $accountId
     * @return \Illuminate\Http\Response
     */
    public function getVotesByAccount($accountId)
    {
        try {
            // Verify account exists
            $account = Account::find($accountId);

            if (!$account) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Get votes by this account with topic information
            $votes = Vote::with('topic')->where('account_id', $accountId)->get();

            // Create XML response
            $xml = new SimpleXMLElement('<votes></votes>');
            $xml->addAttribute('accountId', $accountId);
            $xml->addAttribute('count', count($votes));

            foreach ($votes as $vote) {
                $voteNode = $xml->addChild('vote');
                $voteNode->addAttribute('id', $vote->id);
                $voteNode->addAttribute('idTopic', $vote->topic_id);
                $voteNode->addAttribute('idAccount', $vote->account_id);
                $voteNode->addChild('isPositive', $vote->is_positive ? 'true' : 'false');
                $voteNode->addChild('createdAt', $vote->created_at->format('Y-m-d H:i:s'));

                if ($vote->topic) {
                    $topicNode = $voteNode->addChild('topic');
                    $topicNode->addAttribute('id', $vote->topic->id);
                    $topicNode->addChild('problem', $vote->topic->problem);
                }
            }

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Delete a vote.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Find vote
            $vote = Vote::find($id);

            if (!$vote) {
                return response('<error>Vote not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Store topic ID for vote count update
            $topicId = $vote->topic_id;

            // Delete the vote
            $vote->delete();

            // Get updated vote counts
            $upvotes = Vote::where('topic_id', $topicId)->where('is_positive', true)->count();
            $downvotes = Vote::where('topic_id', $topicId)->where('is_positive', false)->count();

            // Create XML response
            $xml = new SimpleXMLElement('<response></response>');
            $xml->addChild('message', 'Vote deleted successfully');
            $xml->addChild('status', 'success');

            $countsNode = $xml->addChild('voteCounts');
            $countsNode->addChild('upVotes', $upvotes);
            $countsNode->addChild('downVotes', $downvotes);

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Check if an account has voted on a topic.
     *
     * @param  int  $topicId
     * @param  int  $accountId
     * @return \Illuminate\Http\Response
     */
    public function checkVote($topicId, $accountId)
    {
        try {
            // Find the topic
            $topic = Topic::find($topicId);

            if (!$topic) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Verify account exists
            $account = Account::find($accountId);

            if (!$account) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Check if vote exists
            $vote = Vote::where('topic_id', $topicId)
                ->where('account_id', $accountId)
                ->first();

            // Create XML response
            $xml = new SimpleXMLElement('<response></response>');

            if ($vote) {
                $xml->addChild('hasVoted', 'true');
                $voteNode = $xml->addChild('vote');
                $voteNode->addAttribute('id', $vote->id);
                $voteNode->addAttribute('idTopic', $vote->topic_id);
                $voteNode->addAttribute('idAccount', $vote->account_id);
                $voteNode->addChild('isPositive', $vote->is_positive ? 'true' : 'false');
                $voteNode->addChild('createdAt', $vote->created_at->format('Y-m-d H:i:s'));
            } else {
                $xml->addChild('hasVoted', 'false');
            }

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Get vote statistics for a topic.
     *
     * @param  int  $topicId
     * @return \Illuminate\Http\Response
     */
    public function getVoteStats($topicId)
    {
        try {
            // Find the topic
            $topic = Topic::find($topicId);

            if (!$topic) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Get vote counts
            $upvotes = Vote::where('topic_id', $topicId)->where('is_positive', true)->count();
            $downvotes = Vote::where('topic_id', $topicId)->where('is_positive', false)->count();
            $totalVotes = $upvotes + $downvotes;

            // Calculate percentages
            $upvotePercentage = $totalVotes > 0 ? round(($upvotes / $totalVotes) * 100, 1) : 0;
            $downvotePercentage = $totalVotes > 0 ? round(($downvotes / $totalVotes) * 100, 1) : 0;

            // Create XML response
            $xml = new SimpleXMLElement('<voteStats></voteStats>');
            $xml->addAttribute('topicId', $topicId);
            $xml->addChild('totalVotes', $totalVotes);
            $xml->addChild('upVotes', $upvotes);
            $xml->addChild('downVotes', $downvotes);
            $xml->addChild('upVotePercentage', $upvotePercentage);
            $xml->addChild('downVotePercentage', $downvotePercentage);

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }
}
