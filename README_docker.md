# EventHub – teljes indítás 0 állapotról (Docker)

Ez a leírás Windows + Docker Desktop környezetre készült. Lépésről lépésre végigvisz egy *tiszta* (0 állapotú) indításon, beleértve a konténerek, volume-ok, függőségek újraépítését.

---

## 0) Előfeltételek

- **Docker Desktop** fut (Settings → Resources: CPU ≥ 4, Memory ≥ 6–8 GB javasolt).
- Projektstruktúra:
  ```text
  eventhub/
  ├─ apps/
  │  ├─ backend/    # Laravel API
  │  └─ frontend/   # Vite + Vue (vagy más, de Vite alapú)
  └─ docker/
     ├─ docker-compose.yml
     └─ apps/
        ├─ backend/   # Dockerfile, entrypoint.sh
        └─ frontend/  # Dockerfile
  ```

---

## 1) Teljes “nullázás” (konténerek + volume-ok + host függőségek)

PowerShell a `eventhub/docker` mappában:
```powershell
cd C:\wamp64\www\bbox\eventhub\docker

# konténerek + hálózatok LE + a compose volume-ok törlése (-v)
docker compose down -v

# host oldali függőségek törlése (ha léteznek)
rmdir /s /q ..\apps\backend\vendor           2>nul
rmdir /s /q ..\apps\frontend\node_modules    2>nul
```

> **Megj.:** a `-v` törli a compose-hoz tartozó named volume-okat is (pl. `db_data`, `node_modules_front`), így a DB és a frontend `node_modules` is nulláról indul.

---

## 2) Kötelező beállítások (egyszer kell ellenőrizni)

### 2.1 `docker/docker-compose.yml` – alap szolgáltatások

- **Ne legyen** `version:` kulcs a fájl tetején (elavult).
- **Postgres** named volume-on (ne Windows bind-ot használjon!):
  ```yaml
  services:
    db:
      image: postgres:16
      container_name: eventhub-db
      environment:
        POSTGRES_DB: eventhub
        POSTGRES_USER: eventhub
        POSTGRES_PASSWORD: secret
      volumes:
        - db_data:/var/lib/postgresql/data
      healthcheck:
        test: ["CMD-SHELL", "pg_isready -U $POSTGRES_USER -d $POSTGRES_DB"]
        interval: 5s
        timeout: 5s
        retries: 20
      ports:
        - "5432:5432"
  ```

- **Backend** (Laravel) – DB env egyezzen a Postgreshöz:
  ```yaml
    backend:
      build:
        context: ../
        dockerfile: docker/apps/backend/Dockerfile
      container_name: eventhub-api
      depends_on:
        db:
          condition: service_healthy
      environment:
        APP_ENV: local
        APP_DEBUG: "true"
        DB_CONNECTION: pgsql
        DB_HOST: db
        DB_PORT: 5432
        DB_DATABASE: eventhub
        DB_USERNAME: eventhub
        DB_PASSWORD: secret
      volumes:
        - ../apps/backend:/var/www/html
      ports:
        - "8000:8000"
      command: ["/bin/sh","/docker/entrypoint.sh"]
  ```

- **Frontend** (Vite) – külön `node_modules` volume és polling (Windows):
  ```yaml
    frontend:
      build:
        context: ../
        dockerfile: docker/apps/frontend/Dockerfile
      container_name: eventhub-web
      depends_on:
        - backend
      environment:
        NODE_ENV: development
        VITE_API_BASE_URL: http://backend:8000
        CHOKIDAR_USEPOLLING: "true"
      volumes:
        - ../apps/frontend:/app
        - node_modules_front:/app/node_modules
      ports:
        - "5173:5173"
      command: ["sh","-lc","npm ci && npm run dev -- --host 0.0.0.0 --port 5173"]
  ```

- **(Opcionális) Adminer / pgAdmin GUI** – később is hozzáadható (lásd lent).

- **Volumes** blokk a fájl **legalján**:
  ```yaml
  volumes:
    db_data:
    node_modules_front:
  ```

### 2.2 `apps/frontend/vite.config.js` – proxy a backend service-re

```js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  appType: 'spa',
  plugins: [vue()],
  resolve: { alias: { '@': path.resolve(__dirname, './src') } },
  server: {
    host: '0.0.0.0',
    port: 5173,
    cors: true,
    proxy: {
      '/api':     { target: 'http://backend:8000', changeOrigin: true },
      '/login':   { target: 'http://backend:8000', changeOrigin: true },
      '/logout':  { target: 'http://backend:8000', changeOrigin: true },
      '/sanctum': { target: 'http://backend:8000', changeOrigin: true },
    },
  },
})
```

### 2.3 `apps/backend/.env` – DB + Sanctum/CORS

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=eventhub
DB_USERNAME=eventhub
DB_PASSWORD=secret

SESSION_DRIVER=cookie
SESSION_SECURE_COOKIE=false
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173
```

---

## 3) Build és indítás

```powershell
# teljes build (ha Dockerfile-ok változtak)
docker compose build --no-cache

