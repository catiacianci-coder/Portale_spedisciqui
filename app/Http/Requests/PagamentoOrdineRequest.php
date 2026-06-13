<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PagamentoOrdineRequest extends FormRequest
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
            'conferma_wallet' => ['sometimes', 'in:1,true,on,yes'],
        ];
    }
}
