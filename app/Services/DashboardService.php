<?php

namespace App\Services;

use App\Model\Camapign;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Traits\{UserElasticTrait, StatisticsTrait};
use App\Http\Resources\DashboardStatisticsResource;

class DashboardService extends AbstractService
{
    use UserElasticTrait;
    use StatisticsTrait;

    /**
     * get dashboard statistics
     *
     * @param Date $start_date
     * @param Date $end_date
     * @param String $user_identity
     * @return array
     */


     public function getStatistics($start_date, $end_date, $user_identity)
     {
        $start = Carbon::createFromTimeString($start_date);
        $end = Carbon::createFromTimeString($end_date);

        $start_date = $start->toISOString();
        $end_date = $end->toISOString();

        $index = $this->getEventIndexNames($start, $end);
        $body = [
            "size" => 0,
            "query" => [
                "term" => [
                    "campaignInfo.userId.keyword" => $user_identity
                ]
            ],
            "aggs" => [
                "stats_bucket" => [
                    "terms" => [
                        "field" => "campaignInfo.userId.keyword",
                        "size" => 1
                    ],
                    "aggs" => [
                        "messageInfo" => [
                            "filter" => [
                                "term" => [
                                    "eventType.keyword" => "SentMtSms"
                                ]
                            ],
                            "aggs" => [
                                "total_cost" => [
                                    "sum" => [
                                        "field" => "mtSmsSubmitInfo.messageSellPrice"
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

        $es_results = $this->getAggs($index, $body);

        $result = array(
            "sentcount"                => 0,
            "mobileclickscount"        => 0,
            "otherclickscount"         => 0,
            "cost"                     => 0,
            "revenue"                  => 0,
            "profit"                   => 0,
            "roi"                      => 0,
            'ctr'                      => 0,
            'opt_rate'                 => 0,
            'complainer_rate'          => 0,
            'reply_rate'               => 0,
        );

        if (!empty($es_results['stats_bucket']['buckets'])) {
            $result = $this->statsMapperEs($es_results['stats_bucket']['buckets'][0]);
        }

         return new DashboardStatisticsResource($result);
     }
}
