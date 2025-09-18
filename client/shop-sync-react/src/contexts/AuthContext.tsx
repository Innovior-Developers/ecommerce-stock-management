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

      if (token && storedUser) {
        // Verify token is still valid by fetching current user
        try {
          const response = await authService.getCurrentUser();
          if (response.success && response.user) {
            setUser(response.user);
          } else {
            // Token is invalid, clear it
            authService.clearAuthData();
          }
        } catch (error) {
          // API call failed, might be network issue, keep stored user for now
          console.warn("Could not verify token, keeping stored user:", error);
          setUser(storedUser);
        }
      }
    } catch (error) {
      console.error("Auth initialization error:", error);
      authService.clearAuthData();
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
      const errorMessage =
        error instanceof Error ? error.message : "Admin login failed";
      toast.error(errorMessage);
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async () => {
    try {
      await authService.logout();
      setUser(null);
      toast.success("Logged out successfully");
    } catch (error: unknown) {
      console.error("Logout error:", error);
      // Still clear local state even if API call fails
      setUser(null);
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
