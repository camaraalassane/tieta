<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConcourUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'intitule'              => 'required|string|max:255',
            'description'           => 'required|string|max:255',
            'organisateur'          => 'required|string|max:255',
            'avis'                  => ['required'],
            'diplome_min'           => ['required'],
            'date_limite'           => 'required|date',
            'age'                   => ['required'],
            'statut'                =>  'required|string|max:255',
        ];
    }
}
