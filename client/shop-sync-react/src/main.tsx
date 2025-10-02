import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { Provider } from "react-redux";
import { PersistGate } from "redux-persist/integration/react";
import { RouterProvider } from "react-router-dom";

import { store, persistor } from "@/store";
import { AuthProvider } from "@/auth/AuthProvider";
import App, { router } from "./App.tsx"; // ✅ Import router here
import "./index.css";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <Provider store={store}>
      <PersistGate loading={null} persistor={persistor}>
        <AuthProvider>
          <RouterProvider
            router={router}
            future={{ v7_startTransition: true }}
          />
        </AuthProvider>
      </PersistGate>
    </Provider>
  </StrictMode>
);
