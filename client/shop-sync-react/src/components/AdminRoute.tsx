import { Navigate, useLocation } from "react-router-dom";
import { useAuth } from "@/contexts/AuthContext";
import { Loader2 } from "lucide-react";

interface AdminRouteProps {
  children: React.ReactNode;
}

const AdminRoute: React.FC<AdminRouteProps> = ({ children }) => {
  const { user, isAuthenticated, isLoading } = useAuth();
  const location = useLocation();

  console.log("AdminRoute check:", {
    isLoading,
    isAuthenticated,
    user: user ? { id: user.id, role: user.role } : null,
    location: location.pathname,
  });

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="flex items-center space-x-2">
          <Loader2 className="h-6 w-6 animate-spin" />
          <span>Loading...</span>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    console.log("AdminRoute: Not authenticated, redirecting to login");
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (user?.role !== "admin") {
    console.log("AdminRoute: Not admin user, redirecting to home");
    return <Navigate to="/" replace />;
  }

  console.log("AdminRoute: Access granted");
  return <>{children}</>;
};

export default AdminRoute;
