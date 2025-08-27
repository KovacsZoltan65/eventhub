import originClient from '@/services/OriginClient.js'; // base: http://localhost:8000
import apiClient from '@/services/HttpClient.js';       // base: http://localhost:8000/api

function getCookie(name) {
  const m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return m ? decodeURIComponent(m[2]) : null;
}

class BookingsService
{
    constructor() {
        //super();
        this.url = "/bookings";
    }

    async create({ event_id, quantity })
    {
        // biztosítsuk, hogy legyen XSRF-TOKEN (ha nincs, kérünk egyet)
        if (!getCookie('XSRF-TOKEN')) {
            await originClient.get('/sanctum/csrf-cookie');
        }
        // ÁLLAPOTOT MÓDOSÍTÓ HÍVÁS → originClient + /api útvonal
        const { data } = await originClient.post( `/api${this.url}`, { event_id, quantity });

        return {
            bookingId: data.bookingId ?? data.id ?? data.booking?.id,
            quantity: data.quantity ?? data.booking?.quantity ?? 0,
            totalPrice: data.totalPrice ?? (data.quantity ?? data.booking?.quantity ?? 0) * (data.unit_price ?? 0),
            timestamp: data.timestamp ?? data.created_at ?? new Date().toISOString(),
            event: data.event ?? data.booking?.event ?? null,
            raw: data, // ha mégis kell az eredeti
        };
    }

    // ÚJ: saját foglalások listája
    async listMine(params = {}) {
        // Auth-olt GET → originClient (cookie-kkal)
        const query = {
            status: params.status ?? '',
            page: params.page ?? 1,
            per_page: params.perPage ?? 10,   // backend 'per_page'-t vár
            field: params.field ?? 'created_at',
            order: params.order ?? 'desc',
        };

        // üres stringeket távolítsunk el a query-ből
        Object.keys(query).forEach((k) => {
            if (query[k] === '') delete query[k]
        });

        const { data } = await originClient.get('/api/my/bookings', { params: query });
        return data; // { data: [...], links: {...}, meta: {...} }
    }

}

export default new BookingsService();