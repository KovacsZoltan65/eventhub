# EventHub – Monorepo (Backend + Frontend)

Egyszerű eseménykezelő és jegyfoglaló rendszer három szerepkörrel (**admin**, **organizer**, **user**).  
A repo két fő részből áll:

```
eventhub/
├─ backend/   # Laravel API (Sanctum + REST)
└─ frontend/  # Vue 3 + Pinia + Vue Router (Vite)
```

## Fő funkciók

- **Publikus** eseménylista: keresés/szűrés/pagináció, csak `published` események.
- **Jegyfoglalás** (alap): mennyiség választása, készletkezelés (backenden — tranzakció, ütközés ellen).
- **Szerepkörök és jogosultságok**:
  - **admin**: felhasználók listázása, tiltás/engedélyezés, minden entitás elérése.
  - **organizer**: saját események CRUD, kapacitás/publikálás.
  - **user/guest**: böngészés, foglalás.
- **Hitelesítés**: e-mail + jelszó (hash), Laravel Sanctum session cookie-val.
- **Biztonság**: input validáció, CORS + CSRF beállítások.
- **Teljesítmény**: szerveroldali pagináció, indexek.
- **Loggolás**: JSON (INFO/ERROR).

---

## Gyorsindító (helyi környezet)

### 0) Követelmények
- Node 18+ (ajánlott LTS), npm
- PHP 8.2+, Composer
- PostgreSQL 14+ (vagy más, amit `.env`-ben beállítasz)

> *Docker Compose példa lentebb található, ha konténerben futtatnád.*

### 1) Backend (Laravel API)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

`.env` főbb beállítások (lokális fejlesztéshez):

```env
APP_URL=http://localhost:8000

# Adatbázis
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=eventhub
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Session / Sanctum
SESSION_DRIVER=file
SESSION_DOMAIN=localhost
SESSION_COOKIE=eventhub-session
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
```

**CORS** – `config/cors.php` (fejlesztésre javasolt):

```php
return [
  'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
  'allowed_methods' => ['*'],
  'allowed_origins' => ['http://localhost:5173'],
  'allowed_origins_patterns' => [],
  'allowed_headers' => ['*'],
  'exposed_headers' => ['*'],
  'max_age' => 0,
  'supports_credentials' => true,
];
```

Migrációk + seederek:

```bash
php artisan migrate:fresh --seed
```

**Seed felhasználók (példa):**
- admin: `admin@eventhub.local` / `Admin123!`
- organizer: `org@eventhub.local` / `Org123!`
- user: `user@eventhub.local` / `User123!`

API indítása (8000-es port):

```bash
php artisan serve --host=localhost --port=8000
```

### 2) Frontend (Vue 3 + Vite)

```bash
cd ../frontend
npm install
```

**API URL beállítása**  
A projekt jelenlegi állapotában a **`src/helpers/constants.js`** fájlban található a `CONFIG.BASE_URL`:

```js
export const CONFIG = {
  BASE_URL: "http://localhost:8000/api/",
};
```

> (Opcionális) Vite környezeti változóval is megoldható: `VITE_API_BASE=http://localhost:8000/api/`  
> és a `constants.js` olvassa az `import.meta.env.VITE_API_BASE`-t – ez a bővítés később egyszerűsíti a staging/prod buildet.

Dev szerver (5173-as port):

```bash
npm run dev
```

Nyisd meg: <http://localhost:5173>  
- Ha nincs belépve felhasználó: **felhasználó: Guard**, **Login** gomb.  
- Sikeres bejelentkezés után: **felhasználó: {név}**, **Logout** gomb, és az **events** oldal töltődik.

---

## Auth / CSRF röviden (Sanctum)

- **Frontend**: bejelentkezés előtt **GET `/sanctum/csrf-cookie`** (same origin: `http://localhost:8000`), majd **POST `/login`**.
- **CORS**: pontos origin (nem `*`), `supports_credentials=true`.
- **Sütik**: `XSRF-TOKEN` (nem HttpOnly) + `eventhub-session` (HttpOnly).
- **Tipikus 419 (CSRF mismatch) okok**: nincs `X-XSRF-TOKEN` header; nem egyezik az origin; hiányzik `SESSION_DOMAIN=localhost`; a frontend 127.0.0.1-ről fut, de a SANCTUM_STATEFUL_DOMAINS-ban csak localhost szerepel.

