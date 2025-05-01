<?php

namespace App\Utils;


class DB_Utils
{
    const FILE = "xml_as_db/db.xml";
    private static function LoadXml()
    {
        if (!file_exists(DB_Utils::FILE)) {
            throw new \Exception("File not found: " . DB_Utils::FILE);
        }

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->load(DB_Utils::FILE);

        $xpath = new \DOMXPath($doc);
        return [$doc, $xpath];
    }

    public static function getXmlBlocks($xpathQuery)
    {
        try {
            [$doc, $xpath] = DB_Utils::LoadXml();
            $nodes = $xpath->query($xpathQuery);
            if ($nodes === false) {
                throw new \Exception("Invalid XPath query: " . $xpathQuery);
            }

            if ($nodes->length === 0) {
                return [];
            }

            $blocks = [];
            foreach ($nodes as $node) {
                $blocks[] = $doc->saveXML($node);
                if ($blocks[count($blocks) - 1] === false) {
                    throw new \Exception("Failed to save XML for node");
                }
            }

            return $blocks;
        } catch (\Exception $e) {
            error_log("Error in getXmlBlocks: " . $e->getMessage());
            throw $e;
        }
    }
    public static function appendXmlBlock($xpathQuery, $xmlBlock)
    {

        try {
            $xmlFile = public_path('xml_as_db\db.xml');
            $doc = new \DOMDocument();
            $doc->preserveWhiteSpace = false; // Optional: Remove unnecessary whitespace
            $doc->formatOutput = true;     // Optional: Format the output XML nicely

            if (!$doc->load($xmlFile)) {
                throw new \Exception("Failed to load XML file: " . $xmlFile);
            }

            $xpath = new \DOMXPath($doc);
            $nodes = $xpath->query($xpathQuery);

            if ($nodes->length === 0) {
                error_log("No target node found for XPath query: " . $xpathQuery);
                return false;
            }

            // Assuming you want to append within the first matched node
            $parentNode = $nodes->item(0);

            $fragment = $doc->createDocumentFragment();
            if (!$fragment->appendXML($xmlBlock)) {
                throw new \Exception("Invalid XML block to add.");
            }

            $parentNode->appendChild($fragment);

            return $doc->save($xmlFile) !== false;
        } catch (\Exception $e) {
            error_log("Error appending XML block: " . $e->getMessage());
            return false;
        }
    }

    public static function editBlock($xpathQuery, $newXml)
    {
        [$doc, $xpath] = DB_Utils::loadXml();
        $nodes = $xpath->query($xpathQuery);

        if ($nodes->length === 0) {
            throw new \Exception("No nodes matched for editing.");
        }

        $nodeToReplace = $nodes->item(0);
        $parent = $nodeToReplace->parentNode;

        $fragment = $doc->createDocumentFragment();
        if (!$fragment->appendXML($newXml)) {
            throw new \Exception("Invalid replacement XML.");
        }

        $parent->replaceChild($fragment, $nodeToReplace);
        if ($doc->save(DB_Utils::FILE) === false) {
            throw new \Exception("Failed to save the XML file after removing nodes.");
        }
    }

    public static function removeBlock($xpathQuery)
    {
        [$doc, $xpath] = DB_Utils::loadXml();
        $nodes = $xpath->query($xpathQuery);

        if ($nodes === false) {
            throw new \Exception("Invalid XPath query: " . $xpathQuery);
        }

        if ($nodes->length === 0) {
            throw new \Exception("No nodes found matching the query to remove: " . $xpathQuery);
        }

        foreach ($nodes as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            } else {
                throw new \Exception("Cannot remove the root node or a node without a parent.");
            }
        }
        if ($doc->save(DB_Utils::FILE) === false) {
            throw new \Exception("Failed to save the XML file after removing nodes.");
        }
    }

    public static function addBlock($xpathQuery, $newXml, $position = 'append'): bool
    {
        [$doc, $xpath] = DB_Utils::loadXml();
        $nodes = $xpath->query($xpathQuery);

        error_log(print_r($nodes, TRUE));
        if ($nodes->length === 0) {
            throw new \Exception("No parent node found for adding block.");
        }

        $parent = $nodes->item(0);

        $fragment = $doc->createDocumentFragment();
        if (!$fragment->appendXML($newXml)) {
            throw new \Exception("Invalid XML block to add.");
        }

        if ($position === 'prepend' && $parent->hasChildNodes()) {
            $parent->insertBefore($fragment, $parent->firstChild);
        } else {
            $parent->appendChild($fragment);
        }

        return $doc->save(DB_Utils::FILE) !== false;
    }
}
