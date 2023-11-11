<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name'=>['required','min:3','max:40'],
            'username'=>['required','string','min:6','max:12','unique:users'],
            'email'=>['required','string','unique:users'],
            'password'=>['required','string','min:6','max:15','confirmed']
        ];

    }
}
