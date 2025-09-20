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
  const token = localStorage.getItem("jwt_token");
  return {
    user: null,
    token: token,
    isAuthenticated: false,
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
      localStorage.setItem("jwt_token", token);
    },
    // This action is called on logout
    clearCredentials: (state) => {
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;
      localStorage.removeItem("jwt_token");
    },
  },
});

export const { setCredentials, clearCredentials } = authSlice.actions;
export default authSlice.reducer;
