// filepath: c:\Users\CHAMA COMPUTERS\Downloads\Innovior IOT\esm\client\shop-sync-react\src\components\ProtectedRoute.tsx
import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { useAuth } from "@/auth/AuthProvider";
import { Loader2 } from "lucide-react";

interface Props {
  children: React.ReactNode;
}

const ProtectedRoute: React.FC<Props> = ({ children }) => {
  const { user, ready } = useAuth();
  const location = useLocation();

  if (!ready)
    return (
      <div className="min-h-[200px] flex items-center justify-center">
        <Loader2 className="animate-spin" />
      </div>
    );
  if (!user) return <Navigate to="/login" state={{ from: location }} replace />;

  return <>{children}</>;
};

export default ProtectedRoute;
