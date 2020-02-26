<?php

namespace App\Traits;

use Elasticsearch;
use App\Enums\EventType;

trait UserElasticTrait
{
    private $eventTypes = [
        EventType::SentMtSms => 'SentMtSms',
        EventType::ClickReceived => 'ClickReceived',
        EventType::ConversionReceived => 'ConversionReceived',
        EventType::MoSmsReceived => 'MoSmsReceived',
    ];

    protected function count($index, $body)
    {
        $query = [
            'index' => $index,
            'body' => $body
        ];

        $result = Elasticsearch::count($query);
        return $result;
    }

    protected function search($index, $body)
    {
        $query = [
            'index' => $index,
            'body' => $body
        ];

        $result = Elasticsearch::search($query);
        return $result;
    }

    protected function msearch($body) {
        $query = [
            'body' => $body
        ];
        $result = Elasticsearch::msearch($query);
        return $result['responses'];
    }

    protected function scroll($scroll, $scroll_id)
    {
        $query = [
            'scroll' => $scroll,
            'scroll_id' => $scroll_id
        ];

        $result = Elasticsearch::scroll($query);
        return $result;
    }

    protected function getAggs($index, $body)
    {
        $query = [
            'index' => $index,
            'body' => $body
        ];

        $result = Elasticsearch::search($query);
        return $result['aggregations'];
    }

    protected function msearchAggs($body) {
        $results = $this->msearch($body);
        return array_map(function ($result) {
            return isset($result['aggregations']) ? $result['aggregations'] : [];
        }, $results);
    }

    protected function getRecursiveAggs($index, $body, $bucket_name)
    {
        $result = [];
        $has_after_key = false;
        do {
            try {
                $query = [
                    'index' => $index,
                    'body' => $body
                ];
                $search_result = Elasticsearch::search($query);
                if (isset($search_result) && !is_null($search_result)) {
                    $aggs = $search_result['aggregations'];
                    if (isset($aggs[$bucket_name]) && isset($aggs[$bucket_name]['buckets']) && count($aggs[$bucket_name]['buckets'])) {
                        $result = array_merge($result, $aggs[$bucket_name]['buckets']);
                    }
                    if (isset($aggs[$bucket_name]['after_key'])) {
                        $has_after_key = true;
                        $after_key = $aggs[$bucket_name]['after_key'];
                        $body['aggs'][$bucket_name]['composite']['after'] = $after_key;
                    } else {
                        $has_after_key = false;
                    }
                } else {
                    $has_after_key = false;
                }
            } catch(\Exception $e) {
                $has_after_key = false;
            }
        } while ($has_after_key);

        return $result;
    }

    protected function getIndexNames($index, $start_date, $end_date = null) {
        $indexes = array();
        $date = $start_date->copy()->startOfDay();
        array_push($indexes, $this->formatIndexName($index, $date));

        if(!is_null($end_date)) {
            $diff_in_days = $date->diffInDays($end_date);

            for ($i = 1; $i <= $diff_in_days; $i++) {
                array_push($indexes, $this->formatIndexName($index, $date->addDay()));
            }
        }

        return implode(',', $indexes);
    }

    protected function getEventIndexNames($start_date, $end_date = null) {
        return $this->getIndexNames('lucrum-backend-app-events', $start_date, $end_date);
    }

    private function formatIndexName($index, $date) {
        return $index . '-' . $date->format('d_m_Y');
    }
}
