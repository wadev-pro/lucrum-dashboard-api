<?php

namespace App\Services;

use App\Model\{User, MessageGatewayProvider };
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class UserService extends AbstractService
{
    /**
     * get user list
     *
     * @param Array $filters
     * @return array
     */


    public function getList($filters)
    {
        $result = [];
        $db_result = [];
        $page  = isset($filters['page']) ? $filters['page'] : 1;
        $per_page = isset($filters['per_page']) ? $filters['per_page'] : 10;
        $offset = ($page - 1) * $per_page;

        $query = User::whereRaw('1=1');

        if(!empty($filters['search'])) {
            $searchValue = $filters['search'];
            $query = $query->where( function($wquery) use($searchValue) {
                $wquery->where('name', 'like', strtolower($searchValue).'%')
                    ->orWhere('first_name', 'like', strtolower($searchValue).'%')
                    ->orWhere('last_name', 'like', strtolower($searchValue).'%')
                    ->orWhere('email', 'like', strtolower($searchValue).'%')
                    ->orWhere('created_at', 'like', strtolower($searchValue).'%')
                    ->orWhere('updated_at', 'like', strtolower($searchValue).'%');
            });
        }
        $num_results_filtered = $query->count();
        if (isset($filters['order_by'])) {
            $order_dir = $filters['order_dir'] ? $filters['order_dir'] : 'desc';
            $query = $query->orderBy($filters['order_by'], $order_dir);
        } else {
            $query = $query->orderBy('created_at', 'desc');
        }

        $query = $query->offset($offset)->limit($per_page);

        $users = $query->get();
        $count = $offset;

        $result = new LengthAwarePaginator($users, $num_results_filtered, $per_page, $page);
        $result->setPath(route('users.index'));
        return $result;
    }

    /**
     * get user info
     *
     * @param String $user_id
     * @return array
     */

    public function get($user_id)
    {
        $result = User::find($user_id);
        return $result;
    }

    /**
     * create user
     *
     * @param Array $data
     * @return array
     */

    public function create($data)
    {
        $data['password'] = \bcrypt($data['password']);
        $result = User::create($data);
        return $result;
    }

    /**
     * update user
     *
     * @param Array $data
     * @param String $user_id
     * @return array
     */

    public function update($user_id, $data)
    {
        if (isset($data['password']))
        {
            $data['password'] = \bcrypt($data['password']);
        }
        $user = User::find($user_id);
        $user->update($data);
        return $user;
    }

    /**
     * delete user
     *
     * @param Array $data
     * @param String
     * @return array
     */

    public function delete($user_id)
    {
        $user = User::find($user_id);
        if ($user) {
            $result = $user->delete();
        } else {
            $result = false;
        }
        return $result;
    }
}
