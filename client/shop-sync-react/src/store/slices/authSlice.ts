import { createSlice, PayloadAction } from "@reduxjs/toolkit";

interface User {
  id: string;
  email: string;
  name: string;
  role: string;
  avatar?: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
}

const initialState: AuthState = {
  user: null,
  token: null,
  isAuthenticated: false,
};

const authSlice = createSlice({
  name: "auth",
  initialState,
  reducers: {
    setCredentials: (
      state,
      action: PayloadAction<{ user: User; token: string }>
    ) => {
      state.user = action.payload.user;
      state.token = action.payload.token;
      state.isAuthenticated = true;

      // ✅ ONLY store token, NOT user data
      localStorage.setItem("auth_token", action.payload.token);

      // ❌ REMOVE these lines:
      // localStorage.setItem("user", JSON.stringify(action.payload.user));
    },
    clearCredentials: (state) => {
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;

      // Clear all auth data
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user"); // Clean up old data
      localStorage.removeItem("refresh_token");
      localStorage.removeItem("token_expiry");
    },
    logout: (state) => {
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;

      localStorage.removeItem("auth_token");
      localStorage.removeItem("user");
      localStorage.removeItem("refresh_token");
      localStorage.removeItem("token_expiry");
    },
  },
});

export const { setCredentials, clearCredentials, logout } = authSlice.actions;
export default authSlice.reducer;
