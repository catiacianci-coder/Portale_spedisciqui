@extends('layouts.app')
@section('content')
<div class="sq-sim-page">
    <div class="sq-sim-card">
        <h1 class="sq-sim-h1">Prova eamulti — GLS &amp; SDA</h1>
        <p class="sq-mb-14 sq-text-muted">
            Base <code class="sq-code">{{ $apiBase }}</code>
        </p>

        <dl class="sq-sim-list-plain sq-mb-14">
            <li>
                <strong>GLS Light</strong> #{{ $glsLight?->id ?? '13' }}
                · <code class="sq-code">{{ $glsLight?->carrier_code ?? 'gls' }}</code>
                · contratto <code class="sq-code">{{ $glsLight?->codice_servizio ?? '—' }}</code>
            </li>
            <li>
                <strong>SDA M</strong> #{{ $sdaM?->id ?? '4' }}
                · <code class="sq-code">{{ $sdaM?->carrier_code ?? 'sda' }}</code>
                · contratto <code class="sq-code">{{ $sdaM?->codice_servizio ?? '—' }}</code>
            </li>
        </dl>

        @if (! $configured)
            <p class="sq-alert sq-alert--info-warm">API key non configurata (<code class="sq-code">spedisci_online_eamulti_api_key</code>).</p>
        @endif

        @if (! empty($lastLabelGls['tracking']) || ! empty($lastLabelGls['increment_id']) || ! empty($lastLabelGls['pdf_file']))
            <p class="sq-mb-14 sq-alert sq-alert--info-warm">
                Ultima etichetta <strong>GLS</strong>:
                @if (! empty($lastLabelGls['tracking']))
                    tracking <code class="sq-code">{{ $lastLabelGls['tracking'] }}</code>
                @endif
                @if (! empty($lastLabelGls['increment_id']))
                    · increment_id <code class="sq-code">{{ $lastLabelGls['increment_id'] }}</code>
                @endif
                @if (! empty($lastLabelGls['pdf_file']))
                    · <a href="{{ route('test.spedisci-online.pdf', ['carrier' => 'gls']) }}" class="sq-link-back" target="_blank" rel="noopener">Scarica PDF etichetta GLS</a>
                @endif
            </p>
        @endif
        @if (! empty($lastLabelSda['tracking']) || ! empty($lastLabelSda['increment_id']) || ! empty($lastLabelSda['pdf_file']))
            <p class="sq-mb-14 sq-alert sq-alert--info-warm">
                Ultima etichetta <strong>SDA</strong>:
                @if (! empty($lastLabelSda['tracking']))
                    tracking <code class="sq-code">{{ $lastLabelSda['tracking'] }}</code>
                @endif
                @if (! empty($lastLabelSda['increment_id']))
                    · increment_id <code class="sq-code">{{ $lastLabelSda['increment_id'] }}</code>
                @endif
                @if (! empty($lastLabelSda['pdf_file']))
                    · <a href="{{ route('test.spedisci-online.pdf', ['carrier' => 'sda']) }}" class="sq-link-back" target="_blank" rel="noopener">Scarica PDF etichetta SDA</a>
                @endif
            </p>
        @endif

        <p class="sq-text-muted sq-mb-14">
            <strong>Cancellazione etichetta:</strong> basta il <strong>tracking</strong> (LDV dalla risposta create).
            L’<code class="sq-code">increment_id</code> è opzionale ma consigliato se presente in risposta.
        </p>
        <p class="sq-alert sq-alert--info-warm sq-mb-14">
            <strong>Ritiro:</strong> ogni click su “Ritiro” prenota un ritiro <em>reale</em> (nessuna API di annullamento documentata).
        </p>

        <form method="POST" action="{{ route('test.spedisci-online') }}" class="sq-sim-form sq-mb-24">
            @csrf
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="cap_origine"><strong>CAP origine</strong></label>
                    <input id="cap_origine" name="cap_origine" class="sq-sim-input" required
                           value="{{ old('cap_origine', $input['cap_origine']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="cap_destino"><strong>CAP destino</strong></label>
                    <input id="cap_destino" name="cap_destino" class="sq-sim-input" required
                           value="{{ old('cap_destino', $input['cap_destino']) }}">
                </div>
            </div>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="spessore"><strong>Spessore (cm)</strong></label>
                    <input id="spessore" name="spessore" class="sq-sim-input" required
                           value="{{ old('spessore', $input['spessore']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="larghezza"><strong>Larghezza (cm)</strong></label>
                    <input id="larghezza" name="larghezza" class="sq-sim-input" required
                           value="{{ old('larghezza', $input['larghezza']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="altezza"><strong>Altezza (cm)</strong></label>
                    <input id="altezza" name="altezza" class="sq-sim-input" required
                           value="{{ old('altezza', $input['altezza']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="peso"><strong>Peso (kg)</strong></label>
                    <input id="peso" name="peso" class="sq-sim-input" required step="0.01" min="0.01"
                           value="{{ old('peso', $input['peso']) }}">
                </div>
            </div>

            <p class="sq-mb-8"><strong>Mittente</strong> <span class="sq-text-muted">(obbligatorio per etichetta e ritiro)</span></p>
            <div class="sq-sim-row sq-mb-14">
                <div class="sq-sim-field">
                    <label for="mittente_nome"><strong>Nome e cognome</strong></label>
                    <input id="mittente_nome" name="mittente_nome" class="sq-sim-input" required
                           value="{{ old('mittente_nome', $input['mittente_nome']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="mittente_azienda"><strong>Azienda</strong> (opz.)</label>
                    <input id="mittente_azienda" name="mittente_azienda" class="sq-sim-input"
                           value="{{ old('mittente_azienda', $input['mittente_azienda']) }}">
                </div>
            </div>
            <div class="sq-sim-row sq-mb-14">
                <div class="sq-sim-field">
                    <label for="mittente_indirizzo"><strong>Indirizzo</strong></label>
                    <input id="mittente_indirizzo" name="mittente_indirizzo" class="sq-sim-input" required
                           value="{{ old('mittente_indirizzo', $input['mittente_indirizzo']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="mittente_telefono"><strong>Telefono</strong> (opz.)</label>
                    <input id="mittente_telefono" name="mittente_telefono" class="sq-sim-input"
                           value="{{ old('mittente_telefono', $input['mittente_telefono']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="mittente_email"><strong>Email</strong> (opz.)</label>
                    <input id="mittente_email" name="mittente_email" type="email" class="sq-sim-input"
                           value="{{ old('mittente_email', $input['mittente_email']) }}">
                </div>
            </div>

            <p class="sq-mb-8"><strong>Destinatario</strong> <span class="sq-text-muted">(obbligatorio per etichetta)</span></p>
            <div class="sq-sim-row sq-mb-14">
                <div class="sq-sim-field">
                    <label for="destinatario_nome"><strong>Nome e cognome</strong></label>
                    <input id="destinatario_nome" name="destinatario_nome" class="sq-sim-input" required
                           value="{{ old('destinatario_nome', $input['destinatario_nome']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="destinatario_azienda"><strong>Azienda</strong> (opz.)</label>
                    <input id="destinatario_azienda" name="destinatario_azienda" class="sq-sim-input"
                           value="{{ old('destinatario_azienda', $input['destinatario_azienda']) }}">
                </div>
            </div>
            <div class="sq-sim-row sq-mb-14">
                <div class="sq-sim-field">
                    <label for="destinatario_indirizzo"><strong>Indirizzo</strong></label>
                    <input id="destinatario_indirizzo" name="destinatario_indirizzo" class="sq-sim-input" required
                           value="{{ old('destinatario_indirizzo', $input['destinatario_indirizzo']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="destinatario_telefono"><strong>Telefono</strong> (opz.)</label>
                    <input id="destinatario_telefono" name="destinatario_telefono" class="sq-sim-input"
                           value="{{ old('destinatario_telefono', $input['destinatario_telefono']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="destinatario_email"><strong>Email</strong> (opz.)</label>
                    <input id="destinatario_email" name="destinatario_email" type="email" class="sq-sim-input"
                           value="{{ old('destinatario_email', $input['destinatario_email']) }}">
                </div>
            </div>
            <div class="sq-sim-row sq-mb-14">
                <div class="sq-sim-field">
                    <label for="note_spedizione"><strong>Note spedizione</strong> (opz.)</label>
                    <input id="note_spedizione" name="note_spedizione" class="sq-sim-input"
                           value="{{ old('note_spedizione', $input['note_spedizione']) }}">
                </div>
            </div>

            <details class="sq-mb-14">
                <summary><strong>Tracking / cancellazione</strong> (auto dopo create)</summary>
                <div class="sq-sim-row sq-mt-14">
                    <div class="sq-sim-field">
                        <label for="tracking_numero"><strong>Tracking (LDV)</strong></label>
                        <input id="tracking_numero" name="tracking_numero" class="sq-sim-input"
                               placeholder="Da risposta create"
                               value="{{ old('tracking_numero', $input['tracking_numero']) }}">
                    </div>
                    <div class="sq-sim-field">
                        <label for="increment_id"><strong>increment_id</strong> (opz.)</label>
                        <input id="increment_id" name="increment_id" class="sq-sim-input"
                               placeholder="Id numerico da create"
                               value="{{ old('increment_id', $input['increment_id']) }}">
                    </div>
                </div>
            </details>

            <details class="sq-mb-14">
                <summary><strong>Ritiro</strong> (data, ora, colli)</summary>
                <div class="sq-sim-row sq-mt-14">
                    <div class="sq-sim-field">
                        <label for="data_ritiro"><strong>Data ritiro</strong></label>
                        <input id="data_ritiro" name="data_ritiro" type="date" class="sq-sim-input"
                               value="{{ old('data_ritiro', $input['data_ritiro']) }}">
                    </div>
                    <div class="sq-sim-field">
                        <label for="ora_inizio"><strong>Ora inizio</strong></label>
                        <input id="ora_inizio" name="ora_inizio" class="sq-sim-input" placeholder="09:00"
                               value="{{ old('ora_inizio', $input['ora_inizio']) }}">
                    </div>
                    <div class="sq-sim-field">
                        <label for="colli"><strong>Colli</strong></label>
                        <input id="colli" name="colli" type="number" min="1" class="sq-sim-input"
                               value="{{ old('colli', $input['colli']) }}">
                    </div>
                </div>
                <div class="sq-sim-row">
                    <div class="sq-sim-field">
                        <label for="note_ritiro"><strong>Note ritiro</strong> (opz.)</label>
                        <input id="note_ritiro" name="note_ritiro" class="sq-sim-input"
                               value="{{ old('note_ritiro', $input['note_ritiro']) }}">
                    </div>
                </div>
                <p class="sq-text-muted sq-mt-14">
                    Il ritiro usa l’indirizzo <strong>mittente</strong> sopra. Se presente, il tracking viene inviato come <code class="sq-code">shipmentId</code>.
                </p>
            </details>

            <div class="sq-mb-14">
                <p class="sq-mb-8"><strong>GLS Light</strong></p>
                <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                    <button type="submit" name="azione" value="preventivo_gls" class="sq-sim-btn">Preventivo GLS</button>
                    <button type="submit" name="azione" value="create_gls" class="sq-sim-btn">Crea etichetta GLS</button>
                    <button type="submit" name="azione" value="delete_gls" class="sq-sim-btn">Cancella etichetta GLS</button>
                    <button type="submit" name="azione" value="pickup_gls" class="sq-sim-btn">Ritiro GLS</button>
                </div>
            </div>

            <div class="sq-mb-14">
                <p class="sq-mb-8"><strong>SDA M</strong></p>
                <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                    <button type="submit" name="azione" value="preventivo_sda" class="sq-sim-btn">Preventivo SDA</button>
                    <button type="submit" name="azione" value="create_sda" class="sq-sim-btn">Crea etichetta SDA</button>
                    <button type="submit" name="azione" value="delete_sda" class="sq-sim-btn">Cancella etichetta SDA</button>
                    <button type="submit" name="azione" value="pickup_sda" class="sq-sim-btn">Ritiro SDA</button>
                </div>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                <button type="submit" name="azione" value="tracking" class="sq-sim-btn">Tracking</button>
            </div>
        </form>

        @if ($searched ?? false)
            <hr class="sq-sim-hr">
            <h2 class="sq-sim-h2">Risultato</h2>
            @if (! empty($result['corriereLabel']))
                <p class="sq-text-muted sq-mb-14">Corriere: <strong>{{ $result['corriereLabel'] }}</strong></p>
            @endif
            @if (! empty($result['summary']))
                <p class="sq-mb-14"><strong>{{ $result['summary'] }}</strong></p>
            @endif
            @if (! empty($result['pdfUrl']))
                <p class="sq-mb-14">
                    <a href="{{ $result['pdfUrl'] }}" class="sq-sim-btn" target="_blank" rel="noopener">Scarica PDF etichetta</a>
                    <span class="sq-text-muted"> — stampala e consegnala al corriere al ritiro.</span>
                </p>
            @endif
            @if (! empty($result['pdfWarning']))
                <p class="sq-alert sq-alert--info-warm sq-mb-14">{{ $result['pdfWarning'] }}</p>
            @endif
            @if (! empty($result['errorMessage']))
                <p class="sq-alert sq-alert--info-warm sq-mb-14">{{ $result['errorMessage'] }}</p>
            @endif
            <p class="sq-text-muted sq-mb-14">
                {{ $result['method'] ?? '—' }}
                <code class="sq-code">{{ $result['endpoint'] ?? '—' }}</code>
                · HTTP {{ $result['httpStatus'] ?? '—' }}
            </p>

            @if (str_starts_with((string) ($result['azione'] ?? ''), 'preventivo_') && ! empty($result['preventivo']['rates']))
                <details class="sq-mb-14">
                    <summary><strong>Tariffe in risposta</strong></summary>
                    <pre class="sq-pre-json">{{ json_encode($result['preventivo']['rates'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif

            @if (! empty($result['payload']))
                <details class="sq-mb-14">
                    <summary><strong>JSON inviato</strong></summary>
                    <pre class="sq-pre-json">{{ json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif

            @if (! empty($result['rawBody']))
                <details class="sq-mb-14">
                    <summary><strong>JSON ricevuto</strong></summary>
                    <pre class="sq-pre-json">{{ $result['rawBody'] }}</pre>
                </details>
            @endif
        @endif
    </div>
</div>
@endsection
