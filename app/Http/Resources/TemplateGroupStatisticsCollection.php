<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TemplateGroupStatisticsCollection extends ResourceCollection
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
            $template_item = $item['template'];
            return [
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
                'template'              => $item['template']
            ];
        });

        return $collection;
    }
}
