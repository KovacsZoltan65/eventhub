import { v4 as uuidv4 } from 'uuid';
import { apiClient } from './HttpClient.js';

class ErrorService {
  logClientError(error, additionalData = {}) {
    const payload = {
      message: error?.message ?? 'Unknown error',
      stack: error?.stack ?? null,
      component: error?.component ?? 'Unknown',
      category: additionalData.category ?? 'unknown_error',
      priority: additionalData.priority ?? 'low',
      data: additionalData.data ?? null,
      info: error?.info ?? 'No additional info',
      additionalInfo: additionalData.additionalInfo ?? null,
      time: new Date().toISOString(),
      route: window.location.pathname,
      url: window.location.href,
      userAgent: navigator.userAgent,
      uniqueErrorId: uuidv4(),
      // ...additionalData, // ha tényleg mindent rá akarsz önteni
    };

    // backend oldali endpointod szerint
    return apiClient.post('/client-errors', payload);
  }
}

export default new ErrorService();
