<?php

namespace App\Services;

use App\Model\{Campaign, MessageSent, MessageFailed, MessageProcessingFailed, MessageFiltered, MessageTemplate};
use Illuminate\Support\Facades\DB;
use App\Http\Resources\CampaignCollection;
use App\Http\Resources\CarrierStatisticsCollection;
use App\Http\Resources\DidStatistisCollection;
use App\Http\Resources\CampaignTldStatistisCollection;
use App\Http\Resources\MessageTemplateStatistisCollection;
use App\Http\Resources\SmsStatistisCollection;
use App\Http\Resources\ClickStatistisCollection;
use App\Http\Resources\ConversionStatistisCollection;
use App\Http\Resources\CampaignStatisticsResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Traits\{UserElasticTrait, StatisticsTrait};
use Config;

class CampaignService extends AbstractService
{
    use UserElasticTrait;
    use StatisticsTrait;
    /**
     * get campaing list
     *
     * @param Array $filters
     * @param String $user_identity
     * @return array
     */

    private $filteredMessageLabel = array(
        0 => 'None',
        1 => 'BlockedAreaCode',
        2 => 'Complainer',
        3 => 'BlackList',
        4 => 'NotCellular',
        5 => 'BlockedCarrier',
        6 => 'NotClicker',
        7 => 'InvalidPhone'
    );

    public function getList($filters, $user_identity)
    {
        $result = [];
        $db_result = [];
        $page  = isset($filters['page']) ? $filters['page'] : 1;
        $per_page = isset($filters['per_page']) ? $filters['per_page'] : 10;
        $offset = ($page - 1) * $per_page;

        $query = Campaign::where('created_by', $user_identity);

		if(!empty($filters['search'])) {
			$searchValue = $filters['search'];
            $query = $query->where('name', 'ilike', strtolower($searchValue).'%');
		}

        $start = Carbon::today();
        $end = Carbon::today()->add(1, 'day');
        if(!empty($filters['start_date']) && !empty($filters['end_date'])){
            $start = Carbon::createFromTimeString($filters['start_date']);
            $end = Carbon::createFromTimeString($filters['end_date']);
        }
        
        $start_date = $start->format('Y-m-d H:i:sP');
        $end_date = $end->format('Y-m-d H:i:sP');

        $query = $query->whereBetween('created_at', [$start_date, $end_date]);

		$num_results_filtered= $query->count();

        if (isset($filters['order_by'])) {
            $order_dir = $filters['order_dir'] ? $filters['order_dir'] : 'desc';
            $query = $query->orderBy($filters['order_by'], $order_dir);
        } else {
            $query = $query->orderBy('created_at', 'desc');
        }

        $query = $query->offset($offset)->limit($per_page);

		$campaigns = $query->get()->toArray();
        $campaignIds = array();
        foreach ($campaigns as $campaign){
            array_push($campaignIds, $campaign['campaign_id']);
        }

        $statistics = $this->getStatistics($campaignIds, $start, $end);

        foreach ($statistics as $stat) {
            foreach ($campaigns as $key => $campaign) {
                if($campaign['campaign_id'] == $stat['campaignid']) {
                    $campaigns[$key]['complainer_rate'] = $stat['complainer_rate'];
                    $campaigns[$key]['conversioncount'] = $stat['conversioncount'];
                    $campaigns[$key]['cost'] = $stat['cost'];
                    $campaigns[$key]['ctr'] = $stat['ctr'];
                    $campaigns[$key]['mobileclickscount'] = $stat['mobileclickscount'];
                    $campaigns[$key]['opt_rate'] = $stat['opt_rate'];
                    $campaigns[$key]['otherclickscount'] = $stat['otherclickscount'];
                    $campaigns[$key]['profit'] = $stat['profit'];
                    $campaigns[$key]['reply_rate'] = $stat['reply_rate'];
                    $campaigns[$key]['revenue'] = $stat['revenue'];
                    $campaigns[$key]['sentcount'] = $stat['sentcount'];
                    $campaigns[$key]['roi'] = $stat['roi'];
                }
            }
        }

        $count = $offset;
        $campaigns = array_map(function($item, $index) use($count){
            $item['no'] = $count + $index + 1;
            return $item;
        }, $campaigns, array_keys($campaigns));

        $result = new LengthAwarePaginator($campaigns, $num_results_filtered, $per_page, $page);
        $result->setPath(route('campaign.index'));
        $result = new CampaignCollection($result);
        return $result;
    }

