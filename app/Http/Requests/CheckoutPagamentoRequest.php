<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'corriere_id' => ['required', 'integer'],
            'servizi_json' => ['nullable', 'string', 'max:8000'],
            'metodo_pagamento_id' => ['required', 'integer', 'exists:metodo_pagamento_ordinis,id'],
            'conferma_wallet' => ['sometimes', 'in:1,true,on,yes'],
            'punto_consegna_json' => ['nullable', 'string', 'max:4000'],
            'to_service_point' => ['nullable', 'integer'],
            'nome_punto' => ['nullable', 'string', 'max:255'],
            'to_post_number' => ['nullable', 'string', 'max:64'],
            'punto_street' => ['nullable', 'string', 'max:160'],
            'punto_house_number' => ['nullable', 'string', 'max:32'],
            'punto_address_line' => ['nullable', 'string', 'max:255'],
            'punto_postal_code' => ['nullable', 'string', 'max:16'],
            'punto_city' => ['nullable', 'string', 'max:120'],
            'data_ritiro' => ['nullable', 'date'],
        ];
    }
}
