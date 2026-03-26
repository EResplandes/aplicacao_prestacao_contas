<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mimeType = $this->mime_type;

        return [
            'public_id' => $this->public_id,
            'type' => $this->type?->value,
            'disk' => $this->disk,
            'path' => $this->path,
            'original_name' => $this->original_name,
            'mime_type' => $mimeType,
            'size_bytes' => $this->size_bytes,
            'sha256' => $this->sha256,
            'url' => $this->path ? Storage::disk($this->disk)->url($this->path) : null,
            'is_image' => is_string($mimeType) && str_starts_with($mimeType, 'image/'),
        ];
    }
}
