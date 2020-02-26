<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $filter = json_decode($this->filter);
        return array(
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'start_date' => $filter->start_date,
            'end_date' => $filter->end_date,
            'filter' => $filter->filter,
            'filename' => $this->filename,
            'status' => $this->status,
            'completed_at' => $this->completed_at,
            'log' => $this->log,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        );
    }
}
