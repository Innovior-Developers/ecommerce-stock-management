import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { normalizeMongoArray } from "@/utils/normalizeMongoId";

export interface Product {
  _id: string;
  id?: string;
  name: string;
  description: string;
  price: number;
  category: string;
  stock_quantity: number;
  status: string;
  image_url?: string;
  images?: Array<{
    url: string;
    is_primary?: boolean;
    filename?: string;
  }>;
  rating?: number;
  isNew?: boolean;
  weight?: number;
  meta_title?: string;
  meta_description?: string;
  created_at: string;
  sku?: string;
}

export const productsApi = createApi({
  reducerPath: "productsApi",
  baseQuery: fetchBaseQuery({
    baseUrl: "http://localhost:8000/api",
  }),
  tagTypes: ["Products"],
  endpoints: (builder) => ({
    // Get all products (public)
    getProducts: builder.query<
      Product[],
      { search?: string; category?: string }
    >({
      query: ({ search = "", category = "" }) => {
        const params = new URLSearchParams();
        if (search) params.append("search", search);
        if (category && category !== "all") params.append("category", category);
        return `/products?${params.toString()}`;
      },
      transformResponse: (response: unknown) => {
        // ✅ Log the raw response to understand structure
        console.log("📦 Products API Raw Response:", response);

        // ✅ Handle multiple possible response structures
        let data;

        // Structure 1: { success: true, data: { data: [...] } }
        if (response?.data?.data) {
          data = response.data.data;
        }
        // Structure 2: { success: true, data: [...] }
        else if (response?.data && Array.isArray(response.data)) {
          data = response.data;
        }
        // Structure 3: { data: [...] }
        else if (Array.isArray(response?.data)) {
          data = response.data;
        }
        // Structure 4: Direct array [...]
        else if (Array.isArray(response)) {
          data = response;
        }
        // Fallback
        else {
          console.warn("⚠️ Unexpected response structure:", response);
          data = [];
        }

        console.log("📦 Products API Extracted Data:", data);
        console.log("📦 Products API Count:", data.length);

        // ✅ Normalize MongoDB IDs
        return normalizeMongoArray(data);
      },
      providesTags: ["Products"],
    }),

    // Get single product (public)
    getProductById: builder.query<Product, string>({
      query: (id) => `/products/${id}`,
      transformResponse: (response: unknown) => {
        console.log("📦 Single Product Raw Response:", response);

        const product = response?.data || response;

        const normalized = {
          ...product,
          _id: product._id?.$oid || product._id || product.id,
          id: product._id?.$oid || product._id || product.id,
        };

        console.log("📦 Single Product Normalized:", normalized);

        return normalized;
      },
    }),
  }),
});

export const { useGetProductsQuery, useGetProductByIdQuery } = productsApi;
