/* eslint-disable react-refresh/only-export-components */
import axios, { AxiosInstance, AxiosRequestConfig } from "axios";
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
  private isTokenCleared = false; // Add this flag

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
          console.log(
            `🔐 Token added to ${config.method?.toUpperCase()} ${config.url}`
          );
          // Reset the flag when we have a token
          this.isTokenCleared = false;
        } else {
          const protectedEndpoints = ["/admin/", "/auth/logout", "/auth/user"];
          const needsToken = protectedEndpoints.some((endpoint) =>
            config.url?.includes(endpoint)
          );

          if (needsToken && !this.isTokenCleared) {
            console.warn(
              `⚠️  No token for protected endpoint: ${config.method?.toUpperCase()} ${
                config.url
              }`
            );
          }
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor to handle common errors
    this.api.interceptors.response.use(
      (response) => {
        console.log(
          `✅ ${response.config.method?.toUpperCase()} ${response.config.url}:`,
          response.data
        );
        return response;
      },
      (error) => {
        console.error(`❌ API Error Response:`, error.response?.data);

        if (error.response?.status === 401) {
          const isLogoutEndpoint = error.config?.url?.includes("/auth/logout");

          if (isLogoutEndpoint) {
            console.log(
              "ℹ️ 401 on logout endpoint is expected when token is already cleared"
            );
          } else if (!this.isTokenCleared) {
            // Only clear token once to prevent cascade
            console.warn(
              "🚨 401 Unauthorized - clearing invalid token (first time)"
            );
            this.isTokenCleared = true;
            localStorage.removeItem("auth_token");
            localStorage.removeItem("user");

            // Optionally redirect to login after a delay to prevent immediate cascade
            setTimeout(() => {
              if (window.location.pathname.includes("/admin")) {
                window.location.href = "/login";
              }
            }, 1000);
          } else {
            console.log(
              "🔄 401 Unauthorized - token already cleared, skipping"
            );
          }
        }
        return Promise.reject(error);
      }
    );
  }

  // Reset token cleared flag when getting new token
  setToken(token: string) {
    localStorage.setItem("auth_token", token);
    this.isTokenCleared = false;
  }

  async get<T = unknown>(
    endpoint: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await this.api.get(endpoint, config);
      return response.data;
    } catch (error) {
      console.error(`GET ${endpoint} failed:`, error);
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
    } catch (error) {
      console.error(`POST ${endpoint} failed:`, error);
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
    } catch (error) {
      console.error(`PUT ${endpoint} failed:`, error);
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
    } catch (error) {
      console.error(`DELETE ${endpoint} failed:`, error);
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
            "Network error. Please check your connection and ensure the backend server is running at http://localhost:8000",
        };
      } else if (error.code === "ECONNABORTED") {
        // Timeout error
        return {
          success: false,
          message:
            "Request timeout. Please check if the backend server is running and responding.",
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

export const apiService = new ApiService();

// Products API
export async function getAdminProducts(params?: unknown) {
  console.log("Calling getAdminProducts with params:", params);
  return apiService.get("/admin/products", { params });
}

export async function createAdminProduct(data: unknown) {
  console.log("Calling createAdminProduct with data:", data);
  return apiService.post("/admin/products", data);
}

export async function updateAdminProduct(id: string, data: unknown) {
  console.log("Calling updateAdminProduct with id:", id, "data:", data);
  return apiService.put(`/admin/products/${id}`, data);
}

export async function deleteAdminProduct(id: string) {
  console.log("Calling deleteAdminProduct with id:", id);
  return apiService.delete(`/admin/products/${id}`);
}

// Categories API
export async function getAdminCategories(params?: unknown) {
  console.log("Calling getAdminCategories with params:", params);
  return apiService.get("/admin/categories", { params });
}

export async function createAdminCategory(data: unknown) {
  console.log("Calling createAdminCategory with data:", data);
  return apiService.post("/admin/categories", data);
}

export async function updateAdminCategory(id: string, data: unknown) {
  console.log("Calling updateAdminCategory with id:", id, "data:", data);
  return apiService.put(`/admin/categories/${id}`, data);
}

export async function deleteAdminCategory(id: string) {
  console.log("Calling deleteAdminCategory with id:", id);
  return apiService.delete(`/admin/categories/${id}`);
}

// Customers API
export async function getAdminCustomers(params?: unknown) {
  console.log("Calling getAdminCustomers with params:", params);
  return apiService.get("/admin/customers", { params });
}

export async function updateAdminCustomer(id: string, data: unknown) {
  return apiService.put(`/admin/customers/${id}`, data);
}

export async function deleteAdminCustomer(id: string) {
  return apiService.delete(`/admin/customers/${id}`);
}

// Orders API
export async function getAdminOrders(params?: unknown) {
  console.log("Calling getAdminOrders with params:", params);
  return apiService.get("/admin/orders", { params });
}

export async function updateAdminOrder(id: string, data: unknown) {
  return apiService.put(`/admin/orders/${id}`, data);
}

export async function deleteAdminOrder(id: string) {
  return apiService.delete(`/admin/orders/${id}`);
}

// Inventory API
export async function getStockLevels() {
  console.log("Calling getStockLevels");
  return apiService.get("/admin/inventory/stock-levels");
}

export async function getLowStock() {
  console.log("Calling getLowStock");
  return apiService.get("/admin/inventory/low-stock");
}

export async function updateStock(id: string, data: unknown) {
  console.log("Calling updateStock with id:", id, "data:", data);
  return apiService.put(`/admin/inventory/${id}`, data);
}

export default ApiService;
