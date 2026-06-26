<?php

namespace App\Http\Requests;

use App\Models\wallet_ricarica_richiesta;
use Illuminate\Foundation\Http\FormRequest;

class WalletRicaricaPagamentoCartaRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var wallet_ricarica_richiesta|null $ricarica */
        $ricarica = $this->route('ricarica');

        return $ricarica !== null
            && (int) $this->user()?->id === (int) $ricarica->user_id
            && $ricarica->stato === 'in_attesa';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'metodo_pagamento_id' => ['required', 'integer', 'exists:metodo_pagamento_wallet_ricariches,id'],
            'payment_method_id' => ['required_without:payment_intent_id', 'nullable', 'string', 'max:255'],
            'payment_intent_id' => ['required_without:payment_method_id', 'nullable', 'string', 'max:255'],
        ];
    }
}
