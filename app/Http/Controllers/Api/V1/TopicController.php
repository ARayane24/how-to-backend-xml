<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreTopicRequest;
use App\Http\Requests\UpdateTopicRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TopicCollection;
use Illuminate\Support\Facades\Log;
use App\Utils\DB_Utils;
use App\Http\Resources\V1\TopicResource;
use App\Models\Topic;
use Exception;

class TopicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $blocks = DB_Utils::getXmlBlocks("//topics");
        Log::info(print_r($blocks, true));
        if (!$blocks) {
            return response('<error>No topic found</error>', 404)
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
    public function store( )
    {
        $newTopic = request()->getContent();
        if (!$newTopic) {
            return response('<error>No data provided</error>', 400)
                ->header('Content-Type', 'application/xml');
        }

        try {
            $xml = new \SimpleXMLElement($newTopic);

            if (!isset($xml->problem) || !isset($xml->description)) {
                return response('<error>Missing required fields</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            $blocks = DB_Utils::getXmlBlocks("//topic");
            $newId = count($blocks);
            $accountId = $xml['idAccount'];

            $topic = "<topic id=\"$newId\" idAccount=\"$accountId\">\n" .
                "    <problem>{$xml->problem}</problem>\n" .
                "    <description>{$xml->description}</description>\n" .
                "    <createdAt>" . date('Y-m-d') . "</createdAt>\n" .
                "    <updatedAt>" . date('Y-m-d') . "</updatedAt>\n" .
                "    <steps>\n";

            if (isset($xml->steps->step)) {
                foreach ($xml->steps->step as $index => $step) {
                    $stepId = count(DB_Utils::getXmlBlocks("//step")) + (int)$index;
                    $topic .= "        <step id=\"$stepId\" idTopic=\"$newId\">\n" .
                        "            <title>{$step->title}</title>\n" .
                        "            <description>{$step->description}</description>\n" .
                        "            <updatedAt>" . date('Y-m-d') . "</updatedAt>\n" .
                        "        </step>\n";
                }
            }
            
            $topic .= "    </steps>\n</topic>";

            if (DB_Utils::addBlock('/db/topics', $topic)) {
                return response($topic, 201)
                    ->header('Content-Type', 'application/xml');
            }

            return response("<error>Could not create the new topic</error>", 500)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response('<error>Invalid XML format</error>', 400)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
         $account = DB_Utils::getXmlBlocks("//topic[@id='$id']")[0] ?? null;

            if (!$account) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }

            return response($account, 200)
                ->header('Content-Type', 'application/xml');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Topic $topic)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id)
    {
        try {
            $topicXmlString = DB_Utils::getXmlBlocks("//topic[@id='$id']")[0] ?? null;
            if (!$topicXmlString) {
                return response('<error>Topic not found</error>', 404)
                    ->header('Content-Type', 'application/xml');
            }
            $oldXml = new \SimpleXMLElement($topicXmlString);
            log::debug($topicXmlString);
            log::debug($oldXml);
            $updateData = request()->getContent();
            log::debug($updateData);
            if (empty($updateData)) {
                return response('<error>No update data provided</error>', 400)
                    ->header('Content-Type', 'application/xml');
            }

            try {
                $newXml = new \SimpleXMLElement($updateData);

                if (isset($newXml->problem)) {
                    $oldXml->problem = (string) $newXml->problem;
                }
                if (isset($newXml->description)) {
                    $oldXml->description = (string) $newXml->description;
                }
                
                if (isset($newXml->steps->step)) {

                    unset($oldXml->steps);
                    $oldXml->addChild('steps');
                    
                    foreach ($newXml->steps->step as $step) {
                        $newStep = $oldXml->steps->addChild('step');
                        $newStep->addAttribute('id', (string) $step['id']);
                        $newStep->addAttribute('idTopic', $id);
                        $newStep->addChild('title', (string) $step->title);
                        $newStep->addChild('description', (string) $step->description);
                        $newStep->addChild('updatedAt', date('Y-m-d'));
                    }
                }

                $oldXml->updatedAt = date('Y-m-d');

                $updatedTopic = $oldXml->asXML();
                $updatedTopic = preg_replace('/<\?xml.*?\?>\s*/', '', $updatedTopic);

                DB_Utils::editBlock("//topic[@id='$id']", $updatedTopic);
                    return response($updatedTopic, 200)
                        ->header('Content-Type', 'application/xml');


        } catch (\Exception $e) {
            Log::error('Invalid XML format: ' . $e->getMessage());
            return response('<error>Invalid XML format in update data</error>', 400)
                ->header('Content-Type', 'application/xml');
        }

        } catch (\Exception $e) {
            Log::error('Update process failed: ' . $e->getMessage());
            return response('<error>Failed to process update request</error>', 500)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
public function destroy(int $id)
{
    $topic = DB_Utils::getXmlBlocks("//topic[@id='$id']")[0] ?? null;

    if (!$topic) {
        return response('<error>Topic not found</error>', 404)
            ->header('Content-Type', 'application/xml');
    }
    try {
        DB_Utils::removeBlock("//topic[@id='$id']");
        return response('<success>Topic deleted successfully</success>', 200)
            ->header('Content-Type', 'application/xml');
    } catch (\Exception $e) {
        return response('<error>Failed to delete topic</error>', 500)
            ->header('Content-Type', 'application/xml');
    }
}
}
