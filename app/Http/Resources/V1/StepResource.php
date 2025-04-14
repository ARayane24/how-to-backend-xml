<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StepResource extends JsonResource
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
            'idTopic' => $this->id_topic, //foreign key
            'title' => $this->title,
            'description' => $this->description,
            'updatedAt' => $this->updated_at,
        ];
    }

    public function toResponse($request)
    {
        $data = $this->toArray($request);

        // Optional: Log to debug
        logger($data);

        $xml = new \SimpleXMLElement('<step/>');
        $this->arrayToXml($data, $xml);

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml');
    }
}
