<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => $this->location,
            'description' => $this->description,
            'date' => $this->date->format('Y-m-d'), // Format explicite
            'user_id' => $this->user_id,
            'participants' => $this->participants->pluck('user_id') // Exemple
        ];
    }
}
