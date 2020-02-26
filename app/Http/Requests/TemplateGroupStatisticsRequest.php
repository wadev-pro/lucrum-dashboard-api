<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TemplateGroupStatisticsRequest extends FormRequest
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
            'group_id'      => 'required|exists:pgsql.message_template_group,group_id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date',
            'page'          => 'sometimes|required|integer|min:0',
            'per_page'      => 'sometimes|required|integer|min:0',
            'search'        => 'sometimes|string',
        ];
    }
}