# indulás háttérben
docker compose up -d
```

**Log ellenőrzés:**
```powershell
docker compose logs -f db
docker compose logs -f backend
docker compose logs -f frontend
```
Várt jelek:
- db: healthcheck ok
- backend: `Server running on [http://0.0.0.0:8000]`
- frontend: `VITE v... ready ...` és **nincs** `http proxy error`

---

## 4) Migráció + seed (0 állapot után kötelező)

```powershell
# cache ürítés
docker compose exec backend php artisan optimize:clear

# teljes reset + mintaadatok
docker compose exec backend php artisan migrate:fresh --seed
```

**Gyors DB check:**
```powershell
docker compose exec db psql -U eventhub -d eventhub -c "select id,email from users order by id limit 5;"
```

---

## 5) Használat – URL-ek és belépés

- **Frontend**: http://localhost:5173  
- **Backend API**: http://localhost:8000

Seedelt userek (példa):
- admin: `admin@eventhub.local` / `Admin123!`
- organizer: `org@eventhub.local` / `Org123!`
- user: `user@eventhub.local` / `User123!`

---

## 6) (Opcionális) DB admin felület

### Adminer (egyszerű)

`docker-compose.yml` → `services:` alá:
```yaml
  adminer:
    image: adminer
    container_name: eventhub-adminer
    depends_on: [db]
    ports:
      - "8081:8080"
```
Indítás & belépés:
```powershell
docker compose up -d adminer
```
Böngésző: http://localhost:8081  
**System:** PostgreSQL, **Server:** `db`, **User:** `eventhub`, **Password:** `secret`, **Database:** `eventhub`.

### pgAdmin (teljes GUI)

```yaml
  pgadmin:
    image: dpage/pgadmin4:8
    container_name: eventhub-pgadmin
    depends_on: [db]
    environment:
      PGADMIN_DEFAULT_EMAIL: admin@example.com
      PGADMIN_DEFAULT_PASSWORD: secret
    ports:
      - "5050:80"
    volumes:
      - pgadmin_data:/var/lib/pgadmin
```
A `volumes:` aljára:
```yaml
  pgadmin_data:
```
Indítás:
```powershell
docker compose up -d pgadmin
```
Kapcsolat hozzáadása pgAdminban: Host `db`, Port `5432`, User `eventhub`, Password `secret`.

---

## 7) Hibakeresési jegyzetek

- **Proxy hiba / ECONNREFUSED**  
  A Vite proxy **`http://backend:8000`** legyen (konténernév, ne `localhost`).

- **DB jelszó / felhasználónév hibák**  
  `.env` egyezzen:
  ```env
  DB_CONNECTION=pgsql
  DB_HOST=db
  DB_PORT=5432
  DB_DATABASE=eventhub
  DB_USERNAME=eventhub
  DB_PASSWORD=secret
  ```
  Majd:
  ```powershell
  docker compose exec backend php artisan optimize:clear
  docker compose restart backend
  ```

- **esbuild EIO / lassú npm Windows-on**  
  Legyen külön `node_modules` volume (lásd fent), és a frontend Dockerfile tartalmazza:
  ```dockerfile
  FROM node:20-alpine
  RUN apk add --no-cache libc6-compat
  WORKDIR /app
  EXPOSE 5173
  ```

- **Port ütközés**  
  Compose-ban a bal oldali portot írd át (pl. `8001:8000`, `5174:5173`).

---

## 8) (Opcionális) MySQL-re váltás – tömören

- **MySQL service** a compose-ban:
  ```yaml
  mysql:
    image: mysql:8.4
    container_name: eventhub-mysql
    environment:
      MYSQL_DATABASE: eventhub
      MYSQL_USER: eventhub
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: rootsecret
    ports: ["3306:3306"]
    volumes: ["mysql_data:/var/lib/mysql"]
  ```

- **Backend Dockerfile** – `pdo_mysql` is kell:
  ```dockerfile
  RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring intl pdo_mysql
  ```

- **`.env` MySQL-re**:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=mysql
  DB_PORT=3306
  DB_DATABASE=eventhub
  DB_USERNAME=eventhub
  DB_PASSWORD=secret
  ```

- **Újraépítés és migráció**:
  ```powershell
  docker compose build backend --no-cache
  docker compose up -d mysql backend
  docker compose exec backend php artisan optimize:clear
  docker compose exec backend php artisan migrate:fresh --seed
  ```

- **(Opcionális) phpMyAdmin**:
  ```yaml
  phpmyadmin:
    image: phpmyadmin
    container_name: eventhub-phpmyadmin
    depends_on: [mysql]
    environment:
      PMA_HOST: mysql
      PMA_USER: eventhub
      PMA_PASSWORD: secret
    ports:
      - "8082:80"
  ```

---

## 9) Teljes újrakezdés később – gyors recept

```powershell
cd C:\wamp64\www\bbox\eventhub\docker
docker compose down -v
rmdir /s /q ..\apps\backend\vendor        2>nul
rmdir /s /q ..\apps\frontend\node_modules 2>nul
docker compose up -d --build
docker compose exec backend php artisan migrate:fresh --seed
```

---

**Tipp:** ha a DB érzetre lassú Windows-on, ellenőrizd, hogy a Postgres **named volume**-ot használ-e (ne `bind` mountot), és adj több CPU/RAM-ot a Dockernek (Settings → Resources). Fejlesztésben ideiglenesen gyorsíthatsz a Postgresen: `synchronous_commit=off`, `fsync=off` (csak DEV!).
