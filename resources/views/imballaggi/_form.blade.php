@php
    $imb = $imballaggio ?? null;
@endphp
<div style="display: grid; gap: 16px; max-width: 520px;">
    <div>
        <label for="nome" style="display:block; font-weight:700; color:#ff6600; margin-bottom:6px;">Nome</label>
        <input id="nome" name="nome" type="text" required maxlength="120" value="{{ old('nome', $imb->nome ?? '') }}"
               style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box;">
        @error('nome') <span style="color:#b42318; font-size:13px;">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="id_tipo_spediziones" style="display:block; font-weight:700; color:#ff6600; margin-bottom:6px;">Tipo spedizione</label>
        <select id="id_tipo_spediziones" name="id_tipo_spediziones" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px;">
            @foreach ($tipi as $t)
                <option value="{{ $t->id }}" @selected(old('id_tipo_spediziones', $imb->id_tipo_spediziones ?? null) == $t->id)>{{ $t->tipo_spedizione }}</option>
            @endforeach
        </select>
        @error('id_tipo_spediziones') <span style="color:#b42318; font-size:13px;">{{ $message }}</span> @enderror
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div>
            <label for="altezza" style="display:block; font-weight:600; margin-bottom:6px;">Altezza (cm)</label>
            <input id="altezza" name="altezza" type="number" step="0.01" min="0.01" required value="{{ old('altezza', $imb->altezza ?? '') }}"
                   style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box;">
            @error('altezza') <span style="color:#b42318; font-size:13px;">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="larghezza" style="display:block; font-weight:600; margin-bottom:6px;">Larghezza (cm)</label>
            <input id="larghezza" name="larghezza" type="number" step="0.01" min="0.01" required value="{{ old('larghezza', $imb->larghezza ?? '') }}"
                   style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box;">
            @error('larghezza') <span style="color:#b42318; font-size:13px;">{{ $message }}</span> @enderror
        </div>
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div>
            <label for="spessore" style="display:block; font-weight:600; margin-bottom:6px;">Spessore (cm)</label>
            <input id="spessore" name="spessore" type="number" step="0.01" min="0.01" required value="{{ old('spessore', $imb->spessore ?? '') }}"
                   style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box;">
            @error('spessore') <span style="color:#b42318; font-size:13px;">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="peso" style="display:block; font-weight:600; margin-bottom:6px;">Peso (kg)</label>
            <input id="peso" name="peso" type="number" step="0.01" min="0.01" required value="{{ old('peso', $imb->peso ?? '') }}"
                   style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box;">
            @error('peso') <span style="color:#b42318; font-size:13px;">{{ $message }}</span> @enderror
        </div>
    </div>
</div>
