<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RouteReportCollection extends ResourceCollection
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
                'provider_id'   => $item['provider_id'],
                'sentcount'     => $item['sentcount'],
                'cost'          => $item['cost']
            ];
        });

        return $collection;
    }
}
