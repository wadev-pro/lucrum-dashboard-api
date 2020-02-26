<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Route;
use App\Http\Requests\Traits\HasApiResponseTrait;
use App\Http\Requests\Traits\RequestValidationTrait;
use App\Services\UserService;

class UserRequest extends FormRequest
{
    use HasApiResponseTrait;
    use RequestValidationTrait;

    protected $validationRules = [
        'email'         => 'required|email',
        'name'          => 'required|min:1|max:255',
        'first_name'    => 'required|min:1|max:255',
        'last_name'     => 'required|min:1|max:255',
        'role'          => 'required|numeric|in:1,2'
    ];
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return $this->{$this->getCallableValidationMethod()}();
    }

    /**
     *
     * @return array
     */
    private function rulesPost(): array
    {
        $rules = [
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:1|max:255',
        ];

        return array_merge($this->validationRules, $rules);
    }

    /**
     *
     * @return array
     */
    private function rulesPut(): array
    {
        $id = Route::current()->parameter('id');
        $userService = new UserService();
        $user = $userService->get($id);

        $rules = [
            'email'     => ['required', Rule::unique('users')->ignore($user['email'], 'email')],
            'password'  => 'sometimes|string',
        ];

        return array_merge($this->validationRules, $rules);
    }
}
