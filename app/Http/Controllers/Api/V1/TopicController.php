<?php

// namespace App\Http\Controllers\Api\V1;

// use App\Http\Requests\StoreTopicRequest;
// use App\Http\Requests\UpdateTopicRequest;
// use App\Http\Controllers\Controller;
// use App\Http\Resources\V1\TopicCollection;
// use Illuminate\Support\Facades\Log;
// use App\Utils\DB_Utils;
// use App\Http\Resources\V1\TopicResource;
// use App\Models\Topic;
// use Exception;

// class TopicController extends Controller
// {
//     /**
//      * Display a listing of the resource.
//      */
//     public function index()
//     {

//         $blocks = DB_Utils::getXmlBlocks("//topics");
//         Log::info(print_r($blocks, true));
//         if (!$blocks) {
//             return response('<error>No topic found</error>', 404)
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
//     public function store( )
//     {
//         $newTopic = request()->getContent();
//         if (!$newTopic) {
//             return response('<error>No data provided</error>', 400)
//                 ->header('Content-Type', 'application/xml');
//         }

//         try {
//             $xml = new \SimpleXMLElement($newTopic);

//             if (!isset($xml->problem) || !isset($xml->description)) {
//                 return response('<error>Missing required fields</error>', 400)
//                     ->header('Content-Type', 'application/xml');
//             }

//             $blocks = DB_Utils::getXmlBlocks("//topic");
//             $newId = count($blocks);
//             $accountId = $xml['idAccount'];

//             $topic = "<topic id=\"$newId\" idAccount=\"$accountId\">\n" .
//                 "    <problem>{$xml->problem}</problem>\n" .
//                 "    <description>{$xml->description}</description>\n" .
//                 "    <createdAt>" . date('Y-m-d') . "</createdAt>\n" .
//                 "    <updatedAt>" . date('Y-m-d') . "</updatedAt>\n" .
//                 "    <steps>\n";

//             if (isset($xml->steps->step)) {
//                 foreach ($xml->steps->step as $index => $step) {
//                     $stepId = count(DB_Utils::getXmlBlocks("//step")) + (int)$index;
//                     $topic .= "        <step id=\"$stepId\" idTopic=\"$newId\">\n" .
//                         "            <title>{$step->title}</title>\n" .
//                         "            <description>{$step->description}</description>\n" .
//                         "            <updatedAt>" . date('Y-m-d') . "</updatedAt>\n" .
//                         "        </step>\n";
//                 }
//             }

//             $topic .= "    </steps>\n</topic>";

//             if (DB_Utils::addBlock('/db/topics', $topic)) {
//                 return response($topic, 201)
//                     ->header('Content-Type', 'application/xml');
//             }

//             return response("<error>Could not create the new topic</error>", 500)
//                 ->header('Content-Type', 'application/xml');
//         } catch (\Exception $e) {
//             return response('<error>Invalid XML format</error>', 400)
//                 ->header('Content-Type', 'application/xml');
//         }
//     }

//     /**
//      * Display the specified resource.
//      */
//     public function show(int $id)
//     {
//          $account = DB_Utils::getXmlBlocks("//topic[@id='$id']")[0] ?? null;

//             if (!$account) {
//                 return response('<error>Topic not found</error>', 404)
//                     ->header('Content-Type', 'application/xml');
//             }

//             return response($account, 200)
//                 ->header('Content-Type', 'application/xml');
//     }

//     /**
//      * Show the form for editing the specified resource.
//      */
//     public function edit(Topic $topic)
//     {
//         //
//     }

//     /**
//      * Update the specified resource in storage.
//      */
//     public function update(int $id)
//     {
//         try {
//             $topicXmlString = DB_Utils::getXmlBlocks("//topic[@id='$id']")[0] ?? null;
//             if (!$topicXmlString) {
//                 return response('<error>Topic not found</error>', 404)
//                     ->header('Content-Type', 'application/xml');
//             }
//             $oldXml = new \SimpleXMLElement($topicXmlString);
//             log::debug($topicXmlString);
//             log::debug($oldXml);
//             $updateData = request()->getContent();
//             log::debug($updateData);
//             if (empty($updateData)) {
//                 return response('<error>No update data provided</error>', 400)
//                     ->header('Content-Type', 'application/xml');
//             }

//             try {
//                 $newXml = new \SimpleXMLElement($updateData);

//                 if (isset($newXml->problem)) {
//                     $oldXml->problem = (string) $newXml->problem;
//                 }
//                 if (isset($newXml->description)) {
//                     $oldXml->description = (string) $newXml->description;
//                 }

//                 if (isset($newXml->steps->step)) {

//                     unset($oldXml->steps);
//                     $oldXml->addChild('steps');

//                     foreach ($newXml->steps->step as $step) {
//                         $newStep = $oldXml->steps->addChild('step');
//                         $newStep->addAttribute('id', (string) $step['id']);
//                         $newStep->addAttribute('idTopic', $id);
//                         $newStep->addChild('title', (string) $step->title);
//                         $newStep->addChild('description', (string) $step->description);
//                         $newStep->addChild('updatedAt', date('Y-m-d'));
//                     }
//                 }

//                 $oldXml->updatedAt = date('Y-m-d');

//                 $updatedTopic = $oldXml->asXML();
//                 $updatedTopic = preg_replace('/<\?xml.*?\>\s*/', '', $updatedTopic);

//                 DB_Utils::editBlock("//topic[@id='$id']", $updatedTopic);
//                     return response($updatedTopic, 200)
//                         ->header('Content-Type', 'application/xml');


//         } catch (\Exception $e) {
//             Log::error('Invalid XML format: ' . $e->getMessage());
//             return response('<error>Invalid XML format in update data</error>', 400)
//                 ->header('Content-Type', 'application/xml');
//         }

//         } catch (\Exception $e) {
//             Log::error('Update process failed: ' . $e->getMessage());
//             return response('<error>Failed to process update request</error>', 500)
//                 ->header('Content-Type', 'application/xml');
//         }
//     }

//     /**
//      * Remove the specified resource from storage.
//      */
// public function destroy(int $id)
// {
//     $topic = DB_Utils::getXmlBlocks("//topic[@id='$id']")[0] ?? null;

//     if (!$topic) {
//         return response('<error>Topic not found</error>', 404)
//             ->header('Content-Type', 'application/xml');
//     }
//     try {
//         DB_Utils::removeBlock("//topic[@id='$id']");
//         return response('<success>Topic deleted successfully</success>', 200)
//             ->header('Content-Type', 'application/xml');
//     } catch (\Exception $e) {
//         return response('<error>Failed to delete topic</error>', 500)
//             ->header('Content-Type', 'application/xml');
//     }
// }
// }


namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\Step;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleXMLElement;

class TopicController extends Controller
{
    /**
     * Display a listing of the topics.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // Get all topics with account and vote count
            $topics = Topic::with('account')
                ->withCount(['votes as upvotes' => function ($query) {
                    $query->where('is_positive', true);
                }])
                ->withCount(['votes as downvotes' => function ($query) {
                    $query->where('is_positive', false);
                }])
                ->get();

            // Create XML response
            $xml = new SimpleXMLElement('<topics></topics>');

            foreach ($topics as $topic) {
                $topicNode = $xml->addChild('topic');
                $topicNode->addAttribute('id', $topic->id);
                $topicNode->addAttribute('idAccount', $topic->account_id);
                $topicNode->addChild('problem', $topic->problem);
                $topicNode->addChild('description', $topic->description ?? '');
                $topicNode->addChild('createdAt', $topic->created_at->format('Y-m-d H:i:s'));
                $topicNode->addChild('updatedAt', $topic->updated_at->format('Y-m-d H:i:s'));

                // Add votes
                $votesNode = $topicNode->addChild('votes');
                $votesNode->addChild('upVotes', $topic->upvotes);
                $votesNode->addChild('downVotes', $topic->downvotes);

                // Add account info
                if ($topic->account) {
                    $accountNode = $topicNode->addChild('account');
                    $accountNode->addAttribute('id', $topic->account->id);
                    $accountNode->addChild('userName', $topic->account->username);
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
     * Store a newly created topic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Parse XML input
            $xmlInput = simplexml_load_string($request->getContent());

            if (!isset($xmlInput->problem)) {
                return response('<error>Problem is required</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            // Get authenticated user if available, or use explicit account ID
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

            // Create topic
            $topic = new Topic();
            $topic->account_id = $accountId;
            $topic->problem = (string)$xmlInput->problem;
            $topic->description = isset($xmlInput->description) ? (string)$xmlInput->description : '';
            $topic->save();

            // Add steps if provided
            if (isset($xmlInput->steps) && isset($xmlInput->steps->step)) {
                foreach ($xmlInput->steps->step as $stepXml) {
                    if (!isset($stepXml->title)) {
                        continue; // Skip steps without titles
                    }

                    $step = new Step();
                    $step->topic_id = $topic->id;
                    $step->title = (string)$stepXml->title;
                    $step->description = isset($stepXml->description) ? (string)$stepXml->description : '';
                    $step->save();
                }
            }

            // Load the topic with account and steps
            $topic = Topic::with(['account', 'steps'])->find($topic->id);

            // Create XML response
            $xml = new SimpleXMLElement('<topic></topic>');
            $xml->addAttribute('id', $topic->id);
            $xml->addAttribute('idAccount', $topic->account_id);
            $xml->addChild('problem', $topic->problem);
            $xml->addChild('description', $topic->description ?? '');
            $xml->addChild('createdAt', $topic->created_at->format('Y-m-d H:i:s'));
            $xml->addChild('updatedAt', $topic->updated_at->format('Y-m-d H:i:s'));

            // Add account info
            if ($topic->account) {
                $accountNode = $xml->addChild('account');
                $accountNode->addAttribute('id', $topic->account->id);
                $accountNode->addChild('userName', $topic->account->username);
            }

            // Add steps
            if (count($topic->steps) > 0) {
                $stepsNode = $xml->addChild('steps');
                foreach ($topic->steps as $step) {
                    $stepNode = $stepsNode->addChild('step');
                    $stepNode->addAttribute('id', $step->id);
                    $stepNode->addChild('title', $step->title);
                    $stepNode->addChild('description', $step->description ?? '');
                    $stepNode->addChild('updatedAt', $step->updated_at->format('Y-m-d H:i:s'));
                }
            }

            return response($xml->asXML(), 201)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display the specified topic.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            // Find topic with account, steps, and vote counts
            $topic = Topic::with(['account', 'steps'])
                ->withCount(['votes as upvotes' => function ($query) {
                    $query->where('is_positive', true);
                }])
                ->withCount(['votes as downvotes' => function ($query) {
                    $query->where('is_positive', false);
                }])
                ->find($id);

            if (!$topic) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Create XML response
            $xml = new SimpleXMLElement('<topic></topic>');
            $xml->addAttribute('id', $topic->id);
            $xml->addAttribute('idAccount', $topic->account_id);
            $xml->addChild('problem', $topic->problem);
            $xml->addChild('description', $topic->description ?? '');
            $xml->addChild('createdAt', $topic->created_at->format('Y-m-d H:i:s'));
            $xml->addChild('updatedAt', $topic->updated_at->format('Y-m-d H:i:s'));

            // Add votes
            $votesNode = $xml->addChild('votes');
            $votesNode->addChild('upVotes', $topic->upvotes);
            $votesNode->addChild('downVotes', $topic->downvotes);

            // Add account info
            if ($topic->account) {
                $accountNode = $xml->addChild('account');
                $accountNode->addAttribute('id', $topic->account->id);
                $accountNode->addChild('userName', $topic->account->username);
            }

            // Add steps
            if (count($topic->steps) > 0) {
                $stepsNode = $xml->addChild('steps');
                foreach ($topic->steps as $step) {
                    $stepNode = $stepsNode->addChild('step');
                    $stepNode->addAttribute('id', $step->id);
                    $stepNode->addChild('title', $step->title);
                    $stepNode->addChild('description', $step->description ?? '');
                    $stepNode->addChild('updatedAt', $step->updated_at->format('Y-m-d H:i:s'));
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
     * Update the specified topic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the topic
            $topic = Topic::find($id);

            if (!$topic) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Parse XML input
            $xmlInput = simplexml_load_string($request->getContent());

            // Update topic fields
            if (isset($xmlInput->problem)) {
                $topic->problem = (string)$xmlInput->problem;
            }

            if (isset($xmlInput->description)) {
                $topic->description = (string)$xmlInput->description;
            }

            $topic->save();

            // Update steps if provided
            if (isset($xmlInput->steps) && isset($xmlInput->steps->step)) {
                $currentSteps = [];

                foreach ($xmlInput->steps->step as $stepXml) {
                    $stepId = isset($stepXml['id']) ? (int)$stepXml['id'] : null;

                    if ($stepId) {
                        // Update existing step
                        $step = Step::where('id', $stepId)->where('topic_id', $topic->id)->first();

                        if (!$step) {
                            continue; // Skip if step doesn't exist or doesn't belong to this topic
                        }

                        if (isset($stepXml->title)) {
                            $step->title = (string)$stepXml->title;
                        }

                        if (isset($stepXml->description)) {
                            $step->description = (string)$stepXml->description;
                        }

                        $step->save();
                        $currentSteps[] = $stepId;
                    } else {
                        // Add new step
                        if (!isset($stepXml->title)) {
                            continue; // Skip steps without titles
                        }

                        $step = new Step();
                        $step->topic_id = $topic->id;
                        $step->title = (string)$stepXml->title;
                        $step->description = isset($stepXml->description) ? (string)$stepXml->description : '';
                        $step->save();
                        $currentSteps[] = $step->id;
                    }
                }

                // Handle step deletion if preserveSteps is not true
                if (!isset($xmlInput['preserveSteps']) || (string)$xmlInput['preserveSteps'] !== 'true') {
                    Step::where('topic_id', $topic->id)
                        ->whereNotIn('id', $currentSteps)
                        ->delete();
                }
            }

            // Load updated topic with account and steps
            $topic = Topic::with(['account', 'steps'])
                ->withCount(['votes as upvotes' => function ($query) {
                    $query->where('is_positive', true);
                }])
                ->withCount(['votes as downvotes' => function ($query) {
                    $query->where('is_positive', false);
                }])
                ->find($topic->id);

            // Create XML response
            $xml = new SimpleXMLElement('<topic></topic>');
            $xml->addAttribute('id', $topic->id);
            $xml->addAttribute('idAccount', $topic->account_id);
            $xml->addChild('problem', $topic->problem);
            $xml->addChild('description', $topic->description ?? '');
            $xml->addChild('createdAt', $topic->created_at->format('Y-m-d H:i:s'));
            $xml->addChild('updatedAt', $topic->updated_at->format('Y-m-d H:i:s'));

            // Add votes
            $votesNode = $xml->addChild('votes');
            $votesNode->addChild('upVotes', $topic->upvotes);
            $votesNode->addChild('downVotes', $topic->downvotes);

            // Add account info
            if ($topic->account) {
                $accountNode = $xml->addChild('account');
                $accountNode->addAttribute('id', $topic->account->id);
                $accountNode->addChild('userName', $topic->account->username);
            }

            // Add steps
            if (count($topic->steps) > 0) {
                $stepsNode = $xml->addChild('steps');
                foreach ($topic->steps as $step) {
                    $stepNode = $stepsNode->addChild('step');
                    $stepNode->addAttribute('id', $step->id);
                    $stepNode->addChild('title', $step->title);
                    $stepNode->addChild('description', $step->description ?? '');
                    $stepNode->addChild('updatedAt', $step->updated_at->format('Y-m-d H:i:s'));
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
     * Remove the specified topic.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $topic = Topic::find($id);

            if (!$topic) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Due to cascading deletes, this will also delete steps and votes
            $topic->delete();

            return response('<success>Topic deleted successfully</success>', 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Get steps for a topic.
     *
     * @param  int  $topicId
     * @return \Illuminate\Http\Response
     */
    public function getSteps($topicId)
    {
        try {
            $topic = Topic::find($topicId);

            if (!$topic) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            $steps = Step::where('topic_id', $topicId)->orderBy('id')->get();

            // Create XML response
            $xml = new SimpleXMLElement('<steps></steps>');
            $xml->addAttribute('topicId', $topicId);

            foreach ($steps as $step) {
                $stepNode = $xml->addChild('step');
                $stepNode->addAttribute('id', $step->id);
                $stepNode->addChild('title', $step->title);
                $stepNode->addChild('description', $step->description ?? '');
                $stepNode->addChild('updatedAt', $step->updated_at->format('Y-m-d H:i:s'));
            }

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Add a step to a topic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $topicId
     * @return \Illuminate\Http\Response
     */
    public function addStep(Request $request, $topicId)
    {
        try {
            $topic = Topic::find($topicId);

            if (!$topic) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Parse XML input
            $xmlInput = simplexml_load_string($request->getContent());

            if (!isset($xmlInput->title)) {
                return response('<error>Step title is required</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            // Create step
            $step = new Step();
            $step->topic_id = $topicId;
            $step->title = (string)$xmlInput->title;
            $step->description = isset($xmlInput->description) ? (string)$xmlInput->description : '';
            $step->save();

            // Create XML response
            $xml = new SimpleXMLElement('<step></step>');
            $xml->addAttribute('id', $step->id);
            $xml->addAttribute('idTopic', $step->topic_id);
            $xml->addChild('title', $step->title);
            $xml->addChild('description', $step->description);
            $xml->addChild('updatedAt', $step->updated_at->format('Y-m-d H:i:s'));

            return response($xml->asXML(), 201)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Search topics by problem or description.
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

            // Search by problem or description
            $topics = Topic::with('account')
                ->withCount(['votes as upvotes' => function ($query) {
                    $query->where('is_positive', true);
                }])
                ->withCount(['votes as downvotes' => function ($query) {
                    $query->where('is_positive', false);
                }])
                ->where('problem', 'ILIKE', "%$query%")
                ->orWhere('description', 'ILIKE', "%$query%")
                ->get();

            // Create XML response
            $xml = new SimpleXMLElement('<topics></topics>');

            foreach ($topics as $topic) {
                $topicNode = $xml->addChild('topic');
                $topicNode->addAttribute('id', $topic->id);
                $topicNode->addAttribute('idAccount', $topic->account_id);
                $topicNode->addChild('problem', $topic->problem);
                $topicNode->addChild('description', $topic->description ?? '');
                $topicNode->addChild('createdAt', $topic->created_at->format('Y-m-d H:i:s'));
                $topicNode->addChild('updatedAt', $topic->updated_at->format('Y-m-d H:i:s'));

                // Add votes
                $votesNode = $topicNode->addChild('votes');
                $votesNode->addChild('upVotes', $topic->upvotes);
                $votesNode->addChild('downVotes', $topic->downvotes);

                // Add account info
                if ($topic->account) {
                    $accountNode = $topicNode->addChild('account');
                    $accountNode->addAttribute('id', $topic->account->id);
                    $accountNode->addChild('userName', $topic->account->username);
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
     * Get topics by account.
     *
     * @param  int  $accountId
     * @return \Illuminate\Http\Response
     */
    public function getByAccount($accountId)
    {
        try {
            // Check if account exists
            $account = Account::find($accountId);

            if (!$account) {
                return response('<error>Account not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            // Get topics for account
            $topics = Topic::with('account')
                ->withCount(['votes as upvotes' => function ($query) {
                    $query->where('is_positive', true);
                }])
                ->withCount(['votes as downvotes' => function ($query) {
                    $query->where('is_positive', false);
                }])
                ->where('account_id', $accountId)
                ->get();

            // Create XML response
            $xml = new SimpleXMLElement('<topics></topics>');

            foreach ($topics as $topic) {
                $topicNode = $xml->addChild('topic');
                $topicNode->addAttribute('id', $topic->id);
                $topicNode->addAttribute('idAccount', $topic->account_id);
                $topicNode->addChild('problem', $topic->problem);
                $topicNode->addChild('description', $topic->description ?? '');
                $topicNode->addChild('createdAt', $topic->created_at->format('Y-m-d H:i:s'));
                $topicNode->addChild('updatedAt', $topic->updated_at->format('Y-m-d H:i:s'));

                // Add votes
                $votesNode = $topicNode->addChild('votes');
                $votesNode->addChild('upVotes', $topic->upvotes);
                $votesNode->addChild('downVotes', $topic->downvotes);
            }

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>' . $e->getMessage() . '</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }
}
