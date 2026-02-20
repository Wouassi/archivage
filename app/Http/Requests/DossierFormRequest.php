<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class DossierFormRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'depense_id' => 'required|exists:depenses,id',
            'exercice_id' => 'required|exists:exercices,id',
            'imputation_id' => 'required|exists:imputations,id',
            'ordre_paiement' => 'required|string|max:50|unique:dossiers,ordre_paiement,' . ($this->route('record')?->id ?? ''),
            'date_dossier' => 'required|date',
            'beneficiaire' => 'required|string|max:255',
            'montant_engage' => 'required|numeric|min:0',
            'observations' => 'nullable|string|max:5000',
        ];
    }
}
