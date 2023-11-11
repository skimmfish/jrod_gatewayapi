<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimModuleRequest extends FormRequest
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
     * @bodyParam sim_number string required Example: +16302901990
     * @bodyParam sim_port_number Integer required unique: Example: 1,2
     * @bodyParam current_port_state Integer required Default: 1
     */
    public function rules()
    {
        return [
        'sim_number'=>['required','min:11','max:14','unique:sim_modules'],
        'sim_port_number'=>['required','max:1','unique:sim_modules'],
        'current_port_state'=>['required','max:1'],
        ];
    }
}
