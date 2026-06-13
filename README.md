# Portale SpedisciQui

Applicazione Laravel per preventivi, carrello, ordini e pagamenti (incluso Wallet).

**Documentazione completa** (installazione, requisiti, schema database, comandi Artisan, flussi, avvertenze): [docs/MANUALE-PROGETTO.md](docs/MANUALE-PROGETTO.md)  
**Guida debug spedizioni/resi**: [docs/GUIDA-SPEDIZIONI-DEBUG.md](docs/GUIDA-SPEDIZIONI-DEBUG.md)

Avvio rapido dopo il clone:

```bash
composer install && npm install && copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
```

Verificare in `.env` database, `APP_URL` e mail prima di andare in produzione.
