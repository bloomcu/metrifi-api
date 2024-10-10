<?php

namespace DDD\Http\Files\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folder_id' => $this->folder_id,
            'title' => $this->title,
            'filename' => $this->filename,
            // 'path' => $this->path,
            'url' => $this->getStorageUrl(),
            'extension' => $this->extension,
            'size' => $this->size,
        ];
    }
}
