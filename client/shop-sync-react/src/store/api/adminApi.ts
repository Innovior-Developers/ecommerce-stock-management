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
      headers.set("Accept", "application/json");
      // ✅ FIX: Do not set Content-Type here. Let the browser handle it for FormData.
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

    createProduct: builder.mutation<unknown, FormData>({
      query: (formData) => ({
        url: "/products",
        method: "POST",
        body: formData,
      }),
      invalidatesTags: ["Product", "Inventory"],
      // ✅ Add this to force refresh
      async onQueryStarted(arg, { dispatch, queryFulfilled }) {
        try {
          await queryFulfilled;
          // Force refresh the products list
          dispatch(adminApi.util.invalidateTags(["Product"]));
        } catch {
          /* empty */
        }
      },
    }),

    updateProduct: builder.mutation<unknown, { id: string; data: FormData }>({
      query: ({ id, data }) => ({
        url: `/products/${id}`,
        method: "POST", // ✅ Change PUT to POST
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
      async onQueryStarted(id, { dispatch, queryFulfilled }) {
        try {
          await queryFulfilled;
          // Force refresh the products list
          dispatch(adminApi.util.invalidateTags(["Product", "Inventory"]));
        } catch {
          /* empty */
        }
      },
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
