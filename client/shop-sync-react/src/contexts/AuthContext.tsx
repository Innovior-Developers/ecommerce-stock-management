import React, {
  createContext,
  useContext,
  useEffect,
  useState,
  ReactNode,
} from "react";
import { authService, User, LoginCredentials, RegisterData } from "@/api/Auth";
import { toast } from "sonner";

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (credentials: LoginCredentials) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  adminLogin: (credentials: LoginCredentials) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    initializeAuth();
  }, []);

  const initializeAuth = async () => {
    try {
      const { token, user: storedUser } = authService.getAuthData();
      console.log("Auth initialization:", { token: !!token, user: storedUser });

      if (token && storedUser) {
        // Set user immediately from localStorage
        setUser(storedUser);

        // Verify token in background (don't clear user if this fails)
        try {
          const response = await authService.getCurrentUser();
          console.log("Token verification response:", response);

          if (response.success && response.user) {
            // Update user with fresh data
            setUser(response.user);
          }
          // If token verification fails, we still keep the stored user
          // The backend will handle invalid tokens on subsequent requests
        } catch (error) {
          console.warn(
            "Token verification failed, keeping stored user:",
            error
          );
          // Don't clear auth data here - let the user stay logged in
          // Invalid tokens will be handled by API interceptors
        }
      }
    } catch (error) {
      console.error("Auth initialization error:", error);
      // Only clear auth data if there's a parsing error with stored data
      if (error instanceof SyntaxError) {
        authService.clearAuthData();
      }
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (credentials: LoginCredentials) => {
    try {
      setIsLoading(true);
      const response = await authService.customerLogin(credentials);
      setUser(response.user);
      toast.success("Login successful!");
    } catch (error: unknown) {
      console.error("Login error:", error);
      toast.error(error.message || "Login failed");
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const register = async (data: RegisterData) => {
    try {
      setIsLoading(true);
      const response = await authService.customerRegister(data);
      setUser(response.user);
      toast.success("Registration successful!");
    } catch (error: unknown) {
      console.error("Registration error:", error);
      toast.error(error.message || "Registration failed");
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const adminLogin = async (credentials: LoginCredentials) => {
    try {
      setIsLoading(true);
      const response = await authService.adminLogin(credentials);
      setUser(response.user);
      toast.success("Admin login successful!");
    } catch (error: unknown) {
      console.error("Admin login error:", error);
      const errorMessage = error.message || "Admin login failed";
      toast.error(errorMessage);
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  // Update the logout function
  const logout = async () => {
    try {
      const result = await authService.logout();
      setUser(null);
      toast.success(result.message || "Logged out successfully");
    } catch (error: unknown) {
      console.error("Logout error:", error);
      // Still clear local state even if API call fails
      setUser(null);
      authService.clearAuthData();
      toast.success("Logged out");
    }
  };

  const refreshUser = async () => {
    try {
      const response = await authService.getCurrentUser();
      if (response.success && response.user) {
        setUser(response.user);
      }
    } catch (error) {
      console.error("Failed to refresh user:", error);
      // Don't clear user data on refresh failure
    }
  };

  const value: AuthContextType = {
    user,
    isAuthenticated: !!user,
    isLoading,
    login,
    register,
    adminLogin,
    logout,
    refreshUser,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
};
