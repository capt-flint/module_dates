<?php

namespace App\Modules\Dates\Http\Requests;

use App\Modules\Dates\Models\Date;
use InfyOm\Generator\Request\APIRequest;

class UpdateDateAPIRequest extends APIRequest
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
        return Date::$rules;
    }
}
