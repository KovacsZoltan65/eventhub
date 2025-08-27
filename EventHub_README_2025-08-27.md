# EventHub — Monorepo (Backend API + Frontend)

Egyszerű esemény- és jegyfoglaló rendszer **Laravel (API) + Vue 3 (Vite, Pinia, vue‑router)** stackkel.  
Cél: **admin / organizer / user** szerepkörök, publikálható események, jegyfoglalás, admin felhasználókezelés.

---

## Újdonságok / Changelog
**Dátum:** 2025-08-27

- **Admin / Felhasználók**
  - Tiltás/engedélyezés működik (PATCH `/admin/users/{id}/block`), **saját fiók letiltása megakadályozva**.
  - Lista szerver oldali szűrés/rendezés/pagináció, `field=is_blocked` szerinti **rendezés** támogatva.
  - Üres paraméterek normalizálása (backend), `blocked=0|1` helyes kezelése (frontend + backend).
- **Organizer / Események**
  - Saját események lista + **CRUD** + **publish/cancel**.
  - Táblázatban **„Részletek”** gomb az egyértelmű navigációhoz a publikus részletek oldalra.
- **Publikus események**
  - Lista (keresés/helyszín/kategória), szerver oldali pagináció.
  - Részletek oldal (published eseményekhez), jegyfoglalás előkészítve.
- **Frontend router guard**
  - Javítva a szerepkör ellenőrzés: **roles tömb** összevetése a `meta.roles`-szal.
- **HTTP kliens**
  - `originClient` helyes import **OriginClient.js**-ből (CSRF‑képes módosító kérésekhez).

---

## Fő funkciók
- **Felhasználói szerepek**: `admin`, `organizer`, `user` (guest megtekintheti a published eseményeket).
- **Események**: cím, leírás, időpont (`starts_at`), helyszín, kapacitás, kategória (opcionális), státusz (`draft|published|cancelled`).
- **Keresés / szűrés / pagináció** a publikus és az organizer listában.
- **Foglalás**: per-user limit (ENV), tranzakciós készletkezelés (versenyhelyzetben sem mehet 0 alá).
- **Admin**: felhasználók listája, tiltás/engedélyezés, saját fiók védelme.
- **Biztonság**: input validáció, titkok `.env`-ben, stack trace rejtése prod‑ban, **EnsureUserIsNotBlocked** middleware.

---

## Architektúra
```
/backend   # Laravel API (Sanctum, Spatie Permission, Activity Log, stb.)
/frontend  # Vue 3 + Vite + Pinia + vue-router (role-guardolt menük/route-ok)
```

**Adatbázis:** PostgreSQL • Migrációk a `users`, `events`, `bookings` (stb.) táblákhoz.

---

## Követelmények
- Node 18+ (frontend), PHP 8.2+ (backend), Composer 2, PostgreSQL 14+
- Alternatíva: Docker Desktop

---

## Telepítés & futtatás

### Backend
```bash
cd backend
cp .env.example .env
# DB kapcsolat beállítása
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
Alapértelmezett API: `http://localhost:8000` (API: `/api`).

### Frontend
```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```
Vite fejlesztői szerver: `http://localhost:5173`.

### Docker Compose (minta)
Monorepo gyökérben `docker-compose.yml`:
```yaml
version: "3.9"
services:
  db:
    image: postgres:16
    environment:
      POSTGRES_DB: eventhub
      POSTGRES_USER: eventhub
      POSTGRES_PASSWORD: secret
    ports: ["5432:5432"]
    volumes: [ "dbdata:/var/lib/postgresql/data" ]
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U eventhub -d eventhub"]
      interval: 5s
      timeout: 3s
      retries: 10

  api:
    build: ./backend
    env_file: ./backend/.env
    depends_on: { db: { condition: service_healthy } }
    ports: ["8000:8000"]
    volumes: [ "./backend:/var/www/html" ]
    command: sh -c "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"

  web:
    build: ./frontend
    env_file: ./frontend/.env
    environment:
      - VITE_BASE_URL=http://localhost:8000/api
      - VITE_ORIGIN_BASE=http://localhost:8000
    ports: ["5173:5173"]
    depends_on: [ api ]
    volumes: [ "./frontend:/app" ]
    command: sh -c "npm install && npm run dev -- --host"

volumes: { dbdata: {{}} }
```

---

## Környezeti változók

### Backend `.env` (kivonat)
```
APP_NAME=eventhub
APP_ENV=local
APP_KEY=base64:...

APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=eventhub
DB_USERNAME=eventhub
DB_PASSWORD=secret

BOOKING_MAX_PER_USER=5

SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
```

### Frontend `.env.example`
```
VITE_BASE_URL=http://localhost:8000/api
VITE_ORIGIN_BASE=http://localhost:8000
```

---

## Frontend (web)

### Menük (role guard)
- **Események** (`/events`) — publikus
- **Szervező / Események** (`/organizer/events`) — `organizer|admin`
- **Admin / Felhasználók** (`/admin/users`) — `admin`
- **Foglalásaim** (`/bookings`) — bejelentkezett user

### Router guard (kivonat)
```js
if (to.meta?.roles?.length && auth.isAuthenticated) {
  const roles = auth.user?.roles || []
  const ok = to.meta.roles.some(r => roles.includes(r))
  if (!ok) return { path: '/' }
}
```

### HTTP kliensek
- `HttpClient.js` → **apiClient** (`/api`, JSON)
- `OriginClient.js` → **originClient** (CSRF‑köteles műveletek: login/logout/patch)

---

## Backend (API)

### Fő végpontok (rövid)
- **Auth / profil**: `GET /api/me`, `POST /login`, `POST /logout`
- **Publikus események**: `GET /api/events`, `GET /api/events/{id}`
- **Organizer**: `GET/POST/PATCH/DELETE /api/organizer/events`, `PATCH /publish`, `PATCH /cancel`
- **Foglalás**: `POST /api/bookings` (tranzakció, per‑user limit ENV‑ből)
- **Admin**: `GET /api/admin/users`, `PATCH /api/admin/users/{id}/block`

**Middleware**: `auth:sanctum`, `role:admin|organizer` (Spatie), `EnsureUserIsNotBlocked`.

---

## Seed felhasználók
```
admin@eventhub.local / Admin123!
org@eventhub.local   / Org123!
user@eventhub.local  / User123!
```

---

## Ismert korlátozások
- Fizetés nincs integrálva; foglalás demo módban `confirmed`-ként rögzül.
- E-mail verifikáció / jelszó reset nincs bekötve.
- Docker Compose minta – környezetfüggően testre szabandó.

---

## Hibakeresés / Tippek
- **originClient import**: `import originClient from '@/services/OriginClient.js'`
- **blocked=0 query**: frontenden explicit küldd (`'0'|'1'`), backend: `if ($request->has('blocked')) ...`
- **CSRF**: mindig `GET /sanctum/csrf-cookie` login/logout/patch előtt.
