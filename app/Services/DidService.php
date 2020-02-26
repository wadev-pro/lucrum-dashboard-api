<?php

namespace App\Services;

use App\Model\{Did, MessageGatewayProvider };
use Illuminate\Support\Facades\DB;
use App\Http\Resources\DidCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class DidService extends AbstractService
{
    /**
     * get did list
     *
     * @param Array $filters
     * @param String $did_pool_id
     * @return array
     */

    public function getList($filters, $did_pool_id)
    {
        $result = [];
        $db_result = [];
        $page  = isset($filters['page']) ? $filters['page'] : 1;
        $per_page = isset($filters['per_page']) ? $filters['per_page'] : 10;
        $offset = ($page - 1) * $per_page;

        $query = Did::where('did_pool_id', $did_pool_id);

		if(!empty($filters['search'])) {
			$searchValue = $filters['search'];
            $query = $query->where( function($wquery) use($searchValue) {
                $wquery->where('did_code', 'ilike', strtolower($searchValue).'%')
                    ->orWhere('activated_at', 'ilike', strtolower($searchValue).'%')
                    ->orWhere('deactivated_at', 'ilike', strtolower($searchValue).'%')
                    ->orWhere('deactivation_reason', 'ilike', strtolower($searchValue).'%')
                    ->orWhere('reactivated_at', 'ilike', strtolower($searchValue).'%');
            });
		}

		$num_results_filtered= $query->count();

        if (isset($filters['order_by'])) {
            $order_dir = $filters['order_dir'] ? $filters['order_dir'] : 'desc';
            $query = $query->orderBy($filters['order_by'], $order_dir);
        } else {
            $query = $query->orderBy('created_at', 'desc');
        }

        $query = $query->offset($offset)->limit($per_page);

		$dids = $query->get()->toArray();
        $count = $offset;

        $result = new LengthAwarePaginator($dids, $num_results_filtered, $per_page, $page);
        $result->setPath(route('dids.index'));
        $result = new DidCollection($result);
        return $result;
    }
}
