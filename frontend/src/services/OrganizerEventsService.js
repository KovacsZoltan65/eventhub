import originClient from '../services/OriginClient';
import apiClient from '../services/HttpClient';

function getCookie(name) {
  const m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return m ? decodeURIComponent(m[2]) : null;
}

class OrganizerEventsService
{
    /**
     * Beállítja a szolgáltatás alap URL-címét.
     * @constructor
     */
    constructor()
    {
        this.url = '/organizer/events';
    }
/*
    async list(params = {}) { return await apiClient.get(this.url, { params }).then(r => r.data); }
    async show(id) { return await apiClient.get(`${this.url}/${id}`).then(r => r.data); }
    async create(payload) { return await apiClient.post(this.url, payload).then(r => r.data); }
    async update(id, payload) { return await apiClient.put(`${this.url}/${id}`, payload).then(r => r.data); }
    async destroy(id) { return await apiClient.delete(`${this.url}/${id}`).then(r => r.data); }
    async publish(id) { return await apiClient.post(`${this.url}/${id}/publish`).then(r => r.data); }
    async cancel(id) { return await apiClient.patch(`${this.url}/${id}/cancel`).then(r => r.data); }
*/

    
    async list(params = {})
    {
        const {data} = await apiClient.get(this.url, {params});
        return data;
    }

    async show(id)
    {
        const { data } = await apiClient.get(`${this.url}/${id}`);
        return data;
    }

    async create(payload)
    {
        // biztosítsuk, hogy legyen XSRF-TOKEN (ha nincs, kérünk egyet)
        if (!getCookie('XSRF-TOKEN')) {
            await originClient.get('/sanctum/csrf-cookie');
        }

        const { data } = await apiClient.post(this.url, payload);
        return data;
    }

    async update(id, payload)
    {
        // biztosítsuk, hogy legyen XSRF-TOKEN (ha nincs, kérünk egyet)
        if( !getCookie('XSRF-TOKEN') ) {
            await originClient.get('/sanctum/csrf-cookie');
        }

        //const { data } = await apiClient.put(`${this.url}/${id}`, payload);
        const { data } = await originClient.put(`api${this.url}/${id}`, payload);
        return data;
    }

    async destroy(id) {
        const { data } = await apiClient.delete(`${this.url}/${id}`);
        return data;
    }

    async publish(id) {
        const { data } = await apiClient.patch(`${this.url}/${id}/publish`);
        return data;
    }

    async cancel(id) {
        const { data } = await apiClient.patch(`${this.url}/${id}/cancel`);
        return data;
    }
    
}

export default new OrganizerEventsService();