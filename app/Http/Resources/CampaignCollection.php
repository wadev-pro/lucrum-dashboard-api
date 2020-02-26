<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CampaignCollection extends ResourceCollection
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
                'no' => $item['no'],
                'campaign_id' => $item['campaign_id'],
                'name' => $item['name'],
                'processing_status' => $item['processing_status'],
                'created_at' => $item['created_at'],
                'button_status' => $this->getButtonStatus($item['processing_status']),
                'complainer_rate' => $item['complainer_rate'],
                'conversioncount' => $item['conversioncount'],
                'mobileclickscount' => $item['mobileclickscount'],
                'opt_rate' => $item['opt_rate'],
                'otherclickscount' => $item['otherclickscount'],
                'profit' => $item['profit'],
                'reply_rate' => $item['reply_rate'],
                'revenue' => $item['revenue'],
                'sentcount' => $item['sentcount'],
                'cost' => $item['cost'],
                'ctr' => $item['ctr'],
                'roi' => $item['roi']
            ];
        });

        return $collection;
    }

    protected function getButtonStatus($processing_status) {
        $showEdit = true;
		$showDetails = true;
		$showStart = true;
		$showStop = true;
		$showTesting = true;
        $showCheck = false;
        $showStopCheck = false;
		switch ($processing_status) {
			case 0:
				$showEdit = false;
				$showStart = false;
				$showStop = false;
				$showTesting = false;
				break;
			case 1:
				$showEdit = false;
				$showStart = false;
				$showStop = false;
				$showTesting = false;
				break;
			case 2:
                $showCheck = true;
				break;
			case 3:
				$showStart = false;
				$showStop = false;
				$showTesting = false;
				$showEdit = false;
				break;
			case 4:
				$showStart = false;
				$showTesting = false;
				break;
			case 5:
				$showStart = false;
				$showStop = false;
				$showTesting = false;
				break;
			case 6:
				$showStart = false;
				$showStop = false;
				$showTesting = false;
				break;
			case 7:
                $showStart = false;
				break;
			case 8:
				$showStart = false;
				$showStop = false;
				$showEdit = false;
				break;
			case 9:
				$showStart = false;
				$showTesting = false;
				$showEdit = false;
				break;
			case 10:
				$showStart = false;
				$showStop = false;
				$showTesting = false;
				$showEdit = false;
				break;
			case 11:
				break;
			case 12:
                $showCheck = true;
				$showStop = false;
				break;
			case 13:
				$showStop = false;
				break;
			case 14:
				$showStop = false;
				$showEdit = false;
				break;
			case 15:
				$showStop = false;
                $showCheck = true;
				break;
			case 16:
				$showStart = false;
				$showStop = false;
				$showTesting = false;
				$showEdit = false;
				break;
			case 17:
				$showStart = false;
				$showStop = false;
				$showTesting = false;
				$showEdit = false;
				break;
			case 18:
				$showStop = false;
                $showCheck = true;
				break;
            case 19:
                $showCheck = false;
                $showStopCheck = true;
                break;
			default:
				break;
		}
        return [
            'shwo_edit'     => $showEdit,
            'show_detail'   => $showDetails,
            'show_start'   => $showStart,
            'show_stop'   => $showStop,
            'show_testing'   => $showTesting,
            'show_check'   => $showCheck,
            'show_stop_check'   => $showStopCheck,
        ];
    }
}
