/**
 * Standardized API Fetch Utility for HRConnect
 * Guards: try-catch, status checking, JSON parsing, toast notifications
 * Supports: JSON, FormData, Blob responses, timeouts
 */

export const apiFetch = async (url, options = {}) => {
    const {
        timeout = 30000,
        responseType = 'json', // 'json' | 'blob' | 'text'
        ...fetchOptions
    } = options;

    // Detect FormData - don't set Content-Type, let browser set boundary
    const isFormData = fetchOptions.body instanceof FormData;
    
    const defaultHeaders = {
        'X-Requested-With': 'XMLHttpRequest',
        ...window.apiHeaders()
    };

    if (!isFormData) {
        defaultHeaders['Content-Type'] = 'application/json';
    }

    const config = {
        ...fetchOptions,
        headers: { ...defaultHeaders, ...fetchOptions.headers }
    };

    // Create AbortController for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);
    config.signal = controller.signal;

    try {
        const response = await fetch(url, config);
        clearTimeout(timeoutId);

        // Handle unauthorized or server errors before parsing
        if (!response.ok) {
            let errorMessage = `Error ${response.status}: ${response.statusText}`;
            
            // Try to parse error response
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } catch {
                    // Ignore JSON parse error, use default message
                }
            } else if (responseType === 'text') {
                try {
                    const text = await response.text();
                    errorMessage = text || errorMessage;
                } catch {
                    // Ignore
                }
            }
            
            const error = new Error(errorMessage);
            error.status = response.status;
            error.response = response;
            throw error;
        }

        // Parse based on responseType
        switch (responseType) {
            case 'blob':
                return await response.blob();
            case 'text':
                return await response.text();
            case 'json':
            default:
                return await response.json();
        }
    } catch (error) {
        clearTimeout(timeoutId);
        
        // Handle abort/timeout
        if (error.name === 'AbortError') {
            const timeoutError = new Error('Request timeout');
            timeoutError.status = 408;
            throw timeoutError;
        }

        // Unified Error Logging
        console.error('HRConnect API Error:', error);
        
        // Use Global Toast System (from Phase 2)
        if (window.showToast) {
            window.showToast(error.message, 'error');
        }
        
        throw error; // Re-throw for specific component handling if needed
    }
};

// Convenience methods
export const apiGet = (url, options = {}) => apiFetch(url, { ...options, method: 'GET' });
export const apiPost = (url, data, options = {}) => apiFetch(url, { ...options, method: 'POST', body: data instanceof FormData ? data : JSON.stringify(data) });
export const apiPut = (url, data, options = {}) => apiFetch(url, { ...options, method: 'PUT', body: data instanceof FormData ? data : JSON.stringify(data) });
export const apiPatch = (url, data, options = {}) => apiFetch(url, { ...options, method: 'PATCH', body: data instanceof FormData ? data : JSON.stringify(data) });
export const apiDelete = (url, options = {}) => apiFetch(url, { ...options, method: 'DELETE' });
export const apiDownload = (url, options = {}) => apiFetch(url, { ...options, method: 'GET', responseType: 'blob' });