<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatisticsResource extends JsonResource
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
            'sentcount'                 => $this['sentcount'],
            'mobileclickscount'         => $this['mobileclickscount'],
            'otherclickscount'          => $this['otherclickscount'],
            'cost'                      => $this['cost'],
            'revenue'                   => $this['revenue'],
            'profit'                    => $this['profit'],
            'roi'                       => $this['roi'],
            'ctr'                       => $this['ctr'],
            'opt_rate'                  => $this['opt_rate'],
            'complainer_rate'           => $this['complainer_rate'],
            'reply_rate'                => $this['reply_rate'],
        ];
    }
}
