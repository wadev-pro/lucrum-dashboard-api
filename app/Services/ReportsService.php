<?php

namespace App\Services;

use Elasticsearch;
use Config;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\{ LeadReportsCollection, RouteReportCollection };
use Illuminate\Pagination\LengthAwarePaginator;
use App\Model\ReportStatus;
use App\Traits\{UserElasticTrait, UtilsTrait};
use App\Enums\{EventType, ReportType, EventFilterType, ReportStatusType};
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\{S3Service, ReportStatusService};
use Exception;

class ReportsService extends AbstractService
{
    use UserElasticTrait;
    use UtilsTrait;

    /**
     * @var S3Service
     */
    protected $s3Service;

    /**
     * @var ReportStatusService
     */
    protected $reportStatusService;

    public function __construct(
        S3Service $s3Service,
        ReportStatusService $reportStatusService
    )
    {
        $this->s3Service = $s3Service;
        $this->reportStatusService = $reportStatusService;
    }

    /**
     * get lead report
     *
     * @param Array $filters
     * @param String $user_identity
     * @return array
     */

    public function getRouteReports($filters, $user_identity)
    {
        $c_filter = null;
        if (isset($filters['filter'])) {
            if (is_array($filters['filter']))
                $c_filter = $filters['filter'];
            else
                $c_filter = json_decode($filters['filter'], true);
        }
        $start_date = Carbon::today()->toISOString();
        $end_date = Carbon::today()->add(1, 'day')->toISOString();
        if(!empty($filters['start_date']) && !empty($filters['end_date'])){
            $start_date = Carbon::createFromTimeString($filters['start_date'])->toISOString();
            $end_date = Carbon::createFromTimeString($filters['end_date'])->toISOString();
        }

        $page  = isset($filters['page']) ? $filters['page'] : 1;
        $per_page = isset($filters['per_page']) ? $filters['per_page'] : 10;
        $offset = ($page - 1) * $per_page;
        $query_size = $offset + $per_page;

        $order_by = "route";
        $order_dir = "desc";
        if(isset($filters['order_by']))
            $order_by = $filters['order_by'];
        if(isset($filters['order_dir']))
            $order_dir = $filters['order_dir'];

        switch($order_by) {
            case 'sentcount':
                $order_arr = [
                    'leadInfo>LastName' => $order_dir
                ];
                break;
            case 'cost':
                $order_arr = [
                    'leadInfo>Phone' => $order_dir
                ];
                break;
            case 'route':
            default:
                $order_arr = [
                    'leadInfo>FirstName' => $order_dir
                ];
                break;
        }

        $filter_arr = [];
        $filter_must_not_arr = [];
        if ($c_filter) {
            if (isset($c_filter['route'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.messagingGatewayProviderId.keyword" => $c_filter['route']
                    ]
                ];
            }

        }

        $index = 'lucrum-backend-app-events-*';
        $size = Config::get('constants.ES.max_bucket_size') > $query_size ? $query_size : Config::get('constants.ES.max_bucket_size');

        $body =  [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "eventType.keyword" => $this->eventTypes[EventType::SentMtSms]
                            ]
                        ],
                        [
                            "bool" => [
                                "should" => [
                                    [
                                        "range" => [
                                            "mtSmsSubmitInfo.sentAt" => [
                                                "gte" => $start_date,
                                                "lte" => $end_date
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "mtSmsSubmitInfo"
                            ]
                        ]
                    ],
                    "must_not" => [
                    ]
                ]
            ],
            "aggs" => [
                "route_bucket" => [
                    "terms" => [
                        "size" => $size,
                        "field" => "campaignInfo.messagingGatewayProviderId.keyword"
                    ],
                    "aggs" => [
                        "total_cost" => [
                            "sum" => [
                                "field" => "mtSmsSubmitInfo.messageCost"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if (count($filter_arr)) {
            $body["query"]["bool"]["must"] = array_merge($body["query"]["bool"]["must"], $filter_arr);
        }
        if (count($filter_must_not_arr)) {
            $body["query"]["bool"]["must_not"] = array_merge($body["query"]["bool"]["must_not"], $filter_must_not_arr);
        }

        $es_result =  $this->getAggs($index, $body);
        $total_count = $this->getRouteReportTotalCount($filters, $user_identity);

        $result = [];
        $bucket = array_slice($es_result['route_bucket']['buckets'], $offset);
        foreach ($bucket as $item) {

            $sentcount = isset($item['doc_count']) ? $item['doc_count']: 0;
            $cost = isset($item['total_cost']) ? $item['total_cost']['value']: 0;
            $result[]    = array(
                'provider_id' => $item['key'],
                'sentcount' => $sentcount,
                'cost' => $cost,
            );
        }

        $result = new LengthAwarePaginator($result, $total_count, $per_page, $page);
        $result->setPath(route('reports.route'));
        $result = new RouteReportCollection($result);
        return $result;
    }

    /**
     * get route report total count
     *
     * @param String $user_identity
     * @param Array $filter
     * @return array
     */

    public function getRouteReportTotalCount($filters, $user_identity)
    {
        $c_filter = null;
        if (isset($filters['filter'])) {
            if (is_array($filters['filter']))
                $c_filter = $filters['filter'];
            else
                $c_filter = json_decode($filters['filter'], true);
        }
        $start_date = Carbon::today()->toISOString();
        $end_date = Carbon::today()->add(1, 'day')->toISOString();
        if(!empty($filters['start_date']) && !empty($filters['end_date'])){
            $start_date = Carbon::createFromTimeString($filters['start_date'])->toISOString();
            $end_date = Carbon::createFromTimeString($filters['end_date'])->toISOString();
        }

        $filter_arr = [];
        $filter_must_not_arr = [];
        if ($c_filter) {
            if (isset($c_filter['vertical'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.vertical.keyword" => $c_filter['vertical']
                    ]
                ];
            }

            if (isset($c_filter['fileowner'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.campaignFileTags.Owner.keyword" => $c_filter['fileowner']
                    ]
                ];
            }

            if (isset($c_filter['carrier'])) {
                $filter_arr[] = [
                    "term" => [
                        "mtSmsSubmitInfo.mobileCarrierInfo.carrier.keyword" => $c_filter['carrier']
                    ]
                ];
            }
        }

        $index = 'lucrum-backend-app-events-*';

        $body =  [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "eventType.keyword" => $this->eventTypes[EventType::SentMtSms]
                            ]
                        ],
                        [
                            "bool" => [
                                "should" => [
                                    [
                                        "range" => [
                                            "mtSmsSubmitInfo.sentAt" => [
                                                "gte" => $start_date,
                                                "lte" => $end_date
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "mtSmsSubmitInfo"
                            ]
                        ]
                    ],
                    "must_not" => [
                    ]
                ]
            ],
            "aggs" => [
                "total_count" => [
                    "cardinality" => [
                        "field" => "campaignInfo.messagingGatewayProviderId.keyword"
                    ]
                ]
            ]
        ];

        if (count($filter_arr)) {
            $body["query"]["bool"]["must"] = array_merge($body["query"]["bool"]["must"], $filter_arr);
        }
        if (count($filter_must_not_arr)) {
            $body["query"]["bool"]["must_not"] = array_merge($body["query"]["bool"]["must_not"], $filter_must_not_arr);
        }

        $data = $this->getAggs($index, $body);
        return $data['total_count']['value'];
    }


    /**
     * get lead report
     *
     * @param Array $filters
     * @param String $user_identity
     * @return array
     */

    public function getLeadReports($filters, $user_identity)
    {
        $c_filter = null;
        if (isset($filters['filter'])) {
            $c_filter = json_decode($filters['filter'], true);
        }
        $start_date = Carbon::today()->toISOString();
        $end_date = Carbon::today()->add(1, 'day')->toISOString();
        if(!empty($filters['start_date']) && !empty($filters['end_date'])){
            $start_date = Carbon::createFromTimeString($filters['start_date'])->toISOString();
            $end_date = Carbon::createFromTimeString($filters['end_date'])->toISOString();
        }

        $page  = isset($filters['page']) ? $filters['page'] : 1;
        $per_page = isset($filters['per_page']) ? $filters['per_page'] : 10;
        $offset = ($page - 1) * $per_page;
        $query_size = $offset + $per_page;

        $order_by = "firstname";
        $order_dir = "desc";
        if(isset($filters['order_by']))
            $order_by = $filters['order_by'];
        if(isset($filters['order_dir']))
            $order_dir = $filters['order_dir'];

        switch($order_by) {
            case 'lastname':
                $order_arr = [
                    'leadInfo>LastName' => $order_dir
                ];
                break;
            case 'phone':
                $order_arr = [
                    'leadInfo>Phone' => $order_dir
                ];
                break;
            case 'city':
                $order_arr = [
                    'leadInfo>City' => $order_dir
                ];
                break;
            case 'state':
                $order_arr = [
                    'leadInfo>State' => $order_dir
                ];
                break;
            case 'zip':
                $order_arr = [
                    'leadInfo>Zip' => $order_dir
                ];
                break;
            case 'firstname':
            default:
                $order_arr = [
                    'leadInfo>FirstName' => $order_dir
                ];
                break;
        }

        $filter_arr = [];
        $filter_must_not_arr = [];
        if ($c_filter) {
            if (isset($c_filter['vertical'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.vertical.keyword" => $c_filter['vertical']
                    ]
                ];
            }

            if (isset($c_filter['fileowner'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.campaignFileTags.Owner.keyword" => $c_filter['fileowner']
                    ]
                ];
            }

            if (isset($c_filter['carrier'])) {
                $filter_arr[] = [
                    "term" => [
                        "mtSmsSubmitInfo.mobileCarrierInfo.carrier.keyword" => $c_filter['carrier']
                    ]
                ];
            }

            if (isset($c_filter['eventtype'])) {
                switch ($c_filter['eventtype']) {
                    case EventFilterType::Clicker:
                        $filter_arr[] = [
                            "terms" => [
                                "eventType.keyword" => [
                                    $this->eventTypes[EventType::ClickReceived],
                                    $this->eventTypes[EventType::ConversionReceived]
                                ]
                            ]
                        ];
                        break;
                    case EventFilterType::ClickNotConverter:
                        $filter_arr[] = [
                            "term" => [
                                "eventType.keyword" => $this->eventTypes[EventType::ClickReceived]
                            ]
                        ];
                        break;
                    case EventFilterType::Converter:
                        $filter_arr[] = [
                            "terms" => [
                                "eventType.keyword" => [
                                    $this->eventTypes[EventType::ConversionReceived]
                                ]
                            ]
                        ];
                    default:
                        // code...
                        break;
                }
            }

            if (isset($c_filter['state'])) {
                $filter_arr[] = [
                    "term" => [
                        "leadInfo.State.keyword" => $c_filter['state']
                    ]
                ];
            }

            if (isset($c_filter['city'])) {
                $filter_arr[] = [
                    "term" => [
                        "leadInfo.City.keyword" => $c_filter['city']
                    ]
                ];
            }
        }

        $index = 'lucrum-backend-app-events-*';
        $size = Config::get('constants.ES.max_allowed_bucket_size');

        $body =  [
            "size" => $size,
            "_source" => "leadInfo",
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "campaignInfo.userId.keyword" => $user_identity
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "clickInfo"
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "conversionInfo"
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "leadInfo"
                            ]
                        ]
                    ],
                    "filter" => [
                        "range" => [
                            "eventTimestamp" => [
                                "gte" => $start_date,
                                "lte" => $end_date
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if (count($filter_arr)) {
            $body["query"]["bool"]["must"] = array_merge($body["query"]["bool"]["must"], $filter_arr);
        }
        if (count($filter_must_not_arr)) {
            $body["query"]["bool"]["must_not"] = array_merge($body["query"]["bool"]["must_not"], $filter_must_not_arr);
        }

        $es_result =  $this->search($index, $body);
        // $total_count = $this->getLeadReportTotalCount($filters, $user_identity);

        $result = [];
        $bucket = $es_result['hits']['hits'];
        $bucket = array_map(function($item) {
            return $item['_source']['leadInfo'];
        }, $bucket);
        $bucket_unique = $this->unique_multidim_array($bucket,'Phone');
        $current_page_data = array_slice($bucket_unique, $offset, $per_page);

        foreach ($current_page_data as $item) {
            if (!$item) {
                continue;
            }
            $firstname = isset($item['FirstName']) ? $item['FirstName']: '';
            $lastname = isset($item['LastName']) ? $item['LastName']: '';
            $phone = isset($item['Phone']) ? $item['Phone']: '';
            $city = isset($item['City']) ? $item['City']: '';
            $state = isset($item['State']) ? $item['State']: '';
            $zip = isset($item['Zip']) ? $item['Zip']: '';
            $email = isset($item['Email']) ? $item['Email']: '';

            $result[] = array(
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'phone' => $phone,
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
            );
        }
        $result = new LengthAwarePaginator($result, count($bucket_unique), $per_page, $page);
        $result->setPath(route('reports.lead'));
        $result = new LeadReportsCollection($result);
        return $result;
    }

    /**
     * get lead report total count
     *
     * @param String $user_identity
     * @param Array $filter
     * @return array
     */

    public function getLeadReportTotalCount($filters, $user_identity)
    {
        $c_filter = null;
        if (isset($filters['filter'])) {
            if (is_array($filters['filter']))
                $c_filter = $filters['filter'];
            else
                $c_filter = json_decode($filters['filter'], true);
        }
        $start_date = Carbon::today()->toISOString();
        $end_date = Carbon::today()->add(1, 'day')->toISOString();
        if(!empty($filters['start_date']) && !empty($filters['end_date'])){
            $start_date = Carbon::createFromTimeString($filters['start_date'])->toISOString();
            $end_date = Carbon::createFromTimeString($filters['end_date'])->toISOString();
        }

        $filter_arr = [];
        $filter_must_not_arr = [];
        if ($c_filter) {
            if (isset($c_filter['vertical'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.vertical.keyword" => $c_filter['vertical']
                    ]
                ];
            }

            if (isset($c_filter['fileowner'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.campaignFileTags.Owner.keyword" => $c_filter['fileowner']
                    ]
                ];
            }

            if (isset($c_filter['carrier'])) {
                $filter_arr[] = [
                    "term" => [
                        "mtSmsSubmitInfo.mobileCarrierInfo.carrier.keyword" => $c_filter['carrier']
                    ]
                ];
            }

            if (isset($c_filter['eventtype'])) {
                switch ($c_filter['eventtype']) {
                    case EventFilterType::Clicker:
                        $filter_arr[] = [
                            "terms" => [
                                "eventType.keyword" => [
                                    $this->eventTypes[EventType::ClickReceived],
                                    $this->eventTypes[EventType::ConversionReceived]
                                ]
                            ]
                        ];
                        break;
                    case EventFilterType::ClickNotConverter:
                        $filter_arr[] = [
                            "term" => [
                                "eventType.keyword" => $this->eventTypes[EventType::ClickReceived]
                            ]
                        ];
                        break;
                    case EventFilterType::Converter:
                        $filter_arr[] = [
                            "terms" => [
                                "eventType.keyword" => [
                                    $this->eventTypes[EventType::ConversionReceived]
                                ]
                            ]
                        ];
                    default:
                        // code...
                        break;
                }
            }

            if (isset($c_filter['state'])) {
                $filter_arr[] = [
                    "term" => [
                        "leadInfo.State.keyword" => $c_filter['state']
                    ]
                ];
            }

            if (isset($c_filter['city'])) {
                $filter_arr[] = [
                    "term" => [
                        "leadInfo.City.keyword" => $c_filter['city']
                    ]
                ];
            }
        }

        $index = 'lucrum-backend-app-events-*';

        $body =  [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "campaignInfo.userId.keyword" => $user_identity
                            ]
                        ],
                        [
                            "bool" => [
                                "should" => [
                                    [
                                        "range" => [
                                            "conversionInfo.receivedAt" => [
                                                "gte" => $start_date,
                                                "lte" => $end_date
                                            ]
                                        ]
                                    ],
                                    [
                                        "range" => [
                                            "clickInfo.clickedAt" => [
                                                "gte" => $start_date,
                                                "lte" => $end_date
                                            ]
                                        ]
                                    ]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "campaignInfo"
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "mtSmsSubmitInfo"
                            ]
                        ]
                    ],
                    "must_not" => [
                    ]
                ]
            ],
            "aggs" => [
                "total_count" => [
                    "cardinality" => [
                        "field" => "leadInfo.Phone.keyword"
                    ]
                ]
            ]
        ];

        if (count($filter_arr)) {
            $body["query"]["bool"]["must"] = array_merge($body["query"]["bool"]["must"], $filter_arr);
        }
        if (count($filter_must_not_arr)) {
            $body["query"]["bool"]["must_not"] = array_merge($body["query"]["bool"]["must_not"], $filter_must_not_arr);
        }

        $data = $this->getAggs($index, $body);
        return $data['total_count']['value'];
    }

    /**
     * get lead report export token
     *
     * @param String $user_identity
     * @param Array $filter
     * @param String $file_name
     * @param Array $header
     * @return array
     */

    public function getFullLeadData($filters, $user_identity, $file_name, $header)
    {
        $c_filter = null;
        if (isset($filters['filter'])) {
            $c_filter = json_decode($filters['filter'], true);
        }

        $start_date = Carbon::today()->toISOString();
        $end_date = Carbon::today()->add(1, 'day')->toISOString();
        if(!empty($filters['start_date']) && !empty($filters['end_date'])){
            $start_date = Carbon::createFromTimeString($filters['start_date'])->toISOString();
            $end_date = Carbon::createFromTimeString($filters['end_date'])->toISOString();
        }

        $filter_arr = [];
        if ($c_filter) {
            if (isset($c_filter['vertical'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.vertical.keyword" => $c_filter['vertical']
                    ]
                ];
            }

            if (isset($c_filter['fileowner'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.campaignFileTags.Owner.keyword" => $c_filter['fileowner']
                    ]
                ];
            }

            if (isset($c_filter['carrier'])) {
                $filter_arr[] = [
                    "term" => [
                        "mtSmsSubmitInfo.mobileCarrierInfo.carrier.keyword" => $c_filter['carrier']
                    ]
                ];
            }

            if (isset($c_filter['eventtype'])) {
                switch ($c_filter['eventtype']) {
                    case EventFilterType::Clicker:
                        $filter_arr[] = [
                            "terms" => [
                                "eventType.keyword" => [
                                    $this->eventTypes[EventType::ClickReceived],
                                    $this->eventTypes[EventType::ConversionReceived]
                                ]
                            ]
                        ];
                        break;
                    case EventFilterType::ClickNotConverter:
                        $filter_arr[] = [
                            "term" => [
                                "eventType.keyword" => $this->eventTypes[EventType::ClickReceived]
                            ]
                        ];
                        break;
                    case EventFilterType::Converter:
                        $filter_arr[] = [
                            "terms" => [
                                "eventType.keyword" => [
                                    $this->eventTypes[EventType::ConversionReceived]
                                ]
                            ]
                        ];
                    default:
                        // code...
                        break;
                }
            }

            if (isset($c_filter['state'])) {
                $filter_arr[] = [
                    "term" => [
                        "leadInfo.State.keyword" => $c_filter['state']
                    ]
                ];
            }

            if (isset($c_filter['city'])) {
                $filter_arr[] = [
                    "term" => [
                        "leadInfo.City.keyword" => $c_filter['city']
                    ]
                ];
            }
        }
        $index = 'lucrum-backend-app-events-*';
        $size = Config::get('constants.ES.max_allowed_bucket_size');

        $body =  [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "campaignInfo.userId.keyword" => $user_identity
                            ]
                        ],
                        [
                            "bool" => [
                                "should" => [
                                    [
                                        "range" => [
                                            "conversionInfo.receivedAt" => [
                                                "gte" => $start_date,
                                                "lte" => $end_date
                                            ]
                                        ]
                                    ],
                                    [
                                        "range" => [
                                            "clickInfo.clickedAt" => [
                                                "gte" => $start_date,
                                                "lte" => $end_date
                                            ]
                                        ]
                                    ]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "campaignInfo"
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
                "file_bucket" => [
                      "composite" => [
                            "size" => $size,
                            "sources" => [
                                [
                                    "number" => [
                                        "terms" => [
                                            "field" => "leadInfo.Phone.keyword"
                                        ]
                                    ]
                                ]
                            ]
                      ],
                      "aggs" => [
                          "lead" => [
                              "top_hits" => [
                                  "_source" => [
                                      "includes" => ["leadInfo"]
                                  ],
                                  "size" => 1
                              ]
                          ]
                      ]
                 ]
            ]
        ];

        if (count($filter_arr)) {
            $body["query"]["bool"]["must"] = array_merge($body["query"]["bool"]["must"], $filter_arr);
        }


        $response = new StreamedResponse(function() use($index, $body, $header) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $header);
            do {
                $has_after_key = false;
                $result = [];
                try {

                    $search_result = $this->search($index, $body);
                    if (isset($search_result) && !is_null($search_result)) {
                        $aggs = $search_result['aggregations'];
                        if (isset($aggs['file_bucket']) && isset($aggs['file_bucket']['buckets']) && count($aggs['file_bucket']['buckets'])) {
                            $data = $aggs['file_bucket']['buckets'];
                            $bucket = array_map(function($item) {
                                return @$item['lead']['hits']['hits'][0]['_source']['leadInfo'];
                            }, $data);
                            foreach ($bucket as $item) {
                                if (!$item) {
                                    continue;
                                }
                                $firstname = isset($item['FirstName']) ? $this->formatLeadString($item['FirstName']): '';
                                $lastname = isset($item['LastName']) ? $this->formatLeadString($item['LastName']): '';
                                $phone = isset($item['Phone']) ? $this->formatLeadString($item['Phone']): '';
                                $city = isset($item['City']) ? $this->formatLeadString($item['City']): '';
                                $state = isset($item['State']) ? $this->formatLeadString($item['State']): '';
                                $zip = isset($item['Zip']) ? $this->formatLeadString($item['Zip']): '';
                                $email = isset($item['Email']) ? $this->formatLeadString($item['Email']): '';

                                fputcsv($handle, array(
                                    'firstname' => $firstname,
                                    'lastname' => $lastname,
                                    'email' => $email,
                                    'phone' => $phone,
                                    'city' => $city,
                                    'state' => $state,
                                    'zip' => $zip,
                                ));
                            }
                        }
                        if (isset($aggs['file_bucket']['after_key'])) {
                            $has_after_key = true;
                            $after_key = $aggs['file_bucket']['after_key'];
                            $after_key['number'] = $this->formatLeadString($after_key['number']);
                            $body['aggs']['file_bucket']['composite']['after'] = $after_key;
                        } else {
                            $has_after_key = false;
                        }
                    } else {
                        $has_after_key = false;
                    }
                } catch(Exception $e) {
                    $has_after_key = false;
                }
            } while ($has_after_key);
            fclose($handle);
        }, 200, [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Description' => 'Lead Data',
            'Content-Type' => 'txet/csv',
            'Content-Disposition' => 'attachment; filename="'.$file_name.'"',
            'Expires' => '0',
            'Pragma' => 'public'
        ]);

