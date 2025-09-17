import { apiService, ApiResponse } from "./Api";
import { API_ENDPOINTS } from "./config";

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  first_name?: string;
  last_name?: string;
  phone?: string;
}

export interface User {
  id: string;
  name: string;
  email: string;
  role: "admin" | "customer";
  avatar?: string;
  provider?: string;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  user: User;
  token: string;
  token_type: string;
}

class AuthService {
  // Store auth data in localStorage
  setAuthData(data: AuthResponse) {
    localStorage.setItem("auth_token", data.token);
    localStorage.setItem("user", JSON.stringify(data.user));
  }

  // Get stored auth data
  getAuthData() {
    const token = localStorage.getItem("auth_token");
    const userStr = localStorage.getItem("user");
    const user = userStr ? JSON.parse(userStr) : null;

    return { token, user };
  }

  // Clear auth data
  clearAuthData() {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");
  }

  // Check if user is authenticated
  isAuthenticated(): boolean {
    const { token } = this.getAuthData();
    return !!token;
  }

  // Check if user is admin
  isAdmin(): boolean {
    const { user } = this.getAuthData();
    return user?.role === "admin";
  }

  // Customer login
  async customerLogin(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await apiService.post<AuthResponse>(
      API_ENDPOINTS.AUTH.CUSTOMER_LOGIN,
      credentials
    );

    if (response.success && response.token) {
      this.setAuthData(response as AuthResponse);
    }

    return response as AuthResponse;
  }

  // Customer register
  async customerRegister(data: RegisterData): Promise<AuthResponse> {
    const response = await apiService.post<AuthResponse>(
      API_ENDPOINTS.AUTH.CUSTOMER_REGISTER,
      data
    );

    if (response.success && response.token) {
      this.setAuthData(response as AuthResponse);
    }

    return response as AuthResponse;
  }

  // Admin login
  async adminLogin(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await apiService.post<AuthResponse>(
      API_ENDPOINTS.AUTH.ADMIN_LOGIN,
      credentials
    );

    if (response.success && response.token) {
      this.setAuthData(response as AuthResponse);
    }

    return response as AuthResponse;
  }

  // Logout
  async logout(): Promise<ApiResponse> {
    try {
      const response = await apiService.post(API_ENDPOINTS.AUTH.LOGOUT);
      this.clearAuthData();
      return response;
    } catch (error) {
      // Clear auth data even if logout request fails
      this.clearAuthData();
      throw error;
    }
  }

  // Get current user
  async getCurrentUser(): Promise<ApiResponse<User>> {
    return apiService.get(API_ENDPOINTS.AUTH.USER);
  }

  // Social login redirect
  getSocialLoginUrl(provider: "google" | "github"): string {
    const baseUrl = "http://localhost:8000/api";
    return `${baseUrl}/auth/social/${provider}`;
  }
}

export const authService = new AuthService();
export default AuthService;
