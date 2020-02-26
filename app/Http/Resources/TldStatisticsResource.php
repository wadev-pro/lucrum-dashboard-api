<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TldStatisticsResource extends JsonResource
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
            'tld'               => $this['tld'],
            'sentcount'         => $this['sentcount'],
            'mobileclickscount' => $this['mobileclickscount'],
            'otherclickscount'  => $this['otherclickscount'],
            'conversioncount'   => $this['conversioncount'],
            'cost'              => $this['cost'],
            'revenue'           => $this['revenue'],
            'profit'            => $this['profit'],
            'roi'               => $this['roi'],
            'cr'                => $this['cr']
        ];
    }
}
