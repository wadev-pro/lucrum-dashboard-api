<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClickStatistisCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $collection = $this->collection->map(function($item){
            return [
                'did_pool' => trim($item['did_pool']),
                'did' => trim($item['did']),
                'crafted_message' => trim($item['crafted_message']),
                'template' => trim($item['template']),
                'carrier' => $item['carrier'],
                'to' => trim($item['to']),
                'lead' => $item['lead'],
                'clicked_at' => $item['clicked_at'],
                'device' => trim($item['device']),
                'redirect_url' => trim($item['redirect_url']),
            ];
        });

        return $collection;
    }
}
