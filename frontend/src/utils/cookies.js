// src/utils/cookies.js

/**
 * Egyszerű cookie segéd függvények
 * - getCookie(name): string | null
 * - setCookie(name, value, options): void
 * - deleteCookie(name, options): void
 */

export function getCookie(name) {
    if (typeof document === 'undefined') return null;
    const target = name + '=';
    const parts = document.cookie.split(';');
    for (let c of parts) {
        c = c.trim();
        if (c.startsWith(target)) {
            try {
                return decodeURIComponent(c.substring(target.length));
            } catch {
                return c.substring(target.length);
            }
        }
    }
    return null;
}

export function setCookie(
    name,
    value,
    {
        days = null,
        path = '/',
        domain = null,
        secure = false,
        sameSite = 'Lax',
    } = {},
) {
    if (typeof document === 'undefined') return;

    let cookie = `${name}=${encodeURIComponent(value)}; path=${path}`;

    if (days !== null) {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        cookie += `; expires=${date.toUTCString()}`;
    }

    if (domain) cookie += `; domain=${domain}`;
    if (secure) cookie += `; Secure`;
    if (sameSite) cookie += `; SameSite=${sameSite}`;

    document.cookie = cookie;
}

export function deleteCookie(name, { path = '/', domain = null } = {}) {
    if (typeof document === 'undefined') return;

    let cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=${path}`;

    if (domain) cookie += `; domain=${domain}`;
    
    document.cookie = cookie;
}
