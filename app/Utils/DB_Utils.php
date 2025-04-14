<?php

namespace App\Utils;

class DB_Utils
{
    const FILE = "xml_as_db/db.xml";
    private static function loadXml()
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
        list($doc, $xpath) = DB_Utils::loadXml(DB_Utils::FILE);
        $nodes = $xpath->query($xpathQuery);

        if ($nodes->length === 0) {
            return [];
        }

        $blocks = [];
        foreach ($nodes as $node) {
            $blocks[] = $doc->saveXML($node);
        }

        return $blocks;
    }

    public static function editBlock($xpathQuery, $newXml)
    {
        list($doc, $xpath) = DB_Utils::loadXml(DB_Utils::FILE);
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
        $doc->save(DB_Utils::FILE);
    }

    public static function removeBlock($xpathQuery)
    {
        list($doc, $xpath) = DB_Utils::loadXml(DB_Utils::FILE);
        $nodes = $xpath->query($xpathQuery);

        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }

        $doc->save(DB_Utils::FILE);
    }

    public static function addBlock($xpathQuery, $newXml, $position = 'append')
    {
        list($doc, $xpath) = DB_Utils::loadXml(DB_Utils::FILE);
        $nodes = $xpath->query($xpathQuery);

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

        $doc->save(DB_Utils::FILE);
    }
}
