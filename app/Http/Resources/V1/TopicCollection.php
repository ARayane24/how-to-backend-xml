<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TopicCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    protected function arrayToXml(array $data, \SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild($key);
                $this->arrayToXml($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }

    public function toResponse($request)
    {
        $data = $this->toArray($request);

        $xml = new \SimpleXMLElement('<topics/>');

        // Either use arrayToXml:
        foreach ($data as $accountData) {
            $accountXml = $xml->addChild('topic');
            foreach ($accountData as $key => $value) {
                $accountXml->addChild($key, htmlspecialchars((string) $value));
            }
        }

        // Load and validate XSLT
        $xslPath = public_path('xslt/topic.xsl');
        $xslContent = file_get_contents($xslPath);

        if ($xslContent === false) {
            return response("Failed to load XSLT file.", 500);
        }

        $xsl = new \DOMDocument();
        if (!$xsl->loadXML($xslContent)) {
            return response("Invalid XSLT content.", 500);
        }

        $dom = new \DOMDocument();
        $dom->loadXML($xml->asXML());

        $proc = new \XSLTProcessor();
        $proc->importStylesheet($xsl);
        $html = $proc->transformToXML($dom);

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
