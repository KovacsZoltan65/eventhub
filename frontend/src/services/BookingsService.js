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

        const normalized = {
            bookingId: data.bookingId ?? data.id ?? data.booking?.id,
            quantity: data.quantity ?? data.booking?.quantity ?? 0,
            totalPrice: data.totalPrice ?? (data.quantity ?? data.booking?.quantity ?? 0) * (data.unit_price ?? 0),
            timestamp: data.timestamp ?? data.created_at ?? new Date().toISOString(),
            event: data.event ?? data.booking?.event ?? null,
            raw: data, // ha mégis kell az eredeti
        };

        return normalized;
    }

    async myList(params = { page: 1, perPage: 10 })
    {
        // GET nem igényel CSRF headert → mehet apiClienttel
        const { data } = await apiClient.get(`${this.url}`, { params: { page: params.page, per_page: params.perPage } });
        return data;
    }

}

export default new BookingsService();