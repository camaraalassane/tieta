<?php

namespace App\Http\Requests;
use App\Models\Concour;
use Illuminate\Foundation\Http\FormRequest;

class ConcourIndexRequest extends FormRequest
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
            'field' => ['in:intitule,description,organisateur,avis,diplome_min,date_limite,age,statut,created_at,updated_at'],
            'order' => ['in:asc,desc'],
            'perPage' => ['numeric'],
        ];
    }
}
