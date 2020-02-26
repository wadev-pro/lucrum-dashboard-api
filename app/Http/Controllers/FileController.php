<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FileService;
use App\Http\Requests\ExportFileRequest;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * @var FileService
     */
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Get export token
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getExportToken(Request $request)
    {
        $user_id = $request->user_id;
        $report_id = $request->report_id;
        $result = $this->fileService->createExportToken($user_id, $report_id);
        return response()->json([
            'token' => $result
        ]);
    }

    /**
     * Get export token
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportReport(Request $request)
    {
        $token = $request->token;
        $is_valid = $this->fileService->isTokenValid($token);
        if (!$is_valid) {
            return response('Token is invalid or expired');
        }

        $result = $this->fileService->getS3FileData($token);
        return Storage::disk('s3')->download($result['path'], $result['filename']);
    }

}
