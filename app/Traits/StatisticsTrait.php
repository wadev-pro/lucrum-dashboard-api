<?php

namespace App\Traits;

use Carbon\Carbon;

trait StatisticsTrait
{

    private $tldLevelFields = [
        [
            "field" => "mtSmsSubmitInfo.generatedMessageLinkInfo.tld.keyword"
        ],
        [ 
            "field" => "mtSmsSubmitInfo.generatedMessageLinkInfo.baseUrl.keyword",
            "filter" => "mtSmsSubmitInfo.generatedMessageLinkInfo.tld.keyword"
        ],
        [ 
            "field" => "mtSmsSubmitInfo.generatedMessageLinkInfo.fullUrl.keyword",
            "filter" => "mtSmsSubmitInfo.generatedMessageLinkInfo.baseUrl.keyword"
        ]
    ];

    /**
     * @return String
     */
     protected function getRoi($profit, $cost){
         if ($cost)
             $roi = $profit / $cost;
         else
             if ($profit)
                 $roi = 1;
             else
                 $roi = 0;
         return $roi;
     }

     protected function getCtr($mobileclicks, $sentcount){
         if ($sentcount)
             $ctr = $mobileclicks / $sentcount;
         else
             if ($mobileclicks)
                 $ctr = 1;
             else
                 $ctr = 0;
         return $ctr;
     }

     protected function getMoRate($total_count, $sentcount){
         if ($sentcount)
             $mo_rate = $total_count / $sentcount;
         else
             if ($total_count)
                 $mo_rate = 1;
             else
                 $mo_rate = 0;
         return $mo_rate;
    }

    protected function parseDateRange($filters) {
        $start = Carbon::today();
        $end = Carbon::today()->add(1, 'day');
        if(!empty($filters['start_date']) && !empty($filters['end_date'])){
            $start = Carbon::createFromTimeString($filters['start_date']);
            $end = Carbon::createFromTimeString($filters['end_date']);
        }

        return [
            "start" => $start,
            "end" => $end
        ];
    }

    protected function parseSearch($filters) {
        if (isset($filters['search'])) {
            $trimmed = trim($filters['search']);
            return empty($trimmed) ? null : $trimmed;
        }
        return null;
    }

    protected function parsePagination($filters) {
        $page  = isset($filters['page']) ? $filters['page'] : 1;
        $per_page = isset($filters['per_page']) ? $filters['per_page'] : 10;
        $offset = ($page - 1) * $per_page;

        return [
            'page' => $page,
            'pageSize' => $per_page,
            'pageOffset' => $offset,
            'size' => 1000
        ];
    }

    protected function parseOrdering($filters, $mapper, $order_by = 'sentCount', $order_dir = 'desc') {
        if(isset($filters['order_by']))
            $order_by = $filters['order_by'];
        if(isset($filters['order_dir']))
            $order_dir = $filters['order_dir'];

        return [
            'field' => $mapper($order_by),
            'dir'=> $order_dir
        ];
    }

    protected function mapStatsOrder($order_by) {
        switch($order_by) {
            case 'sentcount': return 'messageInfo>_count';
            case 'mobileclickscount': 
            case 'mobileclick': 
                return 'clickInfo>mobile_clicks>_count';
            case 'otherclickscount':
            case 'otherclick': 
                return 'clickInfo>other_clicks>_count';
            case 'conversioncount': return 'conversionInfo>_count';
            case 'cost': return 'messageInfo>total_cost';
            case 'revenue': return 'conversionInfo>total_revenue';
            case 'opt_rate': return 'moSmsInfo>opt>_count';
            case 'complainer_rate': return 'moSmsInfo>complainer>_count';
            case 'reply_rate': return 'moSmsInfo>reply>_count';
            case 'profit': return 'profit';
            case 'roi': return 'roi';
            case 'ctr': return 'click_rate';
        }
        return null;
    }

    protected function mapMessagesOrder($order_by) {
        switch($order_by) {
            case 'did_pool': return 'campaignInfo.didPoolName.keyword';
            case 'message': return 'mtSmsSubmitInfo.craftedMessage.keyword';
            case 'template_group': return 'campaignInfo.messageTemplateGroupName.keyword';
            case 'template': return 'campaignInfo.messageTemplateName.keyword';
            case 'to': return 'mtSmsSubmitInfo.to.keyword';
            case 'did': return 'mtSmsSubmitInfo.from.keyword';
            case 'sent_at': return 'mtSmsSubmitInfo.sentAt';
            case 'device': return 'clickInfo.device.keyword';
            case 'redirect_url': return 'clickInfo.redirectUrl.keyword';
            case 'clicked_at': return 'clickInfo.clickedAt';
            case 'amount': return 'conversionInfo.payoutAmount';
            case 'received_at': return 'conversionInfo.receivedAt';
        }
        return null;
    }

    protected function keyedStatsMapper($key) {
        return function($item) use ($key) {
            $data = $this->statsMapper($item);
            $data[$key] = $item['key'];
            return $data;
        };
    }

    protected function statsMapper($item) {
        $sentcount = isset($item['messageInfo']) ? $item['messageInfo']['doc_count']: 0;;
        $mobileclicks = isset($item['clickInfo']) ? $item['clickInfo']['mobile_clicks']['doc_count']: 0;
        $otherclicks = isset($item['clickInfo']) ? $item['clickInfo']['other_clicks']['doc_count']: 0;
        $conversioncount = isset($item['conversionInfo']) ? $item['conversionInfo']['doc_count']: 0;
        $cost = isset($item['messageInfo']) ? $item['messageInfo']['total_cost']['value']: 0;
        $revenue = isset($item['conversionInfo']) ? $item['conversionInfo']['total_revenue']['value']: 0;
        $profit = $revenue - $cost;
        $roi = $this->getRoi($profit, $cost);
        $ctr = $this->getRoi($mobileclicks, $sentcount);
        $opt_count = isset($item['moSmsInfo']) ? $item['moSmsInfo']['opt']['doc_count']: 0;
        $opt_rate = $this->getMoRate($opt_count, $sentcount);
        $complainer_count = isset($item['moSmsInfo']) ? $item['moSmsInfo']['complainer']['doc_count']: 0;
        $complainer_rate = $this->getMoRate($complainer_count, $sentcount);
        $reply_count = isset($item['moSmsInfo']) ? $item['moSmsInfo']['reply']['doc_count']: 0;
        $reply_rate = $this->getMoRate($reply_count, $sentcount);

        return [
            'sentcount' => $sentcount,
            'mobileclickscount' => $mobileclicks,
            'otherclickscount' => $otherclicks,
            'conversioncount' => $conversioncount,
            'cost' => $cost,
            'revenue' => $revenue,
            'profit' => $profit,
            'roi' => $roi,
            'ctr' => $ctr,
            'opt_rate' => $opt_rate,
            'complainer_rate' => $complainer_rate,
            'reply_rate' => $reply_rate
        ];
    }

    protected function statsMapperEs($item) {
        $sentcount = $item['messageInfo']['doc_count'];
        $mobileclicks = $item['clickInfo']['mobile_clicks']['doc_count'];
        $otherclicks = $item['clickInfo']['other_clicks']['doc_count'];
        $conversioncount = $item['conversionInfo']['doc_count'];
        $cost = $item['messageInfo']['total_cost']['value'];
        $revenue = $item['conversionInfo']['total_revenue']['value'];
        $profit = $item['profit']['value'];
        $roi = $item['roi']['value'];
        $ctr = $item['click_rate']['value'];
        $opt_count = $item['moSmsInfo']['opt']['doc_count'];
        $opt_rate = $item['opt_rate']['value'];
        $complainer_count = $item['moSmsInfo']['complainer']['doc_count'];
        $complainer_rate = $item['complainer_rate']['value'];
        $reply_count = $item['moSmsInfo']['reply']['doc_count'];
        $reply_rate = $item['reply_rate']['value'];

        return [
            'sentcount' => $sentcount,
            'mobileclickscount' => $mobileclicks,
            'otherclickscount' => $otherclicks,
            'conversioncount' => $conversioncount,
            'cost' => $cost,
            'revenue' => $revenue,
            'profit' => $profit,
            'roi' => $roi,
            'ctr' => $ctr,
            'opt_rate' => $opt_rate,
            'complainer_rate' => $complainer_rate,
            'reply_rate' => $reply_rate
        ];
    }
}
