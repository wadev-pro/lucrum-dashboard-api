<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Campaign;
use App\Http\Requests\{UserListRequest, UserRequest};
use App\Services\UserService;
use App\Http\Resources\{UserCollection, UserResource};
use App\Traits\UserTrait;
use Auth;

class UserController extends Controller
{
    use UserTrait;

    /**
     * @var UserService
     */
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get user list
     *
     * @param  App\Http\Requests\UserListRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function index(UserListRequest $request)
    {
        $user = Auth::user();
        if (!$this->isAdmin($user))
        {
            return response()->json([
                'result' => 'Permission denied'
            ], 403);
        }
        $filter = $request->all();
        $result = $this->userService->getList($filter);
        return new UserCollection($result);
    }

    /**
     * Return user object
     *
     * @param  Illuminate\Http\Request  $request
     * @param  Number  $user_id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$user_id)
    {
        $user = Auth::user();
        if (!$this->isAdmin($user))
        {
            return response()->json([
                'result' => 'Permission denied'
            ], 403);
        }
        $result = $this->userService->get($user_id);
        return new UserResource($result);
    }

    /**
     * Create user info
     *
     * @param  App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $user = Auth::user();
        if (!$this->isAdmin($user))
        {
            return response()->json([
                'result' => 'Permission denied'
            ], 403);
        }
        $data = $request->all();
        $result = $this->userService->create($data);
        return new UserResource($result);
    }

    /**
     * Update user info
     *
     * @param  Illuminate\Http\Request  $request
     * @param  Number  $user_id
     */
    public function update(Request $request, $user_id)
    {
        $user = Auth::user();
        if (!$this->isAdmin($user))
        {
            return response()->json([
                'result' => 'Permission denied'
            ], 403);
        }
        $data = $request->all();
        $result = $this->userService->update($user_id, $data);
        return new UserResource($result);
    }

    /**
     * Delete user
     *
     * @param  Illuminate\Http\Request  $request
     * @param  Number  $user_id
     */
    public function destroy(Request $request, $user_id)
    {
        $user = Auth::user();
        if (!$this->isAdmin($user))
        {
            return response()->json([
                'result' => 'Permission denied'
            ], 403);
        }
        $result = $this->userService->delete($user_id);
        return response()->json([
            'result' => 'User has been successfully removed.'
        ]);
    }
}
