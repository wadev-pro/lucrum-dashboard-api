<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignStatisticsResource extends JsonResource
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
            "campaign_id"           => $this['campaignid'],
            'sentcount'             => $this['sentcount'],
            'mobileclickscount'     => $this['mobileclickscount'],
            'otherclickscount'      => $this['otherclickscount'],
            'conversioncount'       => $this['conversioncount'],
            'cost'                  => $this['cost'],
            'revenue'               => $this['revenue'],
            'profit'                => $this['profit'],
            'roi'                   => $this['roi'],
            'ctr'                   => $this['ctr'],
            'opt_rate'              => $this['opt_rate'],
            'complainer_rate'       => $this['complainer_rate'],
            'reply_rate'            => $this['reply_rate'],
        ];
    }
}
