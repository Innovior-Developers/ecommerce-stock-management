/* eslint-disable react-refresh/only-export-components */
import axios, { AxiosInstance, AxiosRequestConfig } from "axios";
import { API_CONFIG } from "./config";
import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import type { RootState } from "../index";

export interface ApiResponse<T = unknown> {
  success: boolean;
  message?: string;
  data?: T;
  user?: unknown;
  token?: string;
  refresh_token?: string;
  token_type?: string;
  expires_in?: number;
}

export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

class ApiService {
  private api: AxiosInstance;
  private isTokenCleared = false;
  private isRefreshing = false;

  constructor() {
    this.api = axios.create({
      baseURL: API_CONFIG.BASE_URL,
      timeout: API_CONFIG.TIMEOUT,
      headers: API_CONFIG.HEADERS,
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    // Request interceptor to add auth token and auto-refresh
    this.api.interceptors.request.use(
      async (config) => {
        const token = localStorage.getItem("auth_token");

        if (token) {
          // Check if token needs refresh before making request
          const tokenExpiry = localStorage.getItem("token_expiry");
          if (tokenExpiry) {
            const fiveMinutesFromNow = Date.now() + 5 * 60 * 1000;
            const expiryTime = parseInt(tokenExpiry);

            if (
              expiryTime <= fiveMinutesFromNow &&
              !this.isRefreshing &&
              !config.url?.includes("/auth/refresh")
            ) {
              try {
                this.isRefreshing = true;
                console.log("🔄 Auto-refreshing token before request");

                const { authService } = await import("./Auth");
                await authService.refreshToken();

                // Get the new token
                const newToken = localStorage.getItem("auth_token");
                if (newToken) {
                  config.headers.Authorization = `Bearer ${newToken}`;
                }
              } catch (error) {
                console.error("Auto token refresh failed:", error);
                // Continue with original token
                config.headers.Authorization = `Bearer ${token}`;
              } finally {
                this.isRefreshing = false;
              }
            } else {
              config.headers.Authorization = `Bearer ${token}`;
            }
          } else {
            config.headers.Authorization = `Bearer ${token}`;
          }

          console.log(
            `🔐 Token added to ${config.method?.toUpperCase()} ${config.url}`
          );
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

    // Response interceptor to handle 401 errors
    this.api.interceptors.response.use(
      (response) => {
        console.log(
          `✅ ${response.config.method?.toUpperCase()} ${response.config.url}:`,
          response.data
        );
        return response;
      },
      async (error) => {
        console.error(`❌ API Error Response:`, error.response?.data);

        if (error.response?.status === 401) {
          const isLogoutEndpoint = error.config?.url?.includes("/auth/logout");
          const isRefreshEndpoint =
            error.config?.url?.includes("/auth/refresh");

          if (isLogoutEndpoint) {
            console.log(
              "ℹ️ 401 on logout endpoint is expected when token is already cleared"
            );
          } else if (isRefreshEndpoint) {
            console.warn("🚨 Refresh token failed, clearing auth data");
            this.isTokenCleared = true;
            localStorage.removeItem("auth_token");
            localStorage.removeItem("refresh_token");
            localStorage.removeItem("user");
            localStorage.removeItem("token_expiry");

            setTimeout(() => {
              if (window.location.pathname.includes("/admin")) {
                window.location.href = "/login";
              }
            }, 1000);
          } else if (!this.isTokenCleared && !this.isRefreshing) {
            console.warn("🔄 401 Unauthorized - attempting token refresh");

            try {
              this.isRefreshing = true;
              const { authService } = await import("./Auth");
              await authService.refreshToken();

              // Retry the original request with new token
              const newToken = localStorage.getItem("auth_token");
              if (newToken && error.config) {
                error.config.headers.Authorization = `Bearer ${newToken}`;
                return this.api.request(error.config);
              }
            } catch (refreshError) {
              console.error("Token refresh failed:", refreshError);
              this.isTokenCleared = true;
              localStorage.removeItem("auth_token");
              localStorage.removeItem("refresh_token");
              localStorage.removeItem("user");
              localStorage.removeItem("token_expiry");

              setTimeout(() => {
                if (window.location.pathname.includes("/admin")) {
                  window.location.href = "/login";
                }
              }, 1000);
            } finally {
              this.isRefreshing = false;
            }
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
      const response = await this.api.post(endpoint, data, {
        ...config,
        headers:
          data instanceof FormData
            ? undefined
            : { "Content-Type": "application/json" },
      });
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

  private handleError(error: unknown): never {
    if (axios.isAxiosError(error)) {
      const apiError: ApiError = {
        success: false,
        message:
          error.response?.data?.message || error.message || "An error occurred",
        errors: error.response?.data?.errors,
      };
      throw apiError;
    }
    throw error;
  }
}

export const apiService = new ApiService();

const adminBaseQuery = fetchBaseQuery({
  baseUrl: "http://localhost:8000/api/admin",
  prepareHeaders: (headers, { getState }) => {
    const token = (getState() as RootState).auth.token;
    if (token) {
      headers.set("authorization", `Bearer ${token}`);
    }
    headers.set("accept", "application/json");
    // ✅ Don't set content-type here - let it be auto-detected for FormData
    return headers;
  },
});

export const adminApi = createApi({
  reducerPath: "adminApi",
  baseQuery: adminBaseQuery,
  tagTypes: ["Product", "Category", "Customer", "Order", "Inventory"],
  endpoints: (builder) => ({
    // Products
    getProducts: builder.query<unknown, { search?: string }>({
      query: ({ search }) => ({
        url: "/products",
        params: search ? { search } : {},
      }),
      providesTags: ["Product"],
    }),

    createProduct: builder.mutation<unknown, unknown>({
      query: (productData) => ({
        url: "/products",
        method: "POST",
        body: productData,
      }),
      invalidatesTags: ["Product", "Inventory"],
    }),

    updateProduct: builder.mutation<unknown, { id: string; data: unknown }>({
      query: ({ id, data }) => ({
        url: `/products/${id}`,
        method: "PUT",
        body: data,
      }),
      invalidatesTags: ["Product", "Inventory"],
    }),

    deleteProduct: builder.mutation<unknown, string>({
      query: (id) => ({
        url: `/products/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Product", "Inventory"],
    }),

    // Categories
    getCategories: builder.query<unknown, { search?: string }>({
      query: ({ search }) => ({
        url: "/categories",
        params: search ? { search } : {},
      }),
      providesTags: ["Category"],
    }),

    createCategory: builder.mutation<unknown, unknown>({
      query: (categoryData) => ({
        url: "/categories",
        method: "POST",
        body: categoryData,
      }),
      invalidatesTags: ["Category"],
    }),

    updateCategory: builder.mutation<unknown, { id: string; data: unknown }>({
      query: ({ id, data }) => ({
        url: `/categories/${id}`,
        method: "PUT",
        body: data,
      }),
      invalidatesTags: ["Category"],
    }),

    deleteCategory: builder.mutation<unknown, string>({
      query: (id) => ({
        url: `/categories/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Category"],
    }),

    // Customers
    getCustomers: builder.query<unknown, { search?: string }>({
      query: ({ search }) => ({
        url: "/customers",
        params: search ? { search } : {},
      }),
      providesTags: ["Customer"],
    }),

    updateCustomer: builder.mutation<unknown, { id: string; data: unknown }>({
      query: ({ id, data }) => ({
        url: `/customers/${id}`,
        method: "PUT",
        body: data,
      }),
      invalidatesTags: ["Customer"],
    }),

    deleteCustomer: builder.mutation<unknown, string>({
      query: (id) => ({
        url: `/customers/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Customer"],
    }),

    // Orders
    getOrders: builder.query<unknown, { search?: string }>({
      query: ({ search }) => ({
        url: "/orders",
        params: search ? { search } : {},
      }),
      providesTags: ["Order"],
    }),

    updateOrder: builder.mutation<unknown, { id: string; data: unknown }>({
      query: ({ id, data }) => ({
        url: `/orders/${id}`,
        method: "PUT",
        body: data,
      }),
      invalidatesTags: ["Order"],
    }),

    deleteOrder: builder.mutation<unknown, string>({
      query: (id) => ({
        url: `/orders/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Order"],
    }),

    // Inventory
    getStockLevels: builder.query<unknown, void>({
      query: () => "/inventory/stock-levels",
      providesTags: ["Inventory"],
    }),

    getLowStock: builder.query<unknown, void>({
      query: () => "/inventory/low-stock",
      providesTags: ["Inventory"],
    }),

    updateStock: builder.mutation<unknown, { id: string; data: unknown }>({
      query: ({ id, data }) => ({
        url: `/inventory/${id}`,
        method: "PUT",
        body: data,
      }),
      invalidatesTags: ["Inventory"],
    }),
  }),
});

export const {
  useGetProductsQuery,
  useCreateProductMutation,
  useUpdateProductMutation,
  useDeleteProductMutation,
  useGetCategoriesQuery,
  useCreateCategoryMutation,
  useUpdateCategoryMutation,
  useDeleteCategoryMutation,
  useGetCustomersQuery,
  useUpdateCustomerMutation,
  useDeleteCustomerMutation,
  useGetOrdersQuery,
  useUpdateOrderMutation,
  useDeleteOrderMutation,
  useGetStockLevelsQuery,
  useGetLowStockQuery,
  useUpdateStockMutation,
} = adminApi;
