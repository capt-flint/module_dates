<?php

namespace App\Modules\Dates\Http\Requests;

use InfyOm\Generator\Request\APIRequest;

class SaveCalendarAPIRequest extends APIRequest
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
            'dates' => 'required|array',
            'range'=> 'required|array|max:2',
        ];
    }

    /**
     * @return array
     */
    protected function validationData()
    {
        return $this->json()->all();
    }
}