### Gyors hibaelhárítás

1. Böngésző DevTools → Network:
   - `GET /sanctum/csrf-cookie` → 200/204, látszik az `XSRF-TOKEN` és a session süti.
   - `POST /login` → legyen `X-XSRF-TOKEN` header és a Cookie-k.
2. `.env` frissítés után: `php artisan config:clear` (és esetleg `route:clear`).  
3. Sütik törlése (Application → Cookies → localhost), hard reload (Ctrl+F5).

---

## API végpontok (jelen állapot)

- **Auth**
  - `POST /login` – bejelentkezés (Sanctum, session cookie)
  - `POST /logout` – kijelentkezés
  - `GET /api/me` – bejelentkezett felhasználó (auth:sanctum)

- **Events**
  - `GET /api/events` – listázás (query: `search`, `location`, `category`, `field`, `order`, `page`, `per_page`)
  - `GET /api/events/{id}` – részletek (opcionális, ha implementálva)
  - (Szervező/Admin: saját CRUD/publish – később)

- **Bookings** (tervezett / részben implementált)
  - `POST /api/bookings` – foglalás létrehozása (tranzakció + készlet/limit ellenőrzések)
  - `GET /api/bookings` – saját foglalások listája (pagináció)

---

## Docker Compose (példa)

> Ha konténerben szeretnéd futtatni, az alábbi **mintát** használhatod kiindulásként (igény szerint módosítandó).  
> Hozz létre egy `docker-compose.yml`-t a repo gyökerében:

```yaml
version: "3.9"
services:
  db:
    image: postgres:14
    environment:
      POSTGRES_DB: eventhub
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
    ports:
      - "5432:5432"
    volumes:
      - dbdata:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres -d eventhub"]
      interval: 5s
      timeout: 3s
      retries: 10

  api:
    build: ./backend
    depends_on:
      db:
        condition: service_healthy
    env_file:
      - ./backend/.env
    ports:
      - "8000:8000"
    command: php artisan serve --host=0.0.0.0 --port=8000

  web:
    build: ./frontend
    environment:
      - VITE_API_BASE=http://localhost:8000/api/
    ports:
      - "5173:5173"
    command: npm run dev -- --host 0.0.0.0

volumes:
  dbdata:
```

> **Megjegyzés:** A fenti `api` szolgáltatáshoz érdemes belerakni a `composer install`, `php artisan migrate --force --seed` lépéseket a Dockerfile-ba/entrypointba, illetve a `web` szolgáltatásnál `npm ci`-t a buildbe.  
> Prod környezetben a Vite „preview” vagy Nginx-es statikus kiszolgálás javasolt.

---

## Projekt struktúra (rövid)

```
backend/
  app/
  bootstrap/
  config/
  database/
    factories/
    migrations/
    seeders/
  routes/
    api.php        # /api végpontok (pl. /api/me, /api/events)
    web.php        # /login, /logout stb.
  .env.example
  composer.json

frontend/
  src/
    components/
      AppHeader.vue
    helpers/
      constants.js
    pages/
      auth/Login.vue
      events/Index.vue
    router/
      index.js
    stores/
      auth.js
    services/
      HttpClient.js
      OriginClient.js
      AuthService.js
  .env.example (opcionális – VITE_API_BASE)
  package.json
```

---

## Ismert limitációk / TODO

- Fizetés/ár kezelés csak váz (a foglalásoknál `unit_price` mező van, logika nincs).
- Admin / Organizer UI részlegesen kész (menük helye megvan, CRUD/publish képernyők kialakítása hátra van).
- További validációk és hibaüzenetek i18n-re vitelezése.
- E2E/Unit tesztelés bővítése (Pest/PhpUnit, Vitest/Cypress).

---

## Gyakori parancsok

**Backend**
```bash
php artisan tinker
php artisan migrate:fresh --seed
php artisan route:list
php artisan config:clear && php artisan route:clear && php artisan cache:clear
```

**Frontend**
```bash
npm run dev
npm run build
npm run preview
```

---

## Licenc

MIT (vagy amit beállítasz a repóban).
