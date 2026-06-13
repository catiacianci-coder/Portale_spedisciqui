<?php

use Livewire\Volt\Component;
use App\Models\comune;

new class extends Component
{
    public $search = ''; // Campo Città
    public $comuni = [];
    
    public $comune_selezionato = '';
    public $cap = '';    // Campo CAP
    public $provincia = '';

    // Cerca quando scrivi nel campo CITTÀ
    public function updatedSearch()
    { 
        if (strlen($this->search) >= 3) {
            $this->comuni = comune::where('comune', 'like', $this->search . '%')
                ->take(100)
                ->get();
        } else {
            $this->comuni = [];
        }
    }

    // Cerca quando scrivi nel campo CAP
    public function updatedCap()
    { 
        if (strlen($this->cap) >= 3) {
            $this->comuni = comune::where('cap', 'like', $this->cap . '%')
                ->take(100)
                ->get();

            // Autocompila la provincia se trova almeno un risultato
            if ($this->comuni->count() > 0) {
                $this->provincia = $this->comuni->first()->provincia;
            }

        } else {
            $this->comuni = [];
            $this->provincia = ''; 
        }
    }

    public function selezionaComune($id)
    {
        $record = comune::find($id);
        if ($record) {
            $this->comune_selezionato = $record->comune;
            $this->cap = str_pad($record->cap, 5, '0', STR_PAD_LEFT);
            $this->provincia = $record->provincia;
            
            $this->search = $record->comune;
            $this->comuni = [];
        }
    }
};
?>

<div style="position: relative; width: 100%;">
    {{-- Campo Città --}}
    <div style="margin-bottom: 15px;">
        <label>Città <span>*</span></label>
        <input type="text" 
               id="citta" 
               name="citta" 
               class="form-control" 
               placeholder="Inizia a scrivere il comune..." 
               wire:model.live="search" 
               required>

        {{-- Lista dei suggerimenti - Posizionata sopra tutto --}}
        @if(count($comuni) > 0)
            <ul class="list-group position-absolute" style="z-index: 9999; width: 100%; list-style: none; padding: 0; margin: 0; border: 1px solid #ddd; background: white; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                @foreach($comuni as $c)
                    <li class="list-group-item list-group-item-action" 
                        style="cursor: pointer; padding: 10px; border-bottom: 1px solid #eee;" 
                        wire:click="selezionaComune({{ $c->id }})">
                        {{ $c->comune }} ({{ $c->provincia }}) - {{ str_pad($c->cap, 5, '0', STR_PAD_LEFT) }}
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Riga CAP e Provincia - Flexbox semplice per evitare scrollbar orizzontali --}}
    <div style="display: flex; gap: 10px; width: 100%;">
        <div style="flex: 1;">
            <label>CAP <span>*</span></label>
            <input type="text" 
                   id="cap" 
                   name="cap" 
                   class="form-control" 
                   placeholder="CAP..."
                   wire:model.live="cap" 
                   maxlength="5" 
                   required>
        </div>
        <div style="flex: 1;">
            <label>Prov. <span>*</span></label>
            <input type="text" 
                   id="provincia" 
                   name="provincia" 
                   class="form-control" 
                   wire:model="provincia" 
                   maxlength="2" 
                   required>
        </div>
    </div>
</div>