<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UserCollection;
use Carbon\Carbon;

class S3Service extends AbstractService
{
    /**
     * put file to s3
     *
     * @param String
     * @return array
     */

    public function putFile($folder_name, $file_name)
    {
        $destination = $folder_name.'/'.$file_name;
        return Storage::disk('s3')->put($destination, fopen(base_path().'/storage/'.$file_name, 'r+'));
    }
}
