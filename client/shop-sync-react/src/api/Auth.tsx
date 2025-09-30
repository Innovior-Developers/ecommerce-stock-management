import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import type { RootState } from "../index";
import { setCredentials, clearCredentials } from "../slices/authSlice";

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  first_name?: string;
  last_name?: string;
  phone?: string;
}

export interface User {
  id: string;
  name: string;
  email: string;
  role: "admin" | "customer";
  avatar?: string;
  provider?: string;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  user: User;
  token: string;
  refresh_token?: string;
  token_type: string;
  expires_in?: number;
}

// Base query with automatic token handling
const authBaseQuery = fetchBaseQuery({
  baseUrl: "http://localhost:8000/api/auth",
  prepareHeaders: (headers, { getState }) => {
    const token = (getState() as RootState).auth.token;
    if (token) {
      headers.set("authorization", `Bearer ${token}`);
    }
    headers.set("accept", "application/json");
    headers.set("content-type", "application/json");
    return headers;
  },
});

// Enhanced base query with automatic token refresh
const authBaseQueryWithReauth = async (
  args: unknown,
  api: unknown,
  extraOptions: unknown
) => {
  let result = await authBaseQuery(args, api, extraOptions);

  if (result.error && result.error.status === 401) {
    console.log("🔄 Token expired, attempting refresh...");

    // Try to refresh token
    const refreshResult = await authBaseQuery(
      { url: "/refresh", method: "POST" },
      api,
      extraOptions
    );

    if (refreshResult.data) {
      const refreshData = refreshResult.data as AuthResponse;
      if (refreshData.success) {
        // Store new token
        api.dispatch(
          setCredentials({
            user: refreshData.user,
            token: refreshData.token,
            refreshToken: refreshData.refresh_token,
          })
        );

        // Retry original request with new token
        result = await authBaseQuery(args, api, extraOptions);
      } else {
        // Refresh failed, clear credentials
        api.dispatch(clearCredentials());
      }
    } else {
      // Refresh failed, clear credentials
      api.dispatch(clearCredentials());
    }
  }

  return result;
};

export const authApi = createApi({
  reducerPath: "authApi",
  baseQuery: authBaseQueryWithReauth,
  tagTypes: ["User"],
  endpoints: (builder) => ({
    // Admin login
    adminLogin: builder.mutation<AuthResponse, LoginCredentials>({
      query: (credentials) => ({
        url: "/admin/login",
        method: "POST",
        body: credentials,
      }),
      async onQueryStarted(arg, { dispatch, queryFulfilled }) {
        try {
          const { data } = await queryFulfilled;
          if (data.success) {
            dispatch(
              setCredentials({
                user: data.user,
                token: data.token,
                refreshToken: data.refresh_token,
              })
            );
          }
        } catch (error) {
          console.error("Admin login failed:", error);
        }
      },
    }),

    // Customer login
    customerLogin: builder.mutation<AuthResponse, LoginCredentials>({
      query: (credentials) => ({
        url: "/customer/login",
        method: "POST",
        body: credentials,
      }),
      async onQueryStarted(arg, { dispatch, queryFulfilled }) {
        try {
          const { data } = await queryFulfilled;
          if (data.success) {
            dispatch(
              setCredentials({
                user: data.user,
                token: data.token,
                refreshToken: data.refresh_token,
              })
            );
          }
        } catch (error) {
          console.error("Customer login failed:", error);
        }
      },
    }),

    // Customer register
    customerRegister: builder.mutation<AuthResponse, RegisterData>({
      query: (userData) => ({
        url: "/customer/register",
        method: "POST",
        body: userData,
      }),
      async onQueryStarted(arg, { dispatch, queryFulfilled }) {
        try {
          const { data } = await queryFulfilled;
          if (data.success) {
            dispatch(
              setCredentials({
                user: data.user,
                token: data.token,
                refreshToken: data.refresh_token,
              })
            );
          }
        } catch (error) {
          console.error("Registration failed:", error);
        }
      },
    }),

    // Get current user
    getCurrentUser: builder.query<{ success: boolean; user: User }, void>({
      query: () => "/user",
      providesTags: ["User"],
    }),

    // Logout
    logout: builder.mutation<{ success: boolean; message: string }, void>({
      query: () => ({
        url: "/logout",
        method: "POST",
      }),
      async onQueryStarted(arg, { dispatch, queryFulfilled }) {
        try {
          await queryFulfilled;
          dispatch(clearCredentials());
        } catch (error) {
          // Clear credentials even if API call fails
          console.warn("Logout API failed, clearing local data anyway");
          dispatch(clearCredentials());
        }
      },
    }),

    // Refresh token
    refreshToken: builder.mutation<AuthResponse, void>({
      query: () => ({
        url: "/refresh",
        method: "POST",
      }),
      async onQueryStarted(arg, { dispatch, queryFulfilled }) {
        try {
          const { data } = await queryFulfilled;
          if (data.success) {
            dispatch(
              setCredentials({
                user: data.user,
                token: data.token,
                refreshToken: data.refresh_token,
              })
            );
          }
        } catch (error) {
          dispatch(clearCredentials());
        }
      },
    }),
  }),
});

export const {
  useAdminLoginMutation,
  useCustomerLoginMutation,
  useCustomerRegisterMutation,
  useGetCurrentUserQuery,
  useLogoutMutation,
  useRefreshTokenMutation,
} = authApi;

// Legacy compatibility functions (if needed for gradual migration)
export const authService = {
  getSocialLoginUrl: (provider: "google" | "github") => {
    return `http://localhost:8000/api/auth/social/${provider}`;
  },
};
