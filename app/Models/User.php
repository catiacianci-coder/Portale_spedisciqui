<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Anagrafica;
use App\Models\UserImballaggio;
use App\Models\destinatario;
use App\Models\mittenza;
use App\Models\wallet_movimento;
use App\Models\wallet_saldo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Support\UserPostingEnablement;
use Illuminate\Support\Str;

class User extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    use CanResetPassword, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'tipo_utente',
        'autoriza_debito_wallet',
        'is_liccardi',
        'is_account_disabled',
        'postagem_bloqueado_pelo_bo',
        'mark',
        'varie_1',
        'account_cancelled_at',
        'carrello_json',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'autoriza_debito_wallet' => 'boolean',
            'is_liccardi' => 'boolean',
            'is_account_disabled' => 'boolean',
            'postagem_bloqueado_pelo_bo' => 'boolean',
            'account_cancelled_at' => 'datetime',
            'carrello_json' => 'array',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withTimestamps();
    }

    public function hasRole(string $nome): bool
    {
        return $this->roles()->where('nome', $nome)->exists();
    }

    /** Ruoli che possono entrare nell’area back-office. */
    public const BACKOFFICE_ROLE_NAMES = ['super_user', 'assistenza', 'contabile'];

    public function canAccessBackoffice(): bool
    {
        return $this->roles()->whereIn('nome', self::BACKOFFICE_ROLE_NAMES)->exists();
    }

    /** Tutte le pagine back-office (solo super_user per ora). */
    public function canAccessBackofficeFull(): bool
    {
        return $this->hasRole(Role::nomeSuperUser());
    }

    /**
     * Anagrafica attiva (unica per utente: indirizzo e dati fatturazione correnti).
     */
    public function anagrafica(): HasOne
    {
        return $this->hasOne(Anagrafica::class, 'user_id')->where('attivo', true);
    }

    public function anagrafiche(): HasMany
    {
        return $this->hasMany(Anagrafica::class, 'user_id')->orderByDesc('id');
    }

    public function imballaggi(): HasMany
    {
        return $this->hasMany(UserImballaggio::class, 'user_id');
    }

    public function mittenze(): HasMany
    {
        return $this->hasMany(mittenza::class, 'user_id');
    }

    public function destinatari(): HasMany
    {
        return $this->hasMany(destinatario::class, 'user_id');
    }

    public function ordini(): HasMany
    {
        return $this->hasMany(ordine::class, 'user_id');
    }

    public function spedizioni(): HasMany
    {
        return $this->hasMany(spedizione::class, 'user_id');
    }

    public function walletSaldo(): HasOne
    {
        return $this->hasOne(wallet_saldo::class, 'user_id');
    }

    public function walletMovimenti(): HasMany
    {
        return $this->hasMany(wallet_movimento::class, 'user_id');
    }

    public function walletRicaricheRichieste(): HasMany
    {
        return $this->hasMany(wallet_ricarica_richiesta::class, 'user_id');
    }

    public function ncPratiche(): HasMany
    {
        return $this->hasMany(nc_pratica::class, 'user_id');
    }

    public function walletSaldoAsFloat(): float
    {
        if ($this->relationLoaded('walletSaldo')) {
            return round((float) ($this->walletSaldo?->saldo ?? 0), 2);
        }

        return round((float) ($this->walletSaldo()->value('saldo') ?? 0), 2);
    }

    public function isPostingEnabled(): bool
    {
        return ! (bool) $this->is_account_disabled;
    }

    /**
     * @return array{abbr: string, class: string, title: string}|null
     */
    public function postingBlockedBadgeMeta(): ?array
    {
        if (! (bool) $this->is_account_disabled) {
            return null;
        }

        return UserPostingEnablement::badgeMeta($this);
    }

    public function displayNameForBackoffice(): string
    {
        $a = $this->relationLoaded('anagrafica') ? $this->anagrafica : $this->anagrafica()->first();
        if ($a) {
            $denom = trim((string) ($a->denominazione_ragione_sociale ?? ''));
            if ($denom !== '') {
                return $denom;
            }
            $nc = trim(trim((string) ($a->nome ?? '')).' '.trim((string) ($a->cognome ?? '')));
            if ($nc !== '') {
                return $nc;
            }
        }

        return $this->headerDisplayName();
    }

    /**
     * Nome da mostrare in header (ragione sociale, nome e cognome, oppure parte locale email).
     */
    public function headerDisplayName(): string
    {
        $a = $this->anagrafica;
        if ($a) {
            $denom = trim((string) ($a->denominazione_ragione_sociale ?? ''));
            if ($denom !== '') {
                return $denom;
            }
            $nomeCognome = trim(trim((string) ($a->nome ?? '')).' '.trim((string) ($a->cognome ?? '')));
            if ($nomeCognome !== '') {
                return $nomeCognome;
            }
        }

        return Str::before((string) $this->email, '@') ?: 'Utente';
    }

    /**
     * Stessa logica di {@see headerDisplayName()} ma max 10 caratteri + "..." se più lungo.
     */
    public function headerDisplayNameShort(): string
    {
        $raw = $this->headerDisplayName();
        if (mb_strlen($raw) <= 10) {
            return $raw;
        }

        return mb_substr($raw, 0, 10).'...';
    }
}