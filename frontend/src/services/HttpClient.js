import axios from "axios";
import { CONFIG } from "../helpers/constants.js";

export const apiClient = axios.create({
    baseURL: CONFIG.BASE_URL,
    headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
    },
    withCredentials: true,
});