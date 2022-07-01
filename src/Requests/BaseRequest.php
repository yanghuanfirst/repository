<?php

namespace Yang\Repository\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * @param Validator $validator
     * 重写父类返回错误信息的方法，
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => -1,
            'message' => $validator->errors()->first()
        ]));
    }
}
