import { createSlice, PayloadAction } from "@reduxjs/toolkit";

interface User {
  id: string;
  name: string;
  email: string;
  role: "admin" | "customer";
  avatar?: string;
}

interface AuthState {
  user: {
    id: string;
    name: string;
    email: string;
    role: "admin" | "customer";
    avatar?: string;
  } | null;
  token: string | null;
  refreshToken: string | null;
  isAuthenticated: boolean;
}

// Initialize state from localStorage
const getInitialState = (): AuthState => {
  const token = localStorage.getItem("auth_token");
  const userStr = localStorage.getItem("user");
  const user = userStr ? JSON.parse(userStr) : null;

  return {
    user,
    token,
    refreshToken: localStorage.getItem("refresh_token"),
    isAuthenticated: !!token && !!user,
  };
};

const authSlice = createSlice({
  name: "auth",
  initialState: getInitialState(),
  reducers: {
    setCredentials: (
      state,
      action: PayloadAction<{
        user: unknown;
        token: string;
        refreshToken?: string;
      }>
    ) => {
      const { user, token, refreshToken } = action.payload;
      state.user = user;
      state.token = token;
      state.isAuthenticated = true;
      if (refreshToken) {
        state.refreshToken = refreshToken;
      }

      // Persist to localStorage
      localStorage.setItem("auth_token", token);
      localStorage.setItem("user", JSON.stringify(user));
      if (refreshToken) {
        localStorage.setItem("refresh_token", refreshToken);
      }
    },
    clearCredentials: (state) => {
      state.user = null;
      state.token = null;
      state.refreshToken = null;
      state.isAuthenticated = false;

      // Clear from localStorage
      localStorage.removeItem("auth_token");
      localStorage.removeItem("refresh_token");
      localStorage.removeItem("user");
    },
  },
});

export const { setCredentials, clearCredentials } = authSlice.actions;
export default authSlice.reducer;
