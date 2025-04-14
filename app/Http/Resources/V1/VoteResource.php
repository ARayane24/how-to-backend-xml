<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'idAccount' => $this->id_account,
            'idTopic' => $this->id_topic,
            'isPositive' => $this->is_positive,
        ];
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

        // Convert array to XML
        $xml = new \SimpleXMLElement('<vote/>');
        $this->arrayToXml($data, $xml);

        // Load XSLT content safely
        $xslPath = public_path('xslt/vote.xsl');
        $xslContent = file_get_contents($xslPath);

        if ($xslContent === false) {
            return response("Failed to load XSLT file.", 500);
        }

        $xsl = new \DOMDocument();
        $xsl->loadXML($xslContent);

        // Load XML document
        $dom = new \DOMDocument();
        $dom->loadXML($xml->asXML());

        // Apply XSLT
        $proc = new \XSLTProcessor();
        $proc->importStylesheet($xsl);
        $html = $proc->transformToXML($dom);

        return response($html, 200)
            ->header('Content-Type', 'text/html');
    }
}
