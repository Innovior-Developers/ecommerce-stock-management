import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";

export interface Product {
  _id: string;
  name: string;
  description: string;
  price: number;
  category: string;
  stock_quantity: number;
  status: string;
  sku?: string;
  images?: Array<{
    url: string;
    is_primary?: boolean;
    filename?: string;
  }>;
  image_url?: string;
  weight?: number;
  meta_title?: string;
  meta_description?: string;
  created_at: string;
}

export interface ProductsResponse {
  success: boolean;
  data: Product[];
  message?: string;
}

export const productsApi = createApi({
  reducerPath: "productsApi",
  baseQuery: fetchBaseQuery({
    baseUrl: "http://localhost:8000/api",
    prepareHeaders: (headers) => {
      headers.set("Accept", "application/json");
      headers.set("Content-Type", "application/json");
      return headers;
    },
  }),
  tagTypes: ["Products"],
  endpoints: (builder) => ({
    // Get all products (public)
    getProducts: builder.query<ProductsResponse, { search?: string }>({
      query: ({ search }) => ({
        url: "/products",
        params: search ? { search } : {},
      }),
      providesTags: ["Products"],
    }),

    // Get single product (public)
    getProduct: builder.query<{ success: boolean; data: Product }, string>({
      query: (id) => `/products/${id}`,
      providesTags: (result, error, id) => [{ type: "Products", id }],
    }),
  }),
});

export const { useGetProductsQuery, useGetProductQuery } = productsApi;
