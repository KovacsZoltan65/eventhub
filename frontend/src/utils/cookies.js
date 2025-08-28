// src/utils/cookies.js

/**
 * Egyszerű cookie segéd függvények
 * - getCookie(name): string | null
 * - setCookie(name, value, options): void
 * - deleteCookie(name, options): void
 */

/**
 * Visszaadja a megadott nevű cookie értékét, vagy null-t, ha nincs ilyen nevű cookie.
 * @param {string} name - a keresett cookie neve
 * @returns {string|null} - a keresett cookie értéke, vagy null, ha nincs ilyen nevű cookie
 */
export function getCookie(name) {
    if (typeof document === 'undefined') return null;
    const target = name + '=';
    const parts = document.cookie.split(';');
    for (let c of parts) {
        c = c.trim();
        if (c.startsWith(target)) {
            // decodeURIComponent() használata a cookie értékének dekódolásához
            // (pl. ha a cookie értéke URL-kódolt karaktereket tartalmaz)
            // https://developer.mozilla.org/en-US/docs/Web/API/Document/cookie#Notes
            try {
                return decodeURIComponent(c.substring(target.length));
            } catch {
                // ha a dekódolás sikertelen, akkor a cookie értékét változtatás nélkül adjuk vissza
                return c.substring(target.length);
            }
        }
    }
    return null;
}

/**
 * Beállítja a cookie-t a megadott névvel és értékkel.
 * @param {string} name - a cookie neve
 * @param {string} value - a cookie értéke
 * @param {Object} [options] - a beállítandó tulajdonságok
 * @param {number} [options.days] - a cookie érvényességi ideje (napokban)
 * @param {string} [options.path] - a cookie elérési útja (alapértelmezés: '/')
 * @param {string} [options.domain] - a cookie tartománya (alapértelmezés: null)
 * @param {boolean} [options.secure] - a cookie biztonsági beállítása (alapértelmezés: false)
 * @param {string} [options.sameSite] - a cookie SameSite tulajdonsága (alapértelmezés: 'Lax')
 */
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

/**
 * Törli a megadott nevű cookie-t.
 * @param {string} name - a törölni kívánt cookie neve
 * @param {Object} [options] - a törölt tulajdonságok
 * @param {string} [options.path] - a törölt cookie elérési útja (alapértelmezés: '/')
 * @param {string} [options.domain] - a törölt cookie tartománya (alapértelmezés: null)
 */
export function deleteCookie(name, { path = '/', domain = null } = {}) {
    if (typeof document === 'undefined') return;

    let cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=${path}`;

    if (domain) cookie += `; domain=${domain}`;
    
    // A cookie értékét egy üres stringre állítjuk, és a lejárati dátumot 1970-re,
    // hogy a cookie-t a böngésző törölje.
    document.cookie = cookie;
}
