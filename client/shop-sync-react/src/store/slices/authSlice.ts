import { createSlice, PayloadAction } from "@reduxjs/toolkit";

// Define the User type for consistency
interface User {
  id: string;
  name: string;
  email: string;
  role: "admin" | "customer";
  status: string;
  avatar?: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
}

// Function to load the initial state from localStorage
const getInitialState = (): AuthState => {
  // ✅ Use consistent token key across your app
  const token =
    localStorage.getItem("auth_token") || localStorage.getItem("jwt_token");
  const userStr = localStorage.getItem("user");

  let user = null;
  try {
    user = userStr ? JSON.parse(userStr) : null;
  } catch (e) {
    localStorage.removeItem("user");
  }

  return {
    user,
    token,
    isAuthenticated: !!(token && user),
  };
};

const authSlice = createSlice({
  name: "auth",
  initialState: getInitialState(),
  reducers: {
    // This action is called on successful login or token refresh
    setCredentials: (
      state,
      action: PayloadAction<{ user: User; token: string }>
    ) => {
      const { user, token } = action.payload;
      state.user = user;
      state.token = token;
      state.isAuthenticated = true;

      // ✅ Store in both keys for consistency
      localStorage.setItem("auth_token", token);
      localStorage.setItem("jwt_token", token);
      localStorage.setItem("user", JSON.stringify(user));
    },
    // This action is called on logout
    clearCredentials: (state) => {
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;

      // ✅ Clear all auth data
      localStorage.removeItem("auth_token");
      localStorage.removeItem("jwt_token");
      localStorage.removeItem("user");
      localStorage.removeItem("refresh_token");
      localStorage.removeItem("token_expiry");
    },
  },
});

export const { setCredentials, clearCredentials } = authSlice.actions;
export default authSlice.reducer;
