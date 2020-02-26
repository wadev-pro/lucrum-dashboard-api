<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Model\Campaign;
use App\Http\Requests\TldStatisticsRequest;
use App\Http\Requests\TemplateGroupStatisticsRequest;
use App\Http\Resources\TldStatisticsResource;
use App\Services\StatisticsService;
use Auth;

class StatisticsController extends Controller
{
    /**
     * @var StatisticsService
     */
    protected $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Get tld statistics by date range
     *
     * @param  App\Http\Requests\TldStatisticsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function getTldStatistics(TldStatisticsRequest $request)
    {
        $user = Auth::user();
        $filter = $request->all();
        $result = $this->statisticsService->getTldStatistics($filter, $user->email);
        return $result;
    }

    /**
     * Get Template Group statistics by date range
     *
     * @param  App\Http\Requests\TemplateGroupStatisticsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function getTemplateGroupStatistics(TemplateGroupStatisticsRequest $request)
    {
        $user = Auth::user();
        $filter = $request->all();
        $groupId = $filter['group_id'];
        $result = $this->statisticsService->getTemplateGroupStatistics($groupId, $filter, $user->email);
        return $result;
    }
}
