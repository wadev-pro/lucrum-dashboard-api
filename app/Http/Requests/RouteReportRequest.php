<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RouteReportRequest extends FormRequest
{
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
            'user_id'         => 'required|email',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date',
            'page'          => 'sometimes|required|integer|min:0',
            'per_page'      => 'sometimes|required|integer|min:0',
            'order_by'      => 'sometimes|required|string',
            'order_dir'     => 'sometimes|required|in:asc,desc',
            'filter'        => 'sometimes|json',
        ];
    }
}
