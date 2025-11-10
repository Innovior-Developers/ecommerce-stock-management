import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import type { RootState } from "..";
import {
  normalizeMongoObject,
  normalizeMongoArray,
} from "@/utils/normalizeMongoId";

const adminBaseQuery = fetchBaseQuery({
  baseUrl: "http://localhost:8000/api/admin",
  prepareHeaders: (headers, { getState }) => {
    const token = (getState() as RootState).auth.token;
    if (token) {
      headers.set("authorization", `Bearer ${token}`);
    }
    headers.set("accept", "application/json");
    // ✅ DON'T set content-type here - let browser set it for FormData
    return headers;
  },
});

export const adminApi = createApi({
  reducerPath: "adminApi",
  baseQuery: adminBaseQuery,
  tagTypes: ["Product", "Category", "Customer", "Order", "Inventory"],
  endpoints: (builder) => ({
    // Products
    getProducts: builder.query({
      query: ({ search = "" }) => `/products?search=${search}`,
      transformResponse: (response: unknown) => {
        const data = response?.data?.data || response?.data || [];
        return {
          ...response,
          data: {
            ...response.data,
            data: normalizeMongoArray(data),
          },
        };
      },
      providesTags: ["Product"],
    }),

    createProduct: builder.mutation({
      query: (formData) => ({
        url: "/products",
        method: "POST",
        body: formData,
        // ✅ Remove headers - browser will set correct multipart/form-data
      }),
      transformResponse: (response: unknown) => {
        return {
          ...response,
          data: normalizeMongoObject(response.data),
        };
      },
      invalidatesTags: ["Product", "Inventory"],
    }),

    // ✅ FIX: Change from PUT to POST for file upload support
    updateProduct: builder.mutation({
      query: ({ id, data }) => ({
        url: `/products/${id}`,
        method: "POST", // ✅ Changed from PUT to POST
        body: data,
        // ✅ Remove headers - browser will set correct multipart/form-data
      }),
      transformResponse: (response: unknown) => {
        return {
          ...response,
          data: normalizeMongoObject(response.data),
        };
      },
      invalidatesTags: ["Product", "Inventory"],
    }),

    deleteProduct: builder.mutation({
      query: (id) => ({
        url: `/products/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Product", "Inventory"],
    }),

    // Categories
    getCategories: builder.query({
      query: ({ search = "" }) => `/categories?search=${search}`,
      transformResponse: (response: unknown) => {
        const data = response?.data?.data || response?.data || [];
        return {
          ...response,
          data: {
            ...response.data,
            data: normalizeMongoArray(data),
          },
        };
      },
      providesTags: ["Category"],
    }),

    createCategory: builder.mutation({
      query: (data) => ({
        url: "/categories",
        method: "POST",
        body: data,
        headers: {
          "content-type": "application/json", // ✅ Categories use JSON
        },
      }),
      transformResponse: (response: unknown) => {
        return {
          ...response,
          data: normalizeMongoObject(response.data),
        };
      },
      invalidatesTags: ["Category"],
    }),

    updateCategory: builder.mutation({
      query: ({ id, data }) => ({
        url: `/categories/${id}`,
        method: "PUT", // ✅ Categories can use PUT (no files)
        body: data,
        headers: {
          "content-type": "application/json", // ✅ Categories use JSON
        },
      }),
      transformResponse: (response: unknown) => {
        return {
          ...response,
          data: normalizeMongoObject(response.data),
        };
      },
      invalidatesTags: ["Category"],
    }),

    deleteCategory: builder.mutation({
      query: (id) => ({
        url: `/categories/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Category"],
    }),

    // Customers
    getCustomers: builder.query({
      query: ({ search = "" }) => `/customers?search=${search}`,
      transformResponse: (response: unknown) => {
        const data = response?.data?.data || response?.data || [];
        return {
          ...response,
          data: {
            ...response.data,
            data: normalizeMongoArray(data),
          },
        };
      },
      providesTags: ["Customer"],
    }),

    // Orders
    getOrders: builder.query({
      query: () => "/orders",
      transformResponse: (response: unknown) => {
        const data = response?.data?.data || response?.data || [];
        return {
          ...response,
          data: {
            ...response.data,
            data: normalizeMongoArray(data),
          },
        };
      },
      providesTags: ["Order"],
    }),

    updateOrder: builder.mutation({
      query: ({ id, status }) => ({
        url: `/orders/${id}`,
        method: "PUT",
        body: { status },
        headers: {
          "content-type": "application/json",
        },
      }),
      transformResponse: (response: unknown) => {
        return {
          ...response,
          data: normalizeMongoObject(response.data),
        };
      },
      invalidatesTags: ["Order"],
    }),

    // Inventory
    getStockLevels: builder.query({
      query: () => "/inventory",
      transformResponse: (response: unknown) => {
        const data = response?.data || [];
        return normalizeMongoArray(data);
      },
      providesTags: ["Inventory"],
    }),

    getLowStock: builder.query({
      query: () => "/inventory/low-stock",
      transformResponse: (response: unknown) => {
        const data = response?.data || [];
        return normalizeMongoArray(data);
      },
      providesTags: ["Inventory"],
    }),

    updateStock: builder.mutation({
      query: ({ id, data }) => ({
        url: `/inventory/${id}`,
        method: "PUT",
        body: data,
        headers: {
          "content-type": "application/json",
        },
      }),
      transformResponse: (response: unknown) => {
        return {
          ...response,
          data: normalizeMongoObject(response.data),
        };
      },
      invalidatesTags: ["Inventory", "Product"],
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
  useGetOrdersQuery,
  useUpdateOrderMutation,
  useGetStockLevelsQuery,
  useGetLowStockQuery,
  useUpdateStockMutation,
} = adminApi;
