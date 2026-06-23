<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PagamentoOrdineCartaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ordine = $this->route('ordine');

        return $ordine !== null && $this->user()?->can('pay', $ordine);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'metodo_pagamento_id' => ['required', 'integer', 'exists:metodo_pagamento_ordinis,id'],
            'payment_method_id' => ['required_without:payment_intent_id', 'nullable', 'string', 'max:255'],
            'payment_intent_id' => ['required_without:payment_method_id', 'nullable', 'string', 'max:255'],
        ];
    }
}
