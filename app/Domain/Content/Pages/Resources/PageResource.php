<?php

namespace DDD\Domain\Pages\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Pages\Resources\PageTypeResource;
use DDD\Domain\Pages\Resources\PageJunkStatusResource;
use DDD\Domain\Base\Statuses\Resources\StatusResource;
use DDD\Domain\Base\Categories\Resources\CategoryResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => new PageTypeResource($this->type),
            'title' => $this->title,
            'location' => $this->location,
            'path' => $this->path,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];;
    }
}
