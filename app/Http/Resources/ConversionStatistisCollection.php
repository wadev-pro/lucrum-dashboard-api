<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ConversionStatistisCollection extends ResourceCollection
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
                'received_at' => $item['received_at'],
                'device' => $item['device'],
                'amount' => $item['amount'],
                'redirect_url' => trim($item['redirect_url']),
            ];
        });

        return $collection;
    }
}
