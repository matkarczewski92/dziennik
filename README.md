# Dziennik hodowlany (Laravel 11 + Bootstrap 5 + Livewire)

Lekki panel hodowlany dla klientow hodowli wezow:
- dashboard z przypomnieniami (karmienia dzis/jutro, wazenia co 30 dni)
- pelny profil zwierzecia (`/animals/{id}`): karmienia, wazenia, wylinki, notatki, galeria
- profil zwierzecia zawiera takze zakladke genetyki (`animal_genotype`)
- import zwierzecia po `secret_tag` przez API hodowli
- panel administratora: user management, blokada/odblokowanie, impersonacja, konfiguracja systemowa

## Wymagania
- PHP 8.2+
- Composer
- Node.js 20+
- MySQL 8+

## Konfiguracja
1. Skopiuj env i ustaw klucz:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
2. Konfiguracja DB (zgodnie z `docs/DB_CONNECTION.md`):
   - `DB_CONNECTION=mysql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_DATABASE=m2531_dziennik`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=admin`
3. Zainstaluj zaleznosci:
   ```bash
   composer install
   npm install
   ```
4. Zbuduj frontend i uruchom migracje + seedy:
   ```bash
   npm run build
   php artisan migrate --seed
   php artisan storage:link
   ```

## Start (dev)
Uruchom backend i Vite:
```bash
php artisan serve
npm run dev
```

## Domyslny seed admina
Seeder tworzy konta:
- admin: `admin@dziennik.local` / `Admin12345!`
- user: `user@dziennik.local` / `User12345!`

Mozesz nadpisac admina przez env:
- `SEED_ADMIN_EMAIL`
- `SEED_ADMIN_NAME`
- `SEED_ADMIN_PASSWORD`

## Integracja API (import)
- endpoint: `GET /api/animals/{secret_tag}`
- naglowek: `X-API-KEY` (pobierany z `system_config` klucz `apiDziennik`)
- API base URL: `HODOWLA_API_BASE_URL`

## Slowniki gatunkow i genetyki (z dumpa)
- `animal_species` (odpowiednik legacy `animal_type`) jest seeded z `docs/m2531_zh.sql`
- `animals.species_id` wskazuje na `animal_species.id`
- `animal_genotype_category` jest seeded 1:1 wg dumpa
- `animal_genotype` przechowuje relacje `animal_id` -> `genotype_id` z polem typu (`v`, `h`, `p`)

## Testy
```bash
php artisan test
```

Testy sa odseparowane od glownej bazy i korzystaja z `m2531_dziennik_test` (patrz `phpunit.xml`).

Testy MVP obejmuja:
- auth (rejestracja/logowanie)
- owner scope dla animals
- admin impersonation (start + powrot)