    /**
     * get detail
     *
     * @param Int $campaign_id
     * @return array
     */

    public function getDetail($campaign_id)
    {
         $campaign = Campaign::where('campaign_id', $campaign_id)->get()->toArray();
         if (count($campaign))
         {
             return $campaign[0];
         }
         return [];
    }

    /**
     * get statistics
     *
     * @param Array $campaign_ids
     * @return array
     */

    public function getStatistics($campaign_ids, $start_date, $end_date)
    {
        if (is_null($campaign_ids) || !count($campaign_ids))
            return [];

        $index = $this->getEventIndexNames($start_date, $end_date);
        $size = Config::get('constants.ES.max_bucket_size');

        $body =  [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "terms" => [
                                "campaignInfo.campaignId.keyword" => $campaign_ids
                            ]
                        ],
                    ],
                    "filter" =>  [
                        "exists" => [
                            "field" => "campaignInfo.campaignId"
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "campaign_bucket" => [
                    "terms" => [
                        "size" => $size,
                        "field" => "campaignInfo.campaignId.keyword"
                    ],
                    "aggs" => [
                        "messageInfo" => [
                            "filter" => [
                                "bool" => [
                                    "must" => [
                                        [
                                            "term" => [
                                                "eventType.keyword" => "SentMtSms"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "mtSmsSubmitInfo"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "aggs" => [
                                "total_cost" => [
                                    "sum" => [
                                        "field" => "mtSmsSubmitInfo.messageCost"
                                    ]
                                ]
                            ]
                        ],
                        "conversionInfo" => [
                            "filter" => [
                                "bool" => [
                                    "must" => [
                                        [
                                            "term" => [
                                                "eventType.keyword" => "ConversionReceived"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "conversionInfo"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "clickInfo"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "aggs" => [
                                "total_revenue" => [
                                    "sum" => [
                                        "field" => "conversionInfo.payoutAmount"
                                    ]
                                ]
                            ]
                        ],
                        "clickInfo" => [
                            "filter" => [
                                "bool" => [
                                    "must" => [
                                        [
                                            "term" => [
                                                "eventType.keyword" => "ClickReceived"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "clickInfo"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "aggs" => [
                                "mobile_clicks" => [
                                    "filter" => [
                                        "term" => [
                                            "clickInfo.isMobileDevice" => true
                                        ]
                                    ]
                                ],
                                "other_clicks" => [
                                    "filter" => [
                                        "term" => [
                                            "clickInfo.isMobileDevice" => false
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "moSmsInfo" => [
                            "filter" => [
                                "bool" => [
                                    "must" => [
                                        [
                                            "term" => [
                                                "eventType.keyword" => "MoSmsReceived"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "moSmsSubmitInfo"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "aggs" => [
                                "opt" => [
                                    "filter" => [
                                        "term" => [
                                            "moSmsSubmitInfo.classificationType.keyword" => "OptOut"
                                        ]
                                    ]
                                ],
                                "complainer" => [
                                    "filter" => [
                                        "term" => [
                                            "moSmsSubmitInfo.classificationType.keyword" => "Complainer"
                                        ]
                                    ]
                                ],
                                "reply" => [
                                    "filter" => [
                                        "term" => [
                                            "moSmsSubmitInfo.classificationType.keyword" => "Reply"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $es_result =  $this->getAggs($index, $body);

        $result = [];
        $bucket = $es_result['campaign_bucket']['buckets'];

        foreach($campaign_ids as $key => $campaign_id) {
            $result[$key] = array(
                'campaignid' => $campaign_id,
                'sentcount' => 0,
                'mobileclickscount' => 0,
                'otherclickscount' => 0,
                'conversioncount' => 0,
                'cost' => 0,
                'revenue' => 0,
                'profit' => 0,
                'roi' => 0,
                'ctr' => 0,
                'opt_rate' => 0,
                'complainer_rate' => 0,
                'reply_rate' => 0,
            );
            if(count($bucket))
            {
                $item = array_filter($bucket, function($t_item) use($campaign_id){
                    return $t_item['key'] == $campaign_id;
                });
                if ($item) {
                    $item = reset($item);
                }
                if ($item) {
                    $campaign_id = $item['key'];
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

                    $result[$key]    = array(
                        'campaignid' => $campaign_id,
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
                        'reply_rate' => $reply_rate,
                    );
                }
            }
        }

        $result = array_map(function($item) {
            return new CampaignStatisticsResource($item);
        }, $result);
        return $result;
    }

    public function getMessageCounts($campaign_id)
    {
        if(is_null($campaign_id)) {
            return [];
        }

        $campaign = $this->getDetail($campaign_id);

        $sentIndex = $this->getCampaignIndexes($campaign);
        $sendingFailedIndex = $this->getCampaignIndexes($campaign, 'lucrum-backend-mt-message-sending-failed-events');
        $processingFailedIndex = $this->getCampaignIndexes($campaign, 'lucrum-backend-mt-message-processing-failed-events');
        $filteredIndex = $this->getCampaignIndexes($campaign, 'lucrum-backend-mt-message-filtered-events');

        $query = [
            [ "index" => $sentIndex ],
            [
                "size" => 0,
                "query" => [
                    "term" => [
                        "campaignInfo.campaignId.keyword" => $campaign_id
                    ]
                ],
                "aggs" => [
                    "sent" => [
                        "filter" => [
                            "term" => [
                                "eventType.keyword" => "SentMtSms"
                            ]
                        ]
                    ]
                ]
            ],
            [
                "ignore_unavailable" => true, 
                "index" => $sendingFailedIndex
            ],
            [
                "size" => 0,
                "query" => [
                    "term" => [
                        "campaignInfo.campaignId.keyword" => $campaign_id
                    ]
                ],
                "aggs" => [
                    "failed" => [
                        "filter" => [
                            "exists" => [
                                "field" => "failedAt"
                            ]
                        ]
                    ]
                ]
            ],
            [
                "ignore_unavailable" => true, 
                "index" => $processingFailedIndex
            ],
            [
                "size" => 0,
                "query" => [
                    "term" => [
                        "campaignInfo.campaignId.keyword" => $campaign_id
                    ]
                ],
                "aggs" => [
                    "failed" => [
                        "filter" => [
                            "exists" => [
                                "field" => "failedAt"
                            ]
                        ]
                    ]
                ]
            ],
            [
                "ignore_unavailable" => true, 
                "index" => $filteredIndex
            ],
            [
                "size" => 0,
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "term" => [
                                    "campaignInfo.campaignId.keyword" => $campaign_id
                                ]
                            ]
                        ]
                    ]
                ],
                "aggs" => [
                    "total" => [
                        "terms" => [
                            "field" => "filter.keyword"
                        ]
                    ]
                ]
            ]
        ];

        $results = $this->msearchAggs($query);

        return [
            'sent_count' => $results[0]['sent']['doc_count'],
            'failed_count' => isset($results[1]['failed']) ? $results[1]['failed']['doc_count'] : 0,
            'processing_failed_count' => isset($results[2]['failed']) ? $results[2]['failed']['doc_count'] : 0,
            'filtered' => $results[3]["total"]["buckets"]
        ];
    }

    private function getCampaignIndexes($campaign, $index = 'lucrum-backend-app-events') {
        if(is_string($campaign) || is_numeric($campaign)) {
            $campaign = $this->getDetail($campaign);
        }

        return $this->getIndexNames($index, new Carbon($campaign['created_at']), Carbon::today());
    }

    private function buildCampaignMessagesQuery($campaign_id, $eventType, $pagination, $order) {
        return [
            "from" => $pagination['pageOffset'],
            "size" => $pagination['pageSize'],
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "campaignInfo.campaignId.keyword" => $campaign_id
                            ]
                        ],
                    ],
                    "filter" => [
                        [
                            "term" => [
                                "eventType.keyword" => $eventType
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "mtSmsSubmitInfo"
                            ]
                        ]
                    ]
                ]
            ],
            "sort" => [
                $order['field'] => $order['dir']
            ],
            "aggs" => [
                "total_count" => [
                    "value_count" => [
                        "field" => 'eventType.keyword'
                    ]
                ]
            ]
        ];
    }

    private function buildCampaignStatsQuery($campaign_id, $field, $pagination, $order) {
        $keyword = $field . '.keyword';
        return [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "campaignInfo.campaignId.keyword" => $campaign_id
                            ]
                        ]
                    ],
                    "filter" => [                        
                        "exists" => [
                            "field" => $keyword
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "total_count" => [
                    "cardinality" => [
                        "field" => $keyword
                    ]
                ],
                "stats_bucket" => [
                    "terms" => [
                        "size" => $pagination['size'],
                        "field" => $keyword
                    ],
                    "aggs" => [
                        "stats_sort" => [
                            "bucket_sort" => [
                                "from" => $pagination['pageOffset'],
                                "size" => $pagination['pageSize'],
                                "sort" => [
                                    $order['field'] => $order['dir']
                                ]
                            ]
                        ],
                        "messageInfo" => [
                            "filter" => [
                                "bool" => [
                                    "must" => [
                                        [
                                            "term" => [
                                                "eventType.keyword" => "SentMtSms"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "mtSmsSubmitInfo"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "aggs" => [
                                "total_cost" => [
                                    "sum" => [
                                        "field" => "mtSmsSubmitInfo.messageCost"
                                    ]
                                ]
                            ]
                        ],
                        "conversionInfo" => [
                            "filter" => [
                                "bool" => [
                                    "must" => [
                                        [
                                            "term" => [
                                                "eventType.keyword" => "ConversionReceived"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "conversionInfo"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "clickInfo"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "aggs" => [
                                "total_revenue" => [
                                    "sum" => [
                                        "field" => "conversionInfo.payoutAmount"
                                    ]
                                ]
                            ]
                        ],
                        "clickInfo" => [
                            "filter" => [
                                "bool" => [
                                    "must" => [
                                        [
                                            "term" => [
                                                "eventType.keyword" => "ClickReceived"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "clickInfo"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "aggs" => [
                                "mobile_clicks" => [
                                    "filter" => [
                                        "term" => [
                                            "clickInfo.isMobileDevice" => true
                                        ]
                                    ]
                                ],
                                "other_clicks" => [
                                    "filter" => [
                                        "term" => [
                                            "clickInfo.isMobileDevice" => false
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "moSmsInfo" => [
                            "filter" => [
                                "bool" => [
                                    "must" => [
                                        [
                                            "term" => [
                                                "eventType.keyword" => "MoSmsReceived"
                                            ]
                                        ],
                                        [
                                            "exists" => [
                                                "field" => "moSmsSubmitInfo"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "aggs" => [
                                "opt" => [
                                    "filter" => [
                                        "term" => [
                                            "moSmsSubmitInfo.classificationType.keyword" => "OptOut"
                                        ]
                                    ]
                                ],
                                "complainer" => [
                                    "filter" => [
                                        "term" => [
                                            "moSmsSubmitInfo.classificationType.keyword" => "Complainer"
                                        ]
                                    ]
                                ],
                                "reply" => [
                                    "filter" => [
                                        "term" => [
                                            "moSmsSubmitInfo.classificationType.keyword" => "Reply"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getCampaignStatistics($campaign_id, $field, $filters, $mapper) {
        $orderMapper = function ($order_by) {
            switch($order_by) {
                case 'carrier':
                case 'did':
                case 'tld':
                    return '_key';
            }
            return $this->mapStatsOrder($order_by);
        };

        $pagination = $this->parsePagination($filters);

        $order = $this->parseOrdering($filters, $orderMapper);

        $body = $this->buildCampaignStatsQuery($campaign_id, $field, $pagination, $order);

        $search = $this->parseSearch($filters);
        if (!is_null($search)) {
            $body["query"]["bool"]["must"][] = [
                "wildcard" => [
                    $field => [
                        "value" => $search
                    ]
                ]
            ];
        }

        $index = $this->getCampaignIndexes($campaign_id);

        $es_results = $this->getAggs($index, $body);
        $total_count = $es_results['total_count']['value'];

        $data = array_map($mapper, $es_results['stats_bucket']['buckets']);

        return new LengthAwarePaginator($data, $total_count, $pagination['pageSize'], $pagination['page']);
    }

    /**
     * get carrier statistics
     *
     * @param String $campaign_id
     * @param Array $filter
     * @return array
     */

    public function getCarrierStatistics($campaign_id, $filters)
    {
        $field = 'mtSmsSubmitInfo.mobileCarrierInfo.carrier';
        $mapper = $this->keyedStatsMapper('carrier');
        $result = $this->getCampaignStatistics($campaign_id, $field, $filters, $mapper);
        $result->setPath(route('campaign.carrier_statistics', [ 'campaign_id'=>$campaign_id ]));
        return new CarrierStatisticsCollection($result);
    }

    /**
     * get did statistics
     *
     * @param String $campaign_id
     * @param Array $filter
     * @return array
     */

    public function getDidStatistics($campaign_id, $filters)
    {
        $field = 'mtSmsSubmitInfo.from';
        $mapper = $this->keyedStatsMapper('did');
        $result = $this->getCampaignStatistics($campaign_id, $field, $filters, $mapper);
        $result->setPath(route('campaign.did_statistics', ['campaign_id'=>$campaign_id]));
        return new DidStatistisCollection($result);
    }

    /**
     * get tld statistics
     *
     * @param String $campaign_id
     * @param Array $filter
     * @return array
     */

    public function getTldStatistics($campaign_id, $filters)
    {
        $field = 'mtSmsSubmitInfo.generatedMessageLinkInfo.tld';
        $mapper = $this->keyedStatsMapper('tld');
        $result = $this->getCampaignStatistics($campaign_id, $field, $filters, $mapper);
        $result->setPath(route('campaign.tld_statistics', ['campaign_id'=>$campaign_id]));
        $result = new CampaignTldStatistisCollection($result);
        return $result;
    }

    /**
     * get message template statistics
     *
     * @param String $campaign_id
     * @param Array $filter
     * @return array
     */

    public function getMessageTemplateStatistics($campaign_id, $filters, $user_identity)
    {
        $query = MessageTemplate::where('created_by', $user_identity);

        if(!empty($filters['search'])) {
            $searchValue = trim($filters['search']);
            $query = $query->where('name', 'ilike', '%'.strtolower($searchValue).'%');
        }
        $templates = array_column($query->get()->toArray(), 'name', 'template_id');

        $mapper = function($item) use($templates) {
            $data = $this->statsMapper($item);

            $templateid = $item['key'];
            if(isset($templates[$templateid])) {
                $data['templatename'] = $templates[$templateid];
                $data['messagetemplateid'] = $templateid;
            }
            return $data;
        };
        $field = 'campaignInfo.messageTemplateId';

        $result = $this->getCampaignStatistics($campaign_id, $field, $filters, $mapper);
        $result->setPath(route('campaign.message_template_statistics', ['campaign_id'=>$campaign_id]));
        $result = new MessageTemplateStatistisCollection($result);
        return $result;
    }

    /**
     * get sms statistics
     *
     * @param String $campaign_id
     * @param Array $filter
     * @return array
     */

    public function getSmsStatistics($campaign_id, $filters, $user_identity)
    {
        $orderMapper = function ($order_by) {
            $ordering = $this->mapMessagesOrder($order_by);

            return is_null($ordering) ? 'mtSmsSubmitInfo.sentAt' : $ordering;
        };

        $pagination = $this->parsePagination($filters);

        $order = $this->parseOrdering($filters, $orderMapper, 'sent_at');

        $body = $this->buildCampaignMessagesQuery($campaign_id, 'SentMtSms', $pagination, $order);
        $search = $this->parseSearch($filters);
        if (!is_null($search)) {
            $body["query"]["bool"]["must"][] = [
                "multi_match" => [
                    "query" => $search,
                    "type" => "phrase",
                    "fields" => [
                        "campaignInfo.didPoolName",
                        "mtSmsSubmitInfo.from",
                        "mtSmsSubmitInfo.to",
                        "mtSmsSubmitInfo.craftedMessage",
                        "campaignInfo.messageTemplateGroupName",
                        "campaignInfo.messageTemplateName",
                        "mtSmsSubmitInfo.mobileCarrierInfo.carrier",
                        "leadInfo.FirstName.carrier",
                        "leadInfo.LastName.carrier",
                        "leadInfo.City.carrier",
                        "leadInfo.State.carrier",
                        "leadInfo.Zip.carrier",
                        "leadInfo.Phone.carrier"
                    ]
                ]
            ];
        }

        $index = $this->getCampaignIndexes($campaign_id);

        $es_results = $this->search($index, $body);
        $total_count = $es_results['aggregations']['total_count']['value'];

        $mapper = function($item) {
            $source = $item['_source'];
            return [
                'did_pool' => $source['campaignInfo']['didPoolName'],
                'did' => $source['mtSmsSubmitInfo']['from'],
                'crafted_message' => $source['mtSmsSubmitInfo']['craftedMessage'],
                'template_group' => $source['campaignInfo']['messageTemplateGroupName'],
                'template' => $source['campaignInfo']['messageTemplateName'],
                'carrier' => $source['mtSmsSubmitInfo']['mobileCarrierInfo'],
                'to' => $source['mtSmsSubmitInfo']['to'],
                'lead' => $source['leadInfo'],
                'sent_at' => $source['mtSmsSubmitInfo']['sentAt']
            ];
        };

        $data = array_map($mapper, $es_results['hits']['hits']);

        $result = new LengthAwarePaginator($data, $total_count, $pagination['pageSize'], $pagination['page']);
        $result->setPath(route('campaign.sms_statistics', ['campaign_id'=>$campaign_id]));
        $result = new SmsStatistisCollection($result);
        return $result;
    }

    /**
     * get click statistics
     *
     * @param String $campaign_id
     * @param Array $filter
     * @return array
     */

    public function getClickStatistics($campaign_id, $filters, $user_identity)
    {
        $orderMapper = function ($order_by) {
            $ordering = $this->mapMessagesOrder($order_by);

            return is_null($ordering) ? 'clickInfo.clickedAt' : $ordering;
        };

        $pagination = $this->parsePagination($filters);

        $order = $this->parseOrdering($filters, $orderMapper, 'sent_at');

        $body = $this->buildCampaignMessagesQuery($campaign_id, 'ClickReceived', $pagination, $order);

        $search = $this->parseSearch($filters);
        if (!is_null($search)) {
            $body["query"]["bool"]["must"][] = [
                "multi_match" => [
                    "query" => $search,
                    "type" => "phrase",
                    "fields" => [
                        "campaignInfo.didPoolName",
                        "mtSmsSubmitInfo.from",
                        "mtSmsSubmitInfo.to",
                        "mtSmsSubmitInfo.craftedMessage",
                        "campaignInfo.messageTemplateName",
                        "mtSmsSubmitInfo.mobileCarrierInfo.carrier",
                        "clickInfo.device",
                        "clickInfo.redirectUrl",
                        "leadInfo.FirstName.carrier",
                        "leadInfo.LastName.carrier",
                        "leadInfo.City.carrier",
                        "leadInfo.State.carrier",
                        "leadInfo.Zip.carrier",
                        "leadInfo.Phone.carrier",
                    ]
                ]
            ];
        }

        $index = $this->getCampaignIndexes($campaign_id);

        $es_results = $this->search($index, $body);
        $total_count = $es_results['aggregations']['total_count']['value'];

        $mapper = function($item) {
            $source = $item['_source'];
            return [
                'did_pool' => $source['campaignInfo']['didPoolName'],
                'did' => $source['mtSmsSubmitInfo']['from'],
                'crafted_message' => $source['mtSmsSubmitInfo']['craftedMessage'],
                'template' => $source['campaignInfo']['messageTemplateName'],
                'carrier' => $source['mtSmsSubmitInfo']['mobileCarrierInfo'],
                'to' => $source['mtSmsSubmitInfo']['to'],
                'lead' => $source['leadInfo'],
                'clicked_at' => $source['clickInfo']['clickedAt'],
                'device' => $source['clickInfo']['device'],
                'redirect_url' => $source['clickInfo']['redirectUrl']
            ];
        };

        $data = array_map($mapper, $es_results['hits']['hits']);

        $result = new LengthAwarePaginator($data, $total_count, $pagination['pageSize'], $pagination['page']);
        $result->setPath(route('campaign.sms_statistics', ['campaign_id'=>$campaign_id]));
        $result = new ClickStatistisCollection($result);
        return $result;
    }

    /**
     * get conversion statistics
     *
     * @param String $campaign_id
     * @param Array $filter
     * @return array
     */

    public function getConversionStatistics($campaign_id, $filters, $user_identity)
    {
        $orderMapper = function ($order_by) {
            $ordering = $this->mapMessagesOrder($order_by);

            return is_null($ordering) ? 'conversionInfo.receivedAt' : $ordering;
        };

        $pagination = $this->parsePagination($filters);

        $order = $this->parseOrdering($filters, $orderMapper, 'sent_at');

        $body = $this->buildCampaignMessagesQuery($campaign_id, 'ConversionReceived', $pagination, $order);
        
        $search = $this->parseSearch($filters);
        if (!is_null($search)) {
            $body["query"]["bool"]["must"][] = [
                "multi_match" => [
                    "query" => $search,
                    "type" => "phrase",
                    "fields" => [
                        "campaignInfo.didPoolName",
                        "mtSmsSubmitInfo.from",
                        "mtSmsSubmitInfo.to",
                        "mtSmsSubmitInfo.craftedMessage",
                        "campaignInfo.messageTemplateName",
                        "mtSmsSubmitInfo.mobileCarrierInfo.carrier",
                        "leadInfo.FirstName.carrier",
                        "leadInfo.LastName.carrier",
                        "leadInfo.City.carrier",
                        "leadInfo.State.carrier",
                        "leadInfo.Zip.carrier",
                        "leadInfo.Phone.carrier",
                        "conversionInfo.deviceCarrier",
                        "conversionInfo.deviceState",
                        "conversionInfo.deviceCity",
                        "conversionInfo.deviceOs",
                        "conversionInfo.deviceName",
                        "conversionInfo.deviceBrand",
                        "conversionInfo.deviceModel",
                        "conversionInfo.deviceBrowserVersion",
                        "conversionInfo.deviceOsVersion",
                        "conversionInfo.deviceIsp",
                        "conversionInfo.deviceIp",
                        "conversionInfo.deviceBrowser"
                    ]
                ]
            ];
        }

        $index = $this->getCampaignIndexes($campaign_id);

        $es_results = $this->search($index, $body);
        $total_count = $es_results['aggregations']['total_count']['value'];

        $mapper = function($item) {
            $source = $item['_source'];
            return [
                'did_pool' => $source['campaignInfo']['didPoolName'],
                'did' => $source['mtSmsSubmitInfo']['from'],
                'crafted_message' => $source['mtSmsSubmitInfo']['craftedMessage'],
                'template' => $source['campaignInfo']['messageTemplateName'],
                'carrier' => $source['mtSmsSubmitInfo']['mobileCarrierInfo'],
                'to' => $source['mtSmsSubmitInfo']['to'],
                'lead' => $source['leadInfo'],
                'received_at' => $source['conversionInfo']['receivedAt'],
                'device' => array(
                    'Carrier' => isset($source['conversionInfo']['deviceCarrier'])? $source['conversionInfo']['deviceCarrier']: "n/a",
                    'State' => isset($source['conversionInfo']['deviceState'])? $source['conversionInfo']['deviceState']: "n/a",
                    'City' => isset($source['conversionInfo']['deviceCity'])? $source['conversionInfo']['deviceCity']: "n/a",
                    'Os' => isset($source['conversionInfo']['deviceOs'])? $source['conversionInfo']['deviceOs']: "n/a",
                    'Name' => isset($source['conversionInfo']['deviceName'])? $source['conversionInfo']['deviceName']: "n/a",
                    'Brand' => isset($source['conversionInfo']['deviceBrand'])? $source['conversionInfo']['deviceBrand']: "n/a",
                    'Model' => isset($source['conversionInfo']['deviceModel'])? $source['conversionInfo']['deviceModel']: "n/a",
                    'BrowserVersion' => isset($source['conversionInfo']['deviceBrowserVersion'])? $source['conversionInfo']['deviceBrowserVersion']: "n/a",
                    'OsVersion' => isset($source['conversionInfo']['deviceOsVersion'])? $source['conversionInfo']['deviceOsVersion']: "n/a",
                    'Isp' => isset($source['conversionInfo']['deviceIsp'])? $source['conversionInfo']['deviceIsp']: "n/a",
                    'Ip' => isset($source['conversionInfo']['deviceIp'])? $source['conversionInfo']['deviceIp']: "n/a",
                ),
                'amount' => $source['conversionInfo']['payoutAmount'],
                'redirect_url' => $source['clickInfo']['redirectUrl']
            ];
        };

        $data = array_map($mapper, $es_results['hits']['hits']);

        $result = new LengthAwarePaginator($data, $total_count, $pagination['pageSize'], $pagination['page']);
        $result->setPath(route('campaign.conversion_statistics', ['campaign_id'=>$campaign_id]));
        $result = new ConversionStatistisCollection($result);
        return $result;
    }
}