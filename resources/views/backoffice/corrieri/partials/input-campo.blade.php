@php
    $inputName = $name ?? 'values['.$corriere->id.']';
    $inputId = ($idPrefix ?? 'campo').'-'.$corriere->id;
    $oldKey = preg_match('/^values\[(\d+)\]$/', (string) $inputName, $m) ? 'values.'.$m[1] : $inputName;
    $val = old($oldKey, $corriere->{$campoKey} ?? '');
@endphp

@switch($meta['type'])
    @case('boolean')
        <select id="{{ $inputId }}" name="{{ $inputName }}" class="sq-bo-corrieri-inp">
            <option value="0" @selected(! (bool) $val)>No</option>
            <option value="1" @selected((bool) $val)>Sì</option>
        </select>
        @break

    @case('select_tipo_od')
        <select id="{{ $inputId }}" name="{{ $inputName }}" class="sq-bo-corrieri-inp">
            @foreach ($tipoOdOptions as $opt)
                <option value="{{ $opt }}" @selected((string) $val === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
        @break

    @case('ricarico')
        <select id="{{ $inputId }}" name="{{ $inputName }}" class="sq-bo-corrieri-inp">
            <option value="">—</option>
            @foreach ($ricarichi as $r)
                <option value="{{ $r->id }}" @selected((int) $val === (int) $r->id)>
                    #{{ $r->id }} ({{ $r->percentuale }}%)
                </option>
            @endforeach
        </select>
        @break

    @case('integer')
        <input id="{{ $inputId }}" type="number" step="1" min="0" name="{{ $inputName }}" class="sq-bo-corrieri-inp"
               value="{{ $val === null || $val === '' ? '' : (int) $val }}">
        @break

    @case('decimal')
        <input id="{{ $inputId }}" type="number" step="0.01" min="0" name="{{ $inputName }}" class="sq-bo-corrieri-inp"
               value="{{ $val === null || $val === '' ? '' : $val }}">
        @break

    @case('textarea')
        <textarea id="{{ $inputId }}" name="{{ $inputName }}" rows="2" class="sq-bo-corrieri-inp sq-bo-corrieri-inp--area">{{ $val }}</textarea>
        @break

    @default
        <input id="{{ $inputId }}" type="text" name="{{ $inputName }}" class="sq-bo-corrieri-inp" value="{{ $val }}">
@endswitch

@error($oldKey)
    <div class="sq-field-error">{{ $message }}</div>
@enderror
