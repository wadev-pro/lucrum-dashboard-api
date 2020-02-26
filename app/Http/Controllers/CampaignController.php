<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Campaign;
use App\Http\Requests\{CampaignRequest, CampaignListRequest, CampaignStatisticsRequest, CampaignDetailStatisticsWithDateRequest};
use App\Services\CampaignService;
use App\Http\Resources\CampaignStatisticsResource;
use App\Http\Resources\CampaignDbResource;
use Auth;

class CampaignController extends Controller
{
    /**
     * @var CampaignService
     */
    protected $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Get campaign list
     *
     * @param  App\Http\Requests\CampaignListRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function index(CampaignListRequest $request)
    {
        $user = Auth::user();
        $filter = $request->all();
        $result = $this->campaignService->getList($filter, $user->email);
        return $result;
    }

    /**
     * Get campaign detail
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request, $campaign_id)
    {
        $user = Auth::user();
        $result = $this->campaignService->getDetail($campaign_id);
        return new CampaignDbResource($result);
    }

    /**
     * Get message counts
     *
     * @param  App\Http\Requests\CampaignRequest  $request
     * @param  String $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function getMessageCounts(CampaignRequest $request, $campaign_id)
    {
        $request = $request->all();
        $result = $this->campaignService->getMessageCounts($campaign_id);
        return $result;
    }

    /**
     * Get statistis by ids
     *
     * @param  App\Http\Requests\CampaignStatisticsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function statistics(CampaignStatisticsRequest $request)
    {
        $request = $request->all();
        $campaign_ids = $request['campaign_ids'];
        $result = $this->campaignService->getStatistics($campaign_ids);
        return $result;
    }

    /**
     * Get carriers statistics
     *
     * @param  App\Http\Requests\CampaignDetailStatisticsWithDateRequest  $request
     * @param  String $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function getCarrierStatistics(CampaignDetailStatisticsWithDateRequest $request, $campaign_id)
    {
        $request = $request->all();
        $result = $this->campaignService->getCarrierStatistics($campaign_id, $request);
        return $result;
    }

    /**
     * Get did statistics
     *
     * @param  App\Http\Requests\CampaignDetailStatisticsWithDateRequest  $request
     * @param  String $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function getDidStatistics(CampaignDetailStatisticsWithDateRequest $request, $campaign_id)
    {
        $request = $request->all();
        $result = $this->campaignService->getDidStatistics($campaign_id, $request);
        return $result;
    }

    /**
     * Get tld statistics
     *
     * @param  App\Http\Requests\CampaignDetailStatisticsWithDateRequest  $request
     * @param  String $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function getTldStatistics(CampaignDetailStatisticsWithDateRequest $request, $campaign_id)
    {
        $request = $request->all();
        $result = $this->campaignService->getTldStatistics($campaign_id, $request);
        return $result;
    }

    /**
     * Get message template statistics
     *
     * @param  App\Http\Requests\CampaignDetailStatisticsWithDateRequest  $request
     * @param  String $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function getMessageTemplateStatistics(CampaignDetailStatisticsWithDateRequest $request, $campaign_id)
    {
        $user = Auth::user();
        $request = $request->all();
        $result = $this->campaignService->getMessageTemplateStatistics($campaign_id, $request, $user->email);
        return $result;
    }


    /**
     * Get sms statistics
     *
     * @param  App\Http\Requests\CampaignDetailStatisticsWithDateRequest  $request
     * @param  String $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function getSmsStatistics(CampaignDetailStatisticsWithDateRequest $request, $campaign_id)
    {
        $user = Auth::user();
        $request = $request->all();
        $result = $this->campaignService->getSmsStatistics($campaign_id, $request, $user->email);
        return $result;
    }

    /**
     * Get click statistics
     *
     * @param  App\Http\Requests\CampaignDetailStatisticsWithDateRequest  $request
     * @param  String $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function getClickStatistics(CampaignDetailStatisticsWithDateRequest $request, $campaign_id)
    {
        $user = Auth::user();
        $request = $request->all();
        $result = $this->campaignService->getClickStatistics($campaign_id, $request, $user->email);
        return $result;
    }

    /**
     * Get conversion statistics
     *
     * @param  App\Http\Requests\CampaignDetailStatisticsWithDateRequest  $request
     * @param  String $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function getConversionStatistics(CampaignDetailStatisticsWithDateRequest $request, $campaign_id)
    {
        $user = Auth::user();
        $request = $request->all();
        $result = $this->campaignService->getConversionStatistics($campaign_id, $request, $user->email);
        return $result;
    }
}
