<?php

namespace App\Http\Requests;

use App\Rules\NCIRule;
use App\Rules\TelephoneRule;
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
        $rules = [
            'type' => 'required|in:cheque,epargne',
            'soldeInitial' => 'required|numeric|min:10000',
            'devise' => 'required|in:FCFA,EUR,USD',
            'solde' => 'sometimes|numeric|min:0',
            'telephone' => ['required', new TelephoneRule()],
            'statut' => 'sometimes|in:actif,bloque,ferme',
        ];

        // Si client_id est fourni, on valide qu'il existe
        if ($this->has('client_id') && !empty($this->client_id)) {
            $rules['client_id'] = 'required|string|exists:clients,id';
        } else {
            // Sinon, on valide les informations du nouveau client
            $rules['client'] = 'required|array';
            $rules['client.titulaire'] = 'required|string|max:255';
            $rules['client.nci'] = ['required', new NCIRule()];
            $rules['client.email'] = 'required|email|unique:clients,email';
            $rules['client.telephone'] = ['required', new TelephoneRule()];
            $rules['client.adresse'] = 'nullable|string|max:500';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être : cheque ou epargne.',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être supérieur ou égal à 10 000.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.in' => 'La devise doit être : FCFA, EUR ou USD.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde doit être positif.',
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.array' => 'Les informations du client doivent être un objet.',
            'client.id.exists' => 'Le client sélectionné n\'existe pas.',
            'client.titulaire.required_if' => 'Le nom du titulaire est obligatoire pour un nouveau client.',
            'client.titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'client.nci.required_if' => 'Le numéro NCI est obligatoire pour un nouveau client.',
            'client.email.required_if' => 'L\'email est obligatoire pour un nouveau client.',
            'client.email.email' => 'L\'email doit être une adresse email valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'client.adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type' => 'type de compte',
            'soldeInitial' => 'solde initial',
            'devise' => 'devise',
            'solde' => 'solde',
            'client.id' => 'client',
            'client.titulaire' => 'nom du titulaire',
            'client.nci' => 'numéro NCI',
            'client.email' => 'email',
            'client.telephone' => 'téléphone',
            'client.adresse' => 'adresse',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Préparer les données selon le type de client
        if ($this->has('client_id') && !empty($this->client_id)) {
            // Client existant - pas besoin de client array
            $this->merge(['client' => null]);
        } elseif ($this->has('client') && is_array($this->client)) {
            // Nouveau client - s'assurer que id est null
            $this->merge([
                'client' => array_merge($this->client, ['id' => null])
            ]);
        }
    }
}
