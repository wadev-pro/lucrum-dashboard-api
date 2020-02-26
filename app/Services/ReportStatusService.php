<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Model\ReportStatus;
use Carbon\Carbon;
use Config;

class ReportStatusService extends AbstractService
{

    /**
     * get list
     *
     * @param String $user_id
     * @return array
     */

    public function getList($user_id) {
        return ReportStatus::where('user_id', $user_id)->get();
    }

    /**
     * create report status
     *
     * @param Array $filters
     * @param String $user_id
     * @return array
     */

    public function createReportStatus($type, $user_id, $filter, $status) {
        $data = array(
            'type'      => $type,
            'user_id'   => $user_id,
            'filter'    => json_encode($filter),
            'status'    => $status
        );
        return ReportStatus::create($data);
    }

    /**
     * update report status
     *
     * @param Integer $status_id
     * @param Array $data
     * @return array
     */

    public function updateReportStatus($status_id, $data) {
        return ReportStatus::where('id', $status_id)
            ->update($data);
    }
}
