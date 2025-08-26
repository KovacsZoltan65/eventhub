/**
 * Alkalmazásszintű konstansok.
 *
 * @constant
 * @type {Object}
 * @property {string} BASE_URL - Az API alap URL-címe. Helyi fejlesztés esetén alapértelmezés szerint üres karakterlánc.
 * @property {string} API_VERSION - Az API verziója.
 * @property {number} TIMEOUT - Az API-kérések időkorlátja milliszekundumban. Alapértelmezés szerint 10 másodperc.
 */
export const CONFIG = {
    BASE_URL: "http://localhost:8000/api/",
    API_VERSION: "v1",
    TIMEOUT: 10000,
};