<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactsRequest extends FormRequest
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
            'contact_no'=>['required','string','max:14','min:14'],
            'contact_fname'=>['required','string','max:50','min:3'],
            'contact_lname'=>['required','string','max:50','min:3'],
            'sim_contact_saved_to'=>['required','string']
        ];
    }
}
