# EventHub

Webes alkalmazás események kezelésére és jegyfoglalásra.

## Funkciók

### Szerepkörök és jogosultságok
- **Admin**: teljes jogosultság, minden adat elérhető, felhasználók tiltása/engedélyezése.
- **Szervező (Organizer)**: saját események CRUD, jegykeret beállítása, esemény publikálása.
- **Felhasználó (User)**: események böngészése, jegyfoglalás.

### Események
- Mezők: `title`, `description`, `startsAt`, `location`, `capacity`, `category` (opcionális), `status` (draft/published/cancelled)
- Keresés/szűrés: cím, helyszín, kategória alapján, szerveroldali paginációval
- Publikálás: csak `published` esemény foglalható

### Jegyfoglalás
- Egy felhasználó eseményenként max. 5 jegyet foglalhat (paraméterezhető)
- Tranzakciós készletkezelés, versenyhelyzet elkerülése
- Foglalás visszaadja: `bookingId`, `quantity`, `totalPrice`, `timestamp`
- Saját foglalások listázása

### Admin funkciók
- Felhasználók listázása, `isBlocked` állítás
- Események és foglalások áttekintése

### Nem funkcionális
- Input validáció, stack trace elrejtés hibák esetén
- Teljesítmény: indexek, pagináció, N+1 elkerülése
- Loggolás: strukturált JSON, INFO/ERROR szint
- Biztonságos jelszókezelés, auth session/JWT alapon

---

## Architektúra

Monorepo struktúra:
- `apps/backend` – Laravel REST API
- `apps/frontend` – Web kliens (Vue 3 + Vite)
- `postgres` – PostgreSQL adatbázis migrációkkal
- `docker-compose.yml` – backend, frontend, postgres indítása

---

## Telepítés és futtatás

### 1. Klónozás és belépés
```bash
git clone https://github.com/KovacsZoltan65/eventhub.git
cd eventhub
```

### 2. Környezeti változók
Minden komponenshez van `.env.example`. Másold `.env` néven és állítsd be:
- **Backend**: API_URL, DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- **Frontend**: VITE_API_URL
- **DB**: POSTGRES_USER, POSTGRES_PASSWORD, POSTGRES_DB

### 3. Docker indítása
```bash
docker compose up -d
```

### 4. Migrációk futtatása
```bash
docker compose exec api php artisan migrate --seed
```

### 5. Elérés
- Backend API: http://localhost:8000
- Frontend: http://localhost:5173

---

## Seed felhasználók

| Szerep | Email | Jelszó |
|--------|--------------------------|-----------|
| Admin  | admin@eventhub.local     | Admin123! |
| Org    | org@eventhub.local       | Org123!   |
| User   | user@eventhub.local      | User123!  |

---

## Ismert limitációk
- Jelszó reset funkció nincs implementálva
- Jegyvásárlás ár nélküli (opcionálisan bővíthető)
- Session alapú auth támogatott, JWT bővítés alatt
