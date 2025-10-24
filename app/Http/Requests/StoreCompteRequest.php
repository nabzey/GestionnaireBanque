<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompteRequest extends FormRequest
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
            'solde_initial' => 'nullable|numeric|min:0',
            'devise' => 'nullable|string|in:FCFA,EUR,USD',
            'type' => 'required|string|in:cheque,courant,epargne',
            'admin_id' => 'required|exists:admins,id',
        ];
    }
}
