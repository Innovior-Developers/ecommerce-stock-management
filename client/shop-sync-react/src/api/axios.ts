import axios from "axios";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost:8000/api";

const api = axios.create({
  baseURL: API_BASE,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  withCredentials: false,
});

// Token management functions
const getToken = () => localStorage.getItem("jwt_token");
const setToken = (token: string | null) => {
  if (token) {
    localStorage.setItem("jwt_token", token);
  } else {
    localStorage.removeItem("jwt_token");
  }
};

// Request interceptor to add token
api.interceptors.request.use(
  (config) => {
    const token = getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor for token refresh
let isRefreshing = false;
let failedQueue: Array<{
  resolve: (value?: unknown) => void;
  reject: (reason?: unknown) => void;
}> = [];

const processQueue = (error: unknown, token: string | null = null) => {
  failedQueue.forEach((prom) => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });

  failedQueue = [];
};

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      if (error.response.data?.error_code === "TOKEN_EXPIRED") {
        if (isRefreshing) {
          return new Promise((resolve, reject) => {
            failedQueue.push({ resolve, reject });
          })
            .then((token) => {
              originalRequest.headers.Authorization = `Bearer ${token}`;
              return api(originalRequest);
            })
            .catch((err) => {
              return Promise.reject(err);
            });
        }

        originalRequest._retry = true;
        isRefreshing = true;

        try {
          const refreshResponse = await axios.post(
            `${API_BASE}/auth/refresh`,
            {},
            {
              headers: { Authorization: `Bearer ${getToken()}` },
            }
          );

          const newToken = refreshResponse.data.token;
          setToken(newToken);
          api.defaults.headers.common.Authorization = `Bearer ${newToken}`;
          processQueue(null, newToken);

          return api(originalRequest);
        } catch (refreshError) {
          processQueue(refreshError, null);
          setToken(null);
          window.location.href = "/login";
          return Promise.reject(refreshError);
        } finally {
          isRefreshing = false;
        }
      } else {
        // Invalid token or other auth error
        setToken(null);
        window.location.href = "/login";
      }
    }

    return Promise.reject(error);
  }
);

export { api, getToken, setToken };
