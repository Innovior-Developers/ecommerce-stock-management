/* eslint-disable react-refresh/only-export-components */
import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from "axios";
import { API_CONFIG } from "./config";

export interface ApiResponse<T = unknown> {
  success: boolean;
  message?: string;
  data?: T;
  user?: unknown;
  token?: string;
  token_type?: string;
}

export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

class ApiService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: API_CONFIG.BASE_URL,
      timeout: API_CONFIG.TIMEOUT,
      headers: API_CONFIG.HEADERS,
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    // Request interceptor to add auth token
    this.api.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem("auth_token");
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor for error handling
    this.api.interceptors.response.use(
      (response: AxiosResponse) => response,
      (error) => {
        if (error.response?.status === 401) {
          // Token expired or invalid
          localStorage.removeItem("auth_token");
          localStorage.removeItem("user");
          window.location.href = "/login";
        }
        return Promise.reject(error);
      }
    );
  }

  async get<T = unknown>(
    endpoint: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await this.api.get(endpoint, config);
      return response.data;
    } catch (error: unknown) {
      throw this.handleError(error);
    }
  }

  async post<T = unknown>(
    endpoint: string,
    data?: unknown,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await this.api.post(endpoint, data, config);
      return response.data;
    } catch (error: unknown) {
      throw this.handleError(error);
    }
  }

  async put<T = unknown>(
    endpoint: string,
    data?: unknown,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await this.api.put(endpoint, data, config);
      return response.data;
    } catch (error: unknown) {
      throw this.handleError(error);
    }
  }

  async delete<T = unknown>(
    endpoint: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await this.api.delete(endpoint, config);
      return response.data;
    } catch (error: unknown) {
      throw this.handleError(error);
    }
  }

  private handleError(error: unknown): ApiError {
    console.error("API Error:", error);

    if (axios.isAxiosError(error)) {
      if (error.response) {
        // Server responded with error status
        return {
          success: false,
          message:
            error.response.data?.message ||
            `Server error: ${error.response.status}`,
          errors: error.response.data?.errors,
        };
      } else if (error.request) {
        // Network error
        return {
          success: false,
          message:
            "Network error. Please check your connection and ensure the backend server is running.",
        };
      }
    }

    return {
      success: false,
      message:
        error instanceof Error ? error.message : "An unexpected error occurred",
    };
  }

  // Health check method
  async healthCheck(): Promise<ApiResponse> {
    return this.get("/health");
  }
}

// Products API
export async function getAdminProducts(params?: unknown) {
  return apiService.get(API_ENDPOINTS.ADMIN.PRODUCTS, { params });
}

export async function createAdminProduct(data: unknown) {
  return apiService.post(API_ENDPOINTS.ADMIN.PRODUCTS, data);
}

export async function updateAdminProduct(id: string, data: unknown) {
  return apiService.put(`${API_ENDPOINTS.ADMIN.PRODUCTS}/${id}`, data);
}

export async function deleteAdminProduct(id: string) {
  return apiService.delete(`${API_ENDPOINTS.ADMIN.PRODUCTS}/${id}`);
}

// Categories API
export async function getAdminCategories(params?: unknown) {
  return apiService.get(API_ENDPOINTS.ADMIN.CATEGORIES, { params });
}

export async function createAdminCategory(data: unknown) {
  return apiService.post(API_ENDPOINTS.ADMIN.CATEGORIES, data);
}

export async function updateAdminCategory(id: string, data: unknown) {
  return apiService.put(`${API_ENDPOINTS.ADMIN.CATEGORIES}/${id}`, data);
}

export async function deleteAdminCategory(id: string) {
  return apiService.delete(`${API_ENDPOINTS.ADMIN.CATEGORIES}/${id}`);
}

// Customers API
export async function getAdminCustomers(params?: unknown) {
  return apiService.get(API_ENDPOINTS.ADMIN.CUSTOMERS, { params });
}

export async function updateAdminCustomer(id: string, data: unknown) {
  return apiService.put(`${API_ENDPOINTS.ADMIN.CUSTOMERS}/${id}`, data);
}

export async function deleteAdminCustomer(id: string) {
  return apiService.delete(`${API_ENDPOINTS.ADMIN.CUSTOMERS}/${id}`);
}

// Orders API
export async function getAdminOrders(params?: unknown) {
  return apiService.get(API_ENDPOINTS.ADMIN.ORDERS, { params });
}

export async function updateAdminOrder(id: string, data: unknown) {
  return apiService.put(`${API_ENDPOINTS.ADMIN.ORDERS}/${id}`, data);
}

export async function deleteAdminOrder(id: string) {
  return apiService.delete(`${API_ENDPOINTS.ADMIN.ORDERS}/${id}`);
}

// Inventory API
export async function getStockLevels() {
  return apiService.get(`${API_ENDPOINTS.ADMIN.INVENTORY}/stock-levels`);
}

export async function getLowStock() {
  return apiService.get(`${API_ENDPOINTS.ADMIN.INVENTORY}/low-stock`);
}

export async function updateStock(id: string, data: unknown) {
  return apiService.put(`${API_ENDPOINTS.ADMIN.INVENTORY}/${id}`, data);
}

// eslint-disable-next-line react-refresh/only-export-components
export const apiService = new ApiService();
export default ApiService;
