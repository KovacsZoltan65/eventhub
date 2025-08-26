import BaseService from "@/services/BaseService.js";

class BookingService extends BaseService
{
    constructor() {
        super();
        this.url = "/bookings";
    }

    /**
     * payload: { event_id: number, quantity: number }
     * várható válasz: { bookingId, quantity, totalPrice?, timestamp? }  – a backendedtől függően
     */
    //async create(payload)
    //{
    //    const res = await this.post(this.url, payload, { /* withCredentials marad default a HttpClient-ben */ });
    //    return res.data;
    //}


    async create(payload)
    {
        // ha még nincs auth és CORS kész, hagyhatod defaultot
        // ha nincs kész a CORS, ideiglenesen:
        // return (await this.post(this.url, payload, { withCredentials: false })).data;
        return (await this.post(this.url, payload)).data;
    }

}

export default new BookingService();