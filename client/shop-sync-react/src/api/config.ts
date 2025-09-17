export const API_CONFIG = {
  BASE_URL: "http://localhost:8000/api",
  TIMEOUT: 10000,
  HEADERS: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
} as const;

export const API_ENDPOINTS = {
  // Auth endpoints
  AUTH: {
    ADMIN_LOGIN: "/auth/admin/login",
    CUSTOMER_LOGIN: "/auth/customer/login",
    CUSTOMER_REGISTER: "/auth/customer/register",
    LOGOUT: "/auth/logout",
    USER: "/auth/user",
    SOCIAL_GOOGLE: "/auth/social/google",
    SOCIAL_GITHUB: "/auth/social/github",
  },
  // Resource endpoints
  PRODUCTS: "/products",
  CATEGORIES: "/categories",
  ORDERS: "/orders",
  ADMIN: {
    PRODUCTS: "/admin/products",
    CATEGORIES: "/admin/categories",
    CUSTOMERS: "/admin/customers",
    ORDERS: "/admin/orders",
    INVENTORY: "/admin/inventory",
  },
  HEALTH: "/health",
} as const;
