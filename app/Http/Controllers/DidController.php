<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Campaign;
use App\Http\Requests\{DidRequest};
use App\Services\DidService;
use App\Http\Resources\DidResource;
use Auth;

class DidController extends Controller
{
    /**
     * @var DidService
     */
    protected $didService;

    public function __construct(DidService $didService)
    {
        $this->didService = $didService;
    }

    /**
     * Get campaign list
     *
     * @param  App\Http\Requests\DidRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function index(DidRequest $request)
    {
        $filter = $request->all();
        $did_pool_id = $filter['domain_pool_id'];
        $result = $this->didService->getList($filter, $did_pool_id);
        return $result;
    }
}