        return $response;
    }

    /**
     * write csv from lead
     *
     * @param String $user_identity
     * @param Array $filter
     * @param ReportStatus $report_status
     * @return array
     */

    public function writeLeadDataToCSV($filters, $user_identity, $report_status)
    {
        $c_filter = null;
        if (isset($filters['filter'])) {
            $c_filter = json_decode($filters['filter'], true);
        }

        $start_date = Carbon::today()->toISOString();
        $end_date = Carbon::today()->add(1, 'day')->toISOString();
        if(!empty($filters['start_date']) && !empty($filters['end_date'])){
            $start_date = Carbon::createFromTimeString($filters['start_date'])->toISOString();
            $end_date = Carbon::createFromTimeString($filters['end_date'])->toISOString();
        }

        $filter_arr = [];
        if ($c_filter) {
            if (isset($c_filter['vertical'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.vertical.keyword" => $c_filter['vertical']
                    ]
                ];
            }

            if (isset($c_filter['fileowner'])) {
                $filter_arr[] = [
                    "term" => [
                        "campaignInfo.campaignFileTags.Owner.keyword" => $c_filter['fileowner']
                    ]
                ];
            }

            if (isset($c_filter['carrier'])) {
                $filter_arr[] = [
                    "term" => [
                        "mtSmsSubmitInfo.mobileCarrierInfo.carrier.keyword" => $c_filter['carrier']
                    ]
                ];
            }

            if (isset($c_filter['eventtype'])) {
                switch ($c_filter['eventtype']) {
                    case EventFilterType::Clicker:
                        $filter_arr[] = [
                            "terms" => [
                                "eventType.keyword" => [
                                    $this->eventTypes[EventType::ClickReceived],
                                    $this->eventTypes[EventType::ConversionReceived]
                                ]
                            ]
                        ];
                        break;
                    case EventFilterType::ClickNotConverter:
                        $filter_arr[] = [
                            "term" => [
                                "eventType.keyword" => $this->eventTypes[EventType::ClickReceived]
                            ]
                        ];
                        break;
                    case EventFilterType::Converter:
                        $filter_arr[] = [
                            "terms" => [
                                "eventType.keyword" => [
                                    $this->eventTypes[EventType::ConversionReceived]
                                ]
                            ]
                        ];
                    default:
                        // code...
                        break;
                }
            }

            if (isset($c_filter['state'])) {
                $filter_arr[] = [
                    "term" => [
                        "leadInfo.State.keyword" => $c_filter['state']
                    ]
                ];
            }

            if (isset($c_filter['city'])) {
                $filter_arr[] = [
                    "term" => [
                        "leadInfo.City.keyword" => $c_filter['city']
                    ]
                ];
            }
        }

        $index = 'lucrum-backend-app-events-*';
        $size = Config::get('constants.ES.max_allowed_bucket_size');
        $body =  [
            "size" => $size,
            "_source" => "leadInfo",
            "from" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "campaignInfo.userId.keyword" => $user_identity
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "clickInfo"
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "conversionInfo"
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "leadInfo"
                            ]
                        ]
                    ],
                    "filter" => [
                        "range" => [
                            "eventTimestamp" => [
                                "gte" => $start_date,
                                "lte" => $end_date
                            ]
                        ]
                    ]
                ]
            ]
        ];

        /*$body =  [
            "_source" => "leadInfo",
            "size" => $size,
            "from" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "campaignInfo.userId.keyword" => $user_identity
                            ]
                        ],
                        [
                            "bool" => [
                                "should" => [
                                    [
                                        "range" => [
                                            "conversionInfo.receivedAt" => [
                                                "gte" => $start_date,
                                                "lte" => $end_date
                                            ]
                                        ]
                                    ],
                                    [
                                        "range" => [
                                            "clickInfo.clickedAt" => [
                                                "gte" => $start_date,
                                                "lte" => $end_date
                                            ]
                                        ]
                                    ]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "leadInfo"
                            ]
                        ]
                    ]
                ]
            ]
        ];*/

        if (count($filter_arr)) {
            $body["query"]["bool"]["must"] = array_merge($body["query"]["bool"]["must"], $filter_arr);
        }

        $file_name = "lead-".date('y-m-d')."-".time().".csv";
        $header = [
            "FirstName",
            "LastName",
            "Email",
            "Phone",
            "City",
            "State",
            "Zip"
        ];

        $phoneArr = [];
        $scroll = Config::get('constants.ES.scroll');
        $scroll_id = null;
        $filepath = base_path().'/storage/'.$file_name;
        $handle = fopen($filepath, 'w+');
        fputcsv($handle, $header);
        fclose($handle);

        do {
            $has_after_key = false;
            $result = [];

            if(is_null($scroll_id))
            {
                $query = [
                    'index' => $index,
                    'body' => $body,
                    'scroll' => $scroll
                ];
                $search_result = Elasticsearch::search($query);
                $scroll_id = $search_result['_scroll_id'];
            } else {
                $search_result = $this->scroll($scroll, $scroll_id);
            }

            if (isset($search_result) && !is_null($search_result)) {
                $result = $search_result['hits']['hits'];
                if (count($result)) {
                    $has_after_key = true;
                    $body['from'] = $body['from'] + $size;

                    $handle = fopen($filepath, 'a+');
                    $result = array_map(function($tmp_item) {
                        $item = $tmp_item['_source']['leadInfo'];

                        $firstname = isset($item['FirstName']) ? $this->formatLeadString($item['FirstName']): '';
                        $lastname = isset($item['LastName']) ? $this->formatLeadString($item['LastName']): '';
                        $phone = isset($item['Phone']) ? $this->formatLeadString($item['Phone']): '';
                        $city = isset($item['City']) ? $this->formatLeadString($item['City']): '';
                        $state = isset($item['State']) ? $this->formatLeadString($item['State']): '';
                        $zip = isset($item['Zip']) ? $this->formatLeadString($item['Zip']): '';
                        $email = isset($item['Email']) ? $this->formatLeadString($item['Email']): '';
                        return array(
                            'FirstName' => $firstname,
                            'LastName' => $lastname,
                            'Phone' => $phone,
                            'City' => $city,
                            'State' => $state,
                            'Zip' => $zip,
                            'Email' => $email
                        );
                    }, $result);

                    foreach ($result as $item) {
                        if (!$item || in_array($item['Phone'], $phoneArr)) {
                            continue;
                        }
                        $phoneArr[] = $item['Phone'];
                        fputcsv($handle, array(
                            $item['FirstName'],
                            $item['LastName'],
                            $item['Email'],
                            $item['Phone'],
                            $item['City'],
                            $item['State'],
                            $item['Zip']
                        ));
                    }
                    fclose($handle);
                } else {
                    $has_after_key = false;
                }
            } else {
                $has_after_key = false;
            }
        } while ($has_after_key);

        $folder = Config::get('constants.S3.lead_folder') ? Config::get('constants.S3.lead_folder') : 'lead';
        $is_upload_success = $this->s3Service->putFile($folder, $file_name);
        if ($is_upload_success) {
            unlink($filepath);
        }
        $this->reportStatusService->updateReportStatus($report_status->id, array(
            'status'        => ReportStatusType::Completed,
            'completed_at'  => Carbon::now(),
            'filename'      => $is_upload_success ? $file_name : null
        ));
    }
}
