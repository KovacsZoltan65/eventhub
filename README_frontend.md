# Frontend – Helyi indítás (Docker nélkül)

Ez az útmutató a **Vue 3 + Vite** alapú frontend lokális futtatását írja le, Docker nélkül.

## 0) Előfeltételek
- Node.js 18+ és npm 9+ (vagy pnpm/yarn)
- Futó backend API (pl. `http://localhost:8000`)

## 1) Projekt könyvtár
Lépj a frontend könyvtárba:
```bash
cd apps/frontend   # vagy: frontend
```

## 2) Környezeti fájl
Másold le az `.env.example`-t:
```bash
copy .env.example .env   # Windows
# vagy
cp .env.example .env     # macOS/Linux
```

Állítsd be az API elérést az `.env`-ben (Vite előtaggal):
```env
# A backend API gyoker URL-je
VITE_API_BASE_URL=http://localhost:8000

# A frontend teljes URL-je (auth/cors miatt is hasznos megadni)
VITE_FRONTEND_URL=http://localhost:5173
```

Ha az alkalmazas kulon "originClient"-et hasznal, ellenorizd, hogy az is ezt a BASE_URL-t/ORIGIN-t veszi fel.

## 3) Fuggosegek telepitese
```bash
npm install
# vagy
pnpm install
# vagy
yarn install
```

## 4) Fejlesztoi szerver inditasa
```bash
npm run dev
# Vagy ha kulon port kell:
# npm run dev -- --port=5173
```
Alapertelemezett eleres: http://localhost:5173

## 5) Ajanlott beallitasok (CORS/CSRF egyuttmukodes)
- Backend `.env` pelda (osszhangban a fronttal):
  ```env
  APP_URL=http://localhost:8000
  FRONTEND_URL=http://localhost:5173
  SANCTUM_STATEFUL_DOMAINS=localhost:5173
  SESSION_DOMAIN=localhost
  ```
- Frontend hivasi sorrend autentikaciohoz (Sanctum eseten):
  1) `GET /sanctum/csrf-cookie`
  2) `POST /login` (email + jelszo)
  3) Hitelesitett API hivások (kuldott sutikkel / Authorization headerekkel)

## 6) Tipikus hibak & megoldasok

- CORS policy: No 'Access-Control-Allow-Origin' header  
  Engedelyezd a front originjet a backend CORS beallitasoknal. `.env`: `FRONTEND_URL=http://localhost:5173` es a CORS config vegye figyelembe.

- CSRF token mismatch vagy Unauthenticated  
  - Mindig kerj CSRF sutit a login elott (`/sanctum/csrf-cookie`).  
  - Azonos `SESSION_DOMAIN` es `SANCTUM_STATEFUL_DOMAINS` a backend `.env`-ben.  
  - Ugyelj, hogy a front es a back azonos top-level domainen fusson (fejlesztesnel `localhost`).

- The requested module ... does not provide an export named 'default'  
  Ellenorizd az import/export szignaturakat. Ha egy modul `export const something = ...`, akkor named import kell: `import { something } from '...';`

- Component setup returned a promise, but no <Suspense>  
  Ha aszinkron `setup()`-ot hasznalsz, tedd `<Suspense>` ala, vagy alakitsd at a komponenst szinkronra.

## 7) Build (opcionalis)
```bash
npm run build
npm run preview   # build ellenorzese lokalisan
```

## 8) Bejelentkezes teszt felhasznalokkal
Ha a backend seedi mintafelhasznalokat, probald ki pl.:
- Admin – `admin@eventhub.local / Admin123!`
- Szervezo – `org@eventhub.local / Org123!`
- Felhasznalo – `user@eventhub.local / User123!`

## 9) Hasznos tippek
- Vite fejlesztesnel a kornyezeti valtozok csak `VITE_` elotaggal lesznek elerhetok a kliensben.
- Ha az API path proxyn keresztul megy, allits be Vite dev szerver proxyt (`vite.config.{js,ts}` -> `server.proxy`).
