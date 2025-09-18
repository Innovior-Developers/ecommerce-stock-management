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

export interface AuthResponse {
  success: boolean;
  message: string;
  user: {
    id: string;
    name: string;
    email: string;
    role: "admin" | "customer";
    avatar?: string;
  };
  token: string;
  refresh_token?: string;
  token_type: string;
  expires_in?: number;
}

export const authApi = createApi({
  reducerPath: "authApi",
  baseQuery: fetchBaseQuery({
    baseUrl: "http://localhost:8000/api/auth",
    prepareHeaders: (headers, { getState }) => {
      const token = (getState() as RootState).auth.token;
      if (token) {
        headers.set("authorization", `Bearer ${token}`);
      }
      // ✅ Add these two headers
      headers.set("Accept", "application/json");
      headers.set("Content-Type", "application/json");
      return headers;
    },
  }),
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
    getCurrentUser: builder.query<{ success: boolean; user: unknown }, void>({
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
