// filepath: c:\Users\CHAMA COMPUTERS\Downloads\Innovior IOT\esm\client\shop-sync-react\src\components\ProtectedRoute.tsx
import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { useAppSelector } from "@/store/hooks";
import { Loader2 } from "lucide-react";

interface Props {
  children: React.ReactNode;
}

const ProtectedRoute: React.FC<Props> = ({ children }) => {
  const { isAuthenticated } = useAppSelector((state) => state.auth);
  const location = useLocation();

  // ✅ Check if Redux state is ready (after rehydration)
  const isRehydrated = useAppSelector((state) => state._persist?.rehydrated);

  if (!isRehydrated)
    return (
      <div className="min-h-[200px] flex items-center justify-center">
        <Loader2 className="animate-spin" />
      </div>
    );
  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  return <>{children}</>;
};

export default ProtectedRoute;
