import { apiClient, originClient } from './http';

import { getCookie } from '../utils/cookies';

class AdminUsersService
{
    constructor()
    {
        this.url = '/admin/users';
    }

    async list(params = {})
    {
        const query = {
            search: params.search ?? '',
            page: params.page ?? 1,
            per_page: params.perPage ?? 12,
            field: params.field ?? 'name',
            order: params.order ?? 'asc',
        };

        const res = await apiClient.get(this.url, { params: query });
        return res.data;
    }

    async setBlocked(id, isBlocked)
    {
        if (!getCookie('XSRF-TOKEN')) {
            await originClient.get('/sanctum/csrf-cookie');
        }

        const url = `/api${this.url}/${id}/${isBlocked ? 'block' : 'unblock'}`;

        const { data } = await originClient.patch(url, {
            is_blocked: !!isBlocked,
        });

        return data;
    }
}

export default new AdminUsersService();