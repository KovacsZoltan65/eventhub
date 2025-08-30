# EventHub

Webes alkalmazás események létrehozására, kezelésére és jegyfoglalásra.  
A projekt célja egy **monorepo** alapú rendszer bemutatása, ahol a backend (Laravel), frontend (Vue 3) és az adatbázis (PostgreSQL) közösen, **Docker Compose** segítségével futtatható.

---

## Funkcionalitás

### Szerepkörök és jogosultságok
- **Admin**
  - Minden entitás listázása és megtekintése
  - Felhasználók tiltása és engedélyezése
  - Események és foglalások áttekintése
- **Szervező (Organizer)**
  - Saját események CRUD (létrehozás, szerkesztés, törlés)
  - Kapacitás/jegykeret beállítása
  - Esemény publikálása / törlése / visszavonása
- **Felhasználó (User)**
  - Publikus események böngészése
  - Jegyfoglalás (max. 5 jegy / esemény, konfigurálható)
  - Saját foglalások listázása és lemondása

### Események
- **Mezők:** `title`, `description`, `startsAt`, `location`, `capacity`, `category?`, `status` (`draft | published | cancelled`)
- **Szűrés / keresés:** cím, helyszín, kategória alapján, szerveroldali pagináció
- **Publikálás:** csak a `published` események láthatók és foglalhatók

### Jegyfoglalás
- Foglalási korlát: alapértelmezésben 5 jegy / felhasználó / esemény
- Tranzakcióbiztos készletkezelés (versenyhelyzet elkerülése)
- Sikeres foglalás válasza tartalmazza:
  - `bookingId`
  - `quantity`
  - `totalPrice` (ha az eseményhez ár tartozik)
  - `timestamp`
- Felhasználók saját foglalásainak listázása

### Admin funkciók
- Felhasználók listázása és `isBlocked` állapot módosítása
- Események és foglalások teljes körű áttekintése

### Nem funkcionális követelmények
- **Biztonság:** minden input validációval ellenőrzött, titkok `.env`-ben tárolva, hibakezelés stack-trace nélkül
- **Teljesítmény:** indexek, pagináció, N+1 lekérdezések elkerülése
- **Loggolás:** strukturált JSON formátumban, INFO/ERROR szintek
- **Skálázhatóság:** session/JWT alapú auth, frontend role-guardolt route-ok

---

## Architektúra

Projekt monorepo struktúrában:

```
eventhub/
├── apps/
│   ├── backend/   # Laravel REST API (auth, események, foglalások)
│   └── frontend/  # Vue 3 + Vite kliensalkalmazás
├── docker-compose.yml
├── .env.example (root)
└── README.md
```

### Backend (Laravel)
- REST API, `routes/api.php`-ban definiálva
- Adatbázis: PostgreSQL, migrációkkal
- Használt csomagok:
  - `spatie/laravel-permission` – jogosultságkezelés
  - `prettus/l5-repository` – repository réteg
  - `spatie/laravel-activitylog` – eseményloggolás
  - `laradumps/laradumps` – debug segédlet
  - `larastan/larastan` – statikus kódelemzés

### Frontend (Vue 3 + Vite)
- Autentikáció (login/logout)
- Role-guardolt menük és route-ok
- PrimeVue komponensek (táblázat, dialógus, űrlapok)
- Oldalak:
  - Login
  - Eseménylista (szűrés, keresés, pagináció)
  - Esemény részletek + foglalás
  - Organizer nézet (saját események kezelése)
  - Admin nézet (felhasználók tiltása/engedélyezése)

### Adatbázis (PostgreSQL)
- **Táblák**: users, events, bookings, roles, permissions, activity_log
- Migrációk automatikusan futnak induláskor vagy külön parancsban

---

## Telepítés és futtatás

### 1. Klónozás
```bash
git clone https://github.com/KovacsZoltan65/eventhub.git
cd eventhub
```

### 2. Környezeti változók
Másold a példafájlokat:
```bash
cp apps/backend/.env.example apps/backend/.env
cp apps/frontend/.env.example apps/frontend/.env
cp .env.example .env
```

- **Backend** (`apps/backend/.env`):
  ```env
  APP_NAME=EventHub
  APP_ENV=local
  APP_KEY=base64:xxxx
  APP_DEBUG=true
  APP_URL=http://localhost:8000

  DB_CONNECTION=pgsql
  DB_HOST=db
  DB_PORT=5432
  DB_DATABASE=eventhub
  DB_USERNAME=eventhub
  DB_PASSWORD=eventhub

  LOG_CHANNEL=stack
  ```

- **Frontend** (`apps/frontend/.env`):
  ```env
  VITE_API_URL=http://localhost:8000/api
  ```

- **DB** (docker compose környezetből):
  ```env
  POSTGRES_USER=eventhub
  POSTGRES_PASSWORD=eventhub
  POSTGRES_DB=eventhub
  ```

### 3. Docker indítása
```bash
docker compose up -d
```

### 4. Migrációk és seed futtatása
```bash
docker compose exec api php artisan migrate --seed
```

### 5. Elérés
- **Backend API:** http://localhost:8000
- **Frontend:** http://localhost:5173

---

## Seed felhasználók

| Szerep   | Email                  | Jelszó    |
|----------|------------------------|-----------|
| Admin    | admin@eventhub.local   | Admin123! |
| Organizer| org@eventhub.local     | Org123!   |
| User     | user@eventhub.local    | User123!  |

---

## Ismert limitációk
- Jelszó reset funkció nincs implementálva
- Jegyvásárlás jelenleg ár nélküli (opcionális bővítés)
- Session alapú auth támogatott, JWT bővítés alatt
- Tesztadatok (events, bookings) korlátozott mennyiségűek

---

## Fejlesztői tippek
- **Cache törlés:** `php artisan optimize:clear`
- **Seeder újrafuttatás:** `php artisan migrate:fresh --seed`
- **Log megtekintése:** `docker compose logs -f api`
- **Adatbázis elérés:** `docker compose exec db psql -U eventhub eventhub`
