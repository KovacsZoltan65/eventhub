import apiClient from '../services/HttpClient';

class OrganizerEventsService
{
    constructor()
    {
        this.url = '/organizer/events';
    }

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

    async create(payload) {
        const { data } = await apiClient.post(this.url, payload);
        return data;
    }

    async update(id, payload) {
        const { data } = await apiClient.patch(`${this.base}/${id}`, payload);
        return data;
    }

    async destroy(id) {
        const { data } = await apiClient.delete(`${this.base}/${id}`);
        return data;
    }

    async publish(id) {
        const { data } = await apiClient.patch(`${this.base}/${id}/publish`);
        return data;
    }

    async cancel(id) {
        const { data } = await apiClient.patch(`${this.base}/${id}/cancel`);
        return data;
    }
}

export default new OrganizerEventsService();