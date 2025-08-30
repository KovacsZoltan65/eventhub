# Backend – Helyi indítás (Docker nélkül)

Ez az útmutató a **Laravel backend** lokális futtatását írja le Windows/Mac/Linux környezetben, Docker használata nélkül.

## 0) Előfeltételek
- PHP 8.2+ (intl, mbstring, openssl, pdo_pgsql, curl, zip)
- Composer 2.x
- Node.js 18+ és npm 9+ (csak az eszközök miatt, nem kötelező a backendhez)
- PostgreSQL 14+ (futó adatbázis, hozzáférés az új DB létrehozásához)
- Git (opcionális)

Tipp: Windows alatt a WAMP/XAMPP gyakran MySQL-t ad – itt PostgreSQL kell. Telepítsd külön (pl. pgAdmin-nal).

## 1) Projekt könyvtár
Lépj a backend könyvtárba:
```bash
cd apps/backend   # vagy: backend
```

## 2) Környezeti fájl
Másold le az `.env.example`-t:
```bash
copy .env.example .env   # Windows
# vagy
cp .env.example .env     # macOS/Linux
```

Állítsd be a legfontosabb változókat az `.env`-ben:
```env
APP_NAME=EventHub
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=eventhub_local
DB_USERNAME=postgres
DB_PASSWORD=postgres
Megjegyzés: Amennyiben az adatbázis elérési paraméterek eltérnek, alkalmazd a megfelelő értékeket.

# Frontend URL + CORS / Sanctum (ha SPA-rol hivod az API-t)
FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost

# (Opcionális) Log formatum
LOG_CHANNEL=stack
LOG_LEVEL=debug
LOG_STACK_CHANNELS="single"
```

## 3) Fuggosegek telepitese
```bash
composer install
```

Ha Windows alatt jogosultsagi gond van, futtasd adminkent vagy a `--no-scripts` opcioval, majd kulon: `php artisan key:generate`.

## 4) App kulcs
```bash
php artisan key:generate
```

## 5) Adatbazis letrehozasa
Hozz letre egy ures PostgreSQL adatbazist (`eventhub_local`) a pgAdmin-bol vagy CLI-vel.

## 6) Migraciok + Seederek
```bash
php artisan migrate
php artisan db:seed
```
A seed letrehozhat mintafelhasznalokat (pl.):
- Admin – `admin@eventhub.local / Admin123!`
- Szervezo – `org@eventhub.local / Org123!`
- Felhasznalo – `user@eventhub.local / User123!`

(Ha a seederek mas credentiellel dolgoznak, ellenorizd a `database/seeders` mappat.)

## 7) Fejlesztoi szerver inditasa
```bash
php artisan serve --host=127.0.0.1 --port=8000
```
Backend elerheto: http://localhost:8000

## 8) Gyors API-teszt (opcionalis)
- GET `/api/health` (ha van)
- Auth eseten: elobb `GET /sanctum/csrf-cookie`, majd `POST /login` (email/jelszo), utana hitelesitett hivások.

## 9) Hasznos Artisan parancsok
```bash
php artisan optimize:clear       # cache urites (config/route/view)
php artisan migrate:fresh --seed # teljes adatbazis ujraepitese
php artisan tinker               # interaktiv konzol
```

## 10) Tipikus hibak & megoldasok

- SQLSTATE[08006] vagy nem csatlakozik a DB-hez  
  Ellenorizd a `DB_*` valtozokat, a portot (5432), es hogy a DB letezik.

- CORS / CSRF problemak SPA-val (Vue)  
  - `.env`-ben legyen:  
    `APP_URL=http://localhost:8000`  
    `FRONTEND_URL=http://localhost:5173`  
    `SANCTUM_STATEFUL_DOMAINS=localhost:5173`  
    `SESSION_DOMAIN=localhost`  
  - SPA-bol eloszor hivd: `GET /sanctum/csrf-cookie`, csak utana `POST /login`.  
  - Engedelyezett origin: `http://localhost:5173`

- Route [login] not defined.  
  Ellenorizd az auth route-ok regisztraciojat (pl. `routes/api.php` vagy `routes/web.php`) es az authentikacios csomagot/beallitast.

## 11) Kovetkezo lepesek (frontend integracio)
- A frontend `.env`-ben az `VITE_API_BASE_URL=http://localhost:8000` legyen beallitva.
- Ha szerepkoros menuk vannak a fronton, csak sikeres bejelentkezes utan fognak latszani a guardolt nezetek.
