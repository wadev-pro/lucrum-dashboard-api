<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Traits\UtilsTrait;

class LeadReportsCollection extends ResourceCollection
{
    use UtilsTrait;
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
                'firstname'     => $this->formatLeadString($item['firstname']),
                'lastname'      => $this->formatLeadString($item['lastname']),
                'email'         => $this->formatLeadString($item['email']),
                'phone'         => $this->formatLeadString($item['phone']),
                'city'          => $this->formatLeadString($item['city']),
                'state'         => $this->formatLeadString($item['state']),
                'zip'           => $this->formatLeadString($item['zip'])
            ];
        });

        return $collection;
    }
}
