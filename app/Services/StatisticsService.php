<?php

namespace App\Services;

use App\Model\Camapign;
use App\Model\MessageTemplate;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\{ TldStatisticsResource, TldStatisticsCollection, TemplateGroupStatisticsCollection};
use Illuminate\Pagination\LengthAwarePaginator;
use App\Traits\UserTrait;
use Carbon\Carbon;
use App\Traits\{UserElasticTrait, StatisticsTrait};
use Config;

class StatisticsService extends AbstractService
{
    use UserTrait;
    use UserElasticTrait;
    use StatisticsTrait;


    /**
     * get dashboard statistics
     *
     * @param Array $filters
     * @param String $user_identity
     * @return array
     */

    public function getTldStatistics($filters, $user_identity)
    {
        $orderMapper = function ($order_by) {
            switch($order_by) {
                case 'name': return '_key';
            }
            return $this->mapStatsOrder($order_by);
        };

        $levelIndex = isset($filters['level']) ? $filters['level'] : 0;

        $level = $this->tldLevelFields[$levelIndex];

        if(is_null($level)) {
            return [];
        }

        $pagination = $this->parsePagination($filters);

        $order = $this->parseOrdering($filters, $orderMapper);

        $body = $this->buildCampaignStatsQuery($user_identity, $level['field'], $pagination, $order);

        if(isset($level['filter'])) {
            $body["query"]["bool"]["must"][] = [
                "term" => [
                    $level['filter'] => $filters['filter']
                ]
            ];
        }

        $search = $this->parseSearch($filters);
        if (!is_null($search)) {
            $body["query"]["bool"]["must"][] = [
                "wildcard" => [
                    $level['field'] => [
                        "value" => $search
                    ]
                ]
            ];
        }

        $interval = $this->parseDateRange($filters);
        $index = $this->getEventIndexNames($interval['start'], $interval['end']);

        $es_results = $this->getAggs($index, $body);
        $total_count = $es_results['total_count']['value'];
        $mapper = function($item) {
            $data = $this->statsMapperEs($item);
            $data['tld'] = $item['key'];
            return $data;
        };

        $data = array_map($mapper, $es_results['stats_bucket']['buckets']);

        $result = new LengthAwarePaginator($data, $total_count, $pagination['pageSize'], $pagination['page']);
        $result->setPath(route('statistics.tld'));
        $result = new TldStatisticsCollection($result);
        return $result;
    }

    /**
     * get dashboard statistics
     *
     * @param Integer $group_id
     * @param Array $filters
     * @param String $user_identity
     * @return array
     */

    public function getTemplateGroupStatistics($group_id, $filters, $user_identity)
    {
        $orderMapper = function ($order_by) {
            switch($order_by) {
                case 'name': return '_key';
            }
            return $this->mapStatsOrder($order_by);
        };
        $pagination = $this->parsePagination($filters);
        $order = $this->parseOrdering($filters, $orderMapper);

        $body = $this->buildCampaignStatsQuery($user_identity, 'campaignInfo.messageTemplateName.keyword', $pagination, $order);
        $body["query"]["bool"]["must"][] = [
            "term" => [
                "campaignInfo.messageTemplateGroupId.keyword" => $group_id
            ]
        ];

        $search = $this->parseSearch($filters);
        if (!is_null($search)) {
            $body["query"]["bool"]["must"][] = [
                "wildcard" => [
                    "campaignInfo.messageTemplateName" => [
                        "value" => $search
                    ]
                ]
            ];
        }

        $interval = $this->parseDateRange($filters);
        $index = $this->getEventIndexNames($interval['start'], $interval['end']);

        $es_results = $this->getAggs($index, $body);
        $total_count = $es_results['total_count']['value'];
        $mapper = function($item) {
            $data = $this->statsMapperEs($item);
            $data['template'] = [
                "name" => $item['key']
            ];
            return $data;
        };

        $data = array_map($mapper, $es_results['stats_bucket']['buckets']);

        $result = new LengthAwarePaginator($data, $total_count, $pagination['pageSize'], $pagination['page']);
        $result->setPath(route('statistics.template_group'));
        $result = new TemplateGroupStatisticsCollection($result);
        return $result;
    }

    private function buildCampaignStatsQuery($user_identity, $field, $pagination, $order) {
        return [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "campaignInfo.userId.keyword" => $user_identity
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "total_count" => [
                    "cardinality" => [
                        "field" => $field
                    ]
                ],
                "stats_bucket" => [
                    "terms" => [
                        "size" => $pagination['size'],
                        "field" => $field
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
                                "term" => [
                                    "eventType.keyword" => "SentMtSms"
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
                                "term" => [
                                    "eventType.keyword" => "ConversionReceived"
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
                                "term" => [
                                    "eventType.keyword" => "ClickReceived"
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
                                "term" => [
                                    "eventType.keyword" => "MoSmsReceived"
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
                        ],
                        "profit" => [
                            "bucket_script" => [
                                "buckets_path" => [
                                    "revenue" => "conversionInfo>total_revenue",
                                    "cost" => "messageInfo>total_cost"
                                ],
                                "script" => "params.revenue - params.cost"
                            ]
                        ],
                        "roi" => [
                            "bucket_script" => [
                                "buckets_path" => [
                                    "revenue" => "conversionInfo>total_revenue",
                                    "cost" => "messageInfo>total_cost"
                                ],
                                "script" => "params.cost == 0 ? 0 : (params.revenue - params.cost) / params.cost"
                            ]
                        ],
                        "click_rate" => [
                            "bucket_script" => [
                                "buckets_path" => [
                                    "clicks" => "clickInfo>mobile_clicks>_count",
                                    "sent" => "messageInfo>_count"
                                ],
                                "script" => "params.sent == 0 ? 0 : params.clicks / params.sent"
                            ]
                        ],
                        "opt_rate" => [
                            "bucket_script" => [
                                "buckets_path" => [
                                    "opts" => "moSmsInfo>opt>_count",
                                    "sent" => "messageInfo>_count"
                                ],
                                "script" => "params.sent == 0 ? 0 : params.opts / params.sent"
                            ]
                        ],
                        "complainer_rate" => [
                            "bucket_script" => [
                                "buckets_path" => [
                                    "complainers" => "moSmsInfo>complainer>_count",
                                    "sent" => "messageInfo>_count"
                                ],
                                "script" => "params.sent == 0 ? 0 : params.complainers / params.sent"
                            ]
                        ],
                        "reply_rate" => [
                            "bucket_script" => [
                                "buckets_path" => [
                                    "replies" => "moSmsInfo>reply>_count",
                                    "sent" => "messageInfo>_count"
                                ],
                                "script" => "params.sent == 0 ? 0 : params.replies / params.sent"
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
