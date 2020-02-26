<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CampaignTldStatistisCollection extends ResourceCollection
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
                'tld'                   => $item['tld'],
                'sentcount'             => $item['sentcount'],
                'mobileclickscount'     => $item['mobileclickscount'],
                'otherclickscount'      => $item['otherclickscount'],
                'conversioncount'       => $item['conversioncount'],
                'cost'                  => $item['cost'],
                'revenue'               => $item['revenue'],
                'profit'                => $item['profit'],
                'roi'                   => $item['roi'],
                'ctr'                   => $item['ctr'],
                'opt_rate'              => $item['opt_rate'],
                'complainer_rate'       => $item['complainer_rate'],
                'reply_rate'            => $item['reply_rate'],
            ];
        });
        return $collection;
    }
}
