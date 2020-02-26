<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
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
                'id'            => $item->id,
                'name'          => $item->name,
                'first_name'    => $item->first_name,
                'last_name'     => $item->last_name,
                'email'         => $item->email,
                'role'          => $item->role,
                'created_at'    => $item->created_at,
                'updated_at'    => $item->updated_att,
                'withLinkPrice' => $item->withLinkPrice,
                'userRouteFee' => $item->userRouteFee,
                'withoutLinkPrice' => $item->withoutLinkPrice,
                'didPrice' => $item->didPrice,
            ];
        });

        return $collection;
    }
}
