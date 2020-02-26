<?php

namespace App\Services;

use App\Model\{FileToken, ReportStatus };
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserCollection;
use App\Services\ReportsService;
use App\Enums\ReportType;
use Carbon\Carbon;
use Config;

class FileService extends AbstractService
{
    /**
     * @var ReportsService
     */
    protected $reportService;

    public function __construct(ReportsService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * created file export token
     *
     * @param String $user_id
     * @param Integer $type
     * @param Array $filters
     * @return array
     */


    public function createExportToken($user_id, $report_id)
    {
        $token = md5($user_id.time());
        $data = array(
            'report_id' => $report_id,
            'token' => $token
        );
        FileToken::create($data);
        return $token;
    }

    /**
     * Check if token is valid
     *
     * @param String $token
     * @return array
     */


    public function isTokenValid($token)
    {
        $expire_time = Config::get('constants.Export.expire_time') ? Config::get('constants.Export.expire_time') : 60;
        $db_result = FileToken::where('token', $token)
            ->where('created_at', '>', Carbon::now()->subMinutes($expire_time)->toDateTimeString())->get();
        if (!count($db_result)) {
            return false;
        }
        return true;
    }

    /**
     * get s3 file data
     *
     * @param String $token
     * @return array
     */


    public function getS3FileData($token)
    {
        $expire_time = Config::get('constants.Export.expire_time') ? Config::get('constants.Export.expire_time') : 60;
        $db_result = FileToken::where('token', $token)
            ->where('created_at', '>', Carbon::now()->subMinutes($expire_time)->toDateTimeString())->get();
        if (!count($db_result)) {
            return null;
        }
        $tokenObj = $db_result[0];
        $report_id = $tokenObj->report_id;
        $reportStatustItem = ReportStatus::find($report_id);
        $filename = $reportStatustItem->filename;
        $folder = Config::get('constants.S3.lead_folder') ? Config::get('constants.S3.lead_folder') : 'lead';
        $path = $folder.'/'.$filename;
        return array(
            'path' => $path,
            'filename' => $filename
        );
    }
}
