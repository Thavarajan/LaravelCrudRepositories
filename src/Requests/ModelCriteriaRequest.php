<?php

namespace Thavam\Repositories\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModelCriteriaRequest extends FormRequest
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
            '*.fieldname' => 'required|string',
            '*.operator' => 'required|string|max:10',
            '*.criteria' => 'required',
        ];
    }
}
