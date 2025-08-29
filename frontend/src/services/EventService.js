import { apiClient, originClient } from './http';

class EventsService
{

    constructor()
    {
        this.url = "/events";
    }

    async list(params = {})
    {
        // snake_case a backendhez (per_page), és üresek kiszűrése
        const raw = {
            status: "published",
            page: params.page ?? 1,
            per_page: params.perPage ?? 12,   // <--- per_page
            search: params.search,            // lehet undefined/'' -> majd szűrjük
            location: params.location,
            category: params.category,
            field: params.field,              // ha lesz rendezés
            order: params.order,
        };

        // csak a "valódi" értékeket küldjük (ne menjen '', null, undefined)
        const query = Object.fromEntries(Object.entries(raw).filter(([_, v]) => v !== undefined && v !== null && v !== ""));
        const res = await apiClient.get(this.url, { params: query });
        return res.data;
    }

    async show(id)
    {
        const res = await apiClient.get(`${this.url}/${id}`);
        return res.data?.data ?? res.data; // támogatjuk mindkét szerkezetet
    }
}

export default new EventsService();