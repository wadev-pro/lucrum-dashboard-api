<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Traits\HasApiResponseTrait;

class DashboardStatisticsRequest extends FormRequest
{
    use HasApiResponseTrait;
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
        return [
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',
        ];
    }
}
