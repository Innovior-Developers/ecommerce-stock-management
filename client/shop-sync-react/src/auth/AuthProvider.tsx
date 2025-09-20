import React, { createContext, useContext, useEffect, useState } from "react";
import {
  api,
  setToken as setStoredToken,
  getToken as getStoredToken,
} from "@/api/axios";

type User = {
  id: string;
  email: string;
  role: string;
  name: string;
  status: string;
  avatar?: string;
};

type AuthContextType = {
  user: User | null;
  token: string | null;
  login: (email: string, password: string, isAdmin?: boolean) => Promise<void>;
  logout: () => Promise<void>;
  ready: boolean;
  isAuthenticated: boolean;
};

const AuthContext = createContext<AuthContextType | undefined>(undefined);

// Helper function to decode JWT payload (without verification)
const decodeJwtPayload = (token: string) => {
  try {
    const base64Url = token.split(".")[1];
    const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split("")
        .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
        .join("")
    );
    return JSON.parse(jsonPayload);
  } catch (error) {
    console.error("Error decoding JWT:", error);
    return null;
  }
};

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({
  children,
}) => {
  const [token, setToken] = useState<string | null>(getStoredToken());
  const [user, setUser] = useState<User | null>(null);
  const [ready, setReady] = useState(false);

  const isAuthenticated = !!user && !!token;

  useEffect(() => {
    async function bootstrap() {
      if (token) {
        try {
          // Decode token to check expiration
          const payload = decodeJwtPayload(token);

          if (!payload) {
            throw new Error("Invalid token");
          }

          // Check if token is expired
          const currentTime = Math.floor(Date.now() / 1000);
          if (payload.exp && payload.exp < currentTime) {
            console.log("Token expired, clearing...");
            setToken(null);
            setStoredToken(null);
            setUser(null);
          } else {
            // Token is valid, fetch user data from API
            const response = await api.get("/auth/user");
            if (response.data.success) {
              setUser(response.data.user);
            } else {
              throw new Error("Failed to fetch user");
            }
          }
        } catch (error) {
          console.error("Auth bootstrap error:", error);
          setUser(null);
          setToken(null);
          setStoredToken(null);
        }
      }
      setReady(true);
    }

    bootstrap();
  }, [token]);

  const login = async (email: string, password: string, isAdmin = false) => {
    try {
      const endpoint = isAdmin ? "/auth/admin/login" : "/auth/customer/login";
      const response = await api.post(endpoint, { email, password });

      if (response.data.success) {
        const newToken = response.data.token;
        const userData = response.data.user;

        setToken(newToken);
        setStoredToken(newToken);
        setUser(userData);

        return Promise.resolve();
      } else {
        throw new Error(response.data.message || "Login failed");
      }
    } catch (error) {
      console.error("Login error:", error);
      throw error;
    }
  };

  const logout = async () => {
    try {
      // Call logout endpoint to invalidate token on server
      await api.post("/auth/logout");
    } catch (error) {
      // Ignore network errors during logout, still clear client state
      console.warn("Logout API call failed:", error);
    } finally {
      setToken(null);
      setStoredToken(null);
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        login,
        logout,
        ready,
        isAuthenticated,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return context;
};
