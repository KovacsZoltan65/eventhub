//import apiClient from './/ApiClient.js';       // url: http://localhost:8000/api
//import originClient from './OriginClient.js';   // url: http://localhost:8000 (CSRF-hez)
import {  apiClient, originClient} from './http.js';
/**
 * Visszaadja a megadott süti értékét, vagy null értéket, ha nincs beállítva ilyen nevű süti.
 * @param {string} name
 * @returns {string|null}
 */
function getCookie(name) {
    const m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return m ? decodeURIComponent(m[2]) : null;
}

class AdminBookingsService
{
    /**
     * Beállítja a szolgáltatás alap URL-jét.
     * @constructor
     */
    constructor()
    {
        this.url = '/admin/bookings';
    }

    /**
     * Visszaadja a foglalások listáját.
     * A szűrési paraméterek:
     *   - user_id: a felhasználó ID-je (egész szám)
     *   - event_id: az esemény ID-je (egész szám)
     *   - status: foglalás státusza (pending|confirmed|cancelled)
     *   - date_from: a legrégebbi dátum (YYYY-MM-DD)
     *   - date_to: a legújabb dátum (YYYY-MM-DD)
     *   - field: rendezési mező (created_at|quantity|total_price)
     *   - order: rendezési irány (asc|desc)
     *   - per_page: oldalankénti elemek száma (egész szám, alapértelmezés: 12)
     *   - page: oldalszám (egész szám)
     * @param {Object} params - a szűrési paraméterek tárolója
     * @returns {Promise} - a Laravel paginator-objektummal tér vissza
     */
    async list(params = {})
    {
        // A backend most a következőket támogatja: user_id, event_id, status, date_from, date_to,
        // field (created_at|quantity|total_price), order (asc|desc), per_page, page
        const q = {};
        // user_id: a felhasználó ID-je (egész szám)
        if (params.user_id) {
            q.user_id   = params.user_id;
        }

        // event_id: az esemény ID-je (egész szám)
        if (params.event_id)  {
            q.event_id  = params.event_id;
        }

        // status: foglalás státusza (pending|confirmed|cancelled)
        if (['pending','confirmed','cancelled'].includes(params.status)) {
            q.status = params.status;
        }

        // date_from: a legrégebbi dátum (YYYY-MM-DD)
        // Több szűrési paramétert is megadhatunk, de ebben az esetben a legrégebbi dátumot vesszük figyelembe
        if (params.date_from) {
            q.date_from = params.date_from; // 'YYYY-MM-DD'
        }

        // date_to: a legújabb dátum (YYYY-MM-DD)
        // Ha ezt a paramétert megadjuk, akkor a legrégebbi dátumig foglaljuk a tartományt
        if (params.date_to) {
            q.date_to   = params.date_to;
        }

        // field: rendezési mező (created_at|quantity|total_price)
        // Megadjuk a rendezési mezőt, ha a paraméterekben szerepel
        if (['created_at','quantity','total_price'].includes(params.field)) {
            q.field = params.field;
        }

        // order: rendezési irány (asc|desc)
        // Megadjuk a rendezési irányt, ha a paraméterekben szerepel
        if (['asc','desc'].includes(params.order)) {
            q.order = params.order;
        }

        // Alapértelmezett érték: 12
        // Minden olyan esetben, amikor a per_page paraméter szerepel a lekérdezésben, akkor a backend
        // a szerveroldali szűréshez használja fel ezt az értéket, és a megfelelő darabszámú elemet fogja
        // visszaadni a válaszban.
        q.per_page = Number.isInteger(params.perPage) ? params.perPage : 12;

        // page: oldalszám (egész szám)
        // A lekérdezésben megadott oldalszámot használjuk fel, ha a paraméterekben szerepel
        // A szerveroldali szűréshez használjuk fel, hogy a megfelelő oldalt adjuk vissza a válaszban
        if (Number.isInteger(params.page)) {
            q.page = params.page;
        }

        const { data } = await apiClient.get(this.url, { params: q });
        return data; // Laravel paginator
    }

    async cancel(id) {
        if (!getCookie('XSRF-TOKEN')) {
            await originClient.get('/sanctum/csrf-cookie');
        }
        const { data } = await originClient.patch(`/api${this.url}/${id}/cancel`);
        return data;
    }
}

export default new AdminBookingsService();
