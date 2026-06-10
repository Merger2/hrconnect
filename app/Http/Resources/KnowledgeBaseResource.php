<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeBaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category?->value,
            'status' => $this->status?->value,
            'source_document' => $this->source_document,
            'page_number' => $this->page_number,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
