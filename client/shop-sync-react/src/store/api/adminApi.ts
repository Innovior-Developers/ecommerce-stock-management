import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import type { RootState } from "../index";

export const adminApi = createApi({
  reducerPath: "adminApi",
  baseQuery: fetchBaseQuery({
    baseUrl: "http://localhost:8000/api/admin",
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

    // Orders
    getOrders: builder.query<unknown, void>({
      query: () => "/orders",
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
        url: `/inventory/stock/${id}`,
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
  useGetOrdersQuery,
  useUpdateOrderMutation,
  useGetStockLevelsQuery,
  useGetLowStockQuery,
  useUpdateStockMutation,
} = adminApi;
