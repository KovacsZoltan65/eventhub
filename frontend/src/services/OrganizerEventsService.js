import originClient from '../services/OriginClient';
import apiClient from '../services/HttpClient';

let csrfReady = false;
async function ensureCsrf() {
  if (csrfReady && document.cookie.includes('XSRF-TOKEN=')) return;
  await originClient.get('/sanctum/csrf-cookie'); // <-- 5173 origin -> süti a 5173-ra kerül
  csrfReady = true;
}

class OrganizerEventsService {
  constructor() { this.url = '/organizer/events'; }

  async list(params = {}) {
    const { data } = await apiClient.get(this.url, { params });
    return data;
  }
  async show(id) {
    const { data } = await apiClient.get(`${this.url}/${id}`);
    return data;
  }
  async create(payload) {
    await ensureCsrf();
    const { data } = await apiClient.post(this.url, payload);
    return data;
  }
  async update(id, payload) {
    await ensureCsrf();
    const { data } = await apiClient.put(`${this.url}/${id}`, payload);
    return data;
  }
  async destroy(id) {
    await ensureCsrf();
    const { data } = await apiClient.delete(`${this.url}/${id}`);
    return data;
  }
  async publish(id) {
    await ensureCsrf();
    const { data } = await apiClient.post(`${this.url}/${id}/publish`);
    return data;
  }
  async cancel(id) {
    await ensureCsrf();
    const { data } = await apiClient.post(`${this.url}/${id}/cancel`);
    return data;
  }
}

export default new OrganizerEventsService();
