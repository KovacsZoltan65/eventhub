import apiClient from '@/services/HttpClient.js';       // url: http://localhost:8000/api
import originClient from '@/services/OriginClient.js';   // url: http://localhost:8000 (CSRF-hez)

function getCookie(name) {
    const m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return m ? decodeURIComponent(m[2]) : null;
}

class AdminBookingsService
{
    constructor()
    {
        this.url = '/admin/bookings';
    }

    async list(params = {})
    {
        // A backend most a következőket támogatja: user_id, event_id, status, date_from, date_to,
        // field (created_at|quantity|total_price), order (asc|desc), per_page, page
        const q = {};
        if (params.user_id)   q.user_id   = params.user_id;
        if (params.event_id)  q.event_id  = params.event_id;
        if (['pending','confirmed','cancelled'].includes(params.status)) q.status = params.status;
        if (params.date_from) q.date_from = params.date_from; // 'YYYY-MM-DD'
        if (params.date_to)   q.date_to   = params.date_to;

        if (['created_at','quantity','total_price'].includes(params.field)) q.field = params.field;
        if (['asc','desc'].includes(params.order)) q.order = params.order;

        q.per_page = Number.isInteger(params.perPage) ? params.perPage : 12;
        if (Number.isInteger(params.page)) q.page = params.page;

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
