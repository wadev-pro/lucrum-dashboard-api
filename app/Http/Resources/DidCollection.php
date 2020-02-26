<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DidCollection extends ResourceCollection
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
                'id'                            => $item['id'],
                'didPoolId'                     => $item['did_pool_id'],
                'code'                          => $item['did_code'],
                'messageGatewayProviderId'      => $item['message_gateway_provider_id'],
                'activatedAt'                   => $item['activated_at'],
                'isAutoPurchased'               => $item['is_auto_purchased'],
                'status'                        => $item['status'],
                'deactivatedAt'                 => $item['deactivated_at'],
                'deactivatedBy'                 => $item['deactivated_by'],
                'deactivationReason'            => $item['deactivation_reason'],
                'reactivatedAt'                 => $item['reactivated_at'],
                'reactivatedBy'                 => $item['reactivated_ny'],
            ];
        });
        return $collection;
    }
}
