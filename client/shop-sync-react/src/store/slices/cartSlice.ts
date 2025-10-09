import { createSlice, PayloadAction } from "@reduxjs/toolkit";

interface CartItem {
  id: string;
  name: string;
  price: number;
  quantity: number;
  image: string;
  stock_quantity: number;
}

interface CartState {
  items: CartItem[];
  total: number;
  itemCount: number;
}

const initialState: CartState = {
  items: [],
  total: 0,
  itemCount: 0,
};

const calculateTotals = (items: CartItem[]) => {
  const total = items.reduce(
    (sum, item) => sum + item.price * item.quantity,
    0
  );
  const itemCount = items.reduce((sum, item) => sum + item.quantity, 0);
  return { total, itemCount };
};

const cartSlice = createSlice({
  name: "cart",
  initialState,
  reducers: {
    addToCart: (state, action: PayloadAction<CartItem>) => {
      const existingItem = state.items.find(
        (item) => item.id === action.payload.id
      );

      if (existingItem) {
        // Check stock before increasing quantity
        if (existingItem.quantity < action.payload.stock_quantity) {
          existingItem.quantity += 1;
        }
      } else {
        state.items.push({ ...action.payload, quantity: 1 });
      }

      const { total, itemCount } = calculateTotals(state.items);
      state.total = total;
      state.itemCount = itemCount;

      // Save to localStorage
      localStorage.setItem("cart", JSON.stringify(state.items));
    },

    removeFromCart: (state, action: PayloadAction<string>) => {
      state.items = state.items.filter((item) => item.id !== action.payload);

      const { total, itemCount } = calculateTotals(state.items);
      state.total = total;
      state.itemCount = itemCount;

      localStorage.setItem("cart", JSON.stringify(state.items));
    },

    updateQuantity: (
      state,
      action: PayloadAction<{ id: string; quantity: number }>
    ) => {
      const item = state.items.find((item) => item.id === action.payload.id);
      if (item) {
        // Ensure quantity doesn't exceed stock
        item.quantity = Math.min(action.payload.quantity, item.stock_quantity);
      }

      const { total, itemCount } = calculateTotals(state.items);
      state.total = total;
      state.itemCount = itemCount;

      localStorage.setItem("cart", JSON.stringify(state.items));
    },

    clearCart: (state) => {
      state.items = [];
      state.total = 0;
      state.itemCount = 0;
      localStorage.removeItem("cart");
    },

    loadCartFromStorage: (state) => {
      const savedCart = localStorage.getItem("cart");
      if (savedCart) {
        try {
          state.items = JSON.parse(savedCart);
          const { total, itemCount } = calculateTotals(state.items);
          state.total = total;
          state.itemCount = itemCount;
        } catch (error) {
          console.error("Failed to load cart from storage:", error);
        }
      }
    },
  },
});

export const {
  addToCart,
  removeFromCart,
  updateQuantity,
  clearCart,
  loadCartFromStorage,
} = cartSlice.actions;

export default cartSlice.reducer;
