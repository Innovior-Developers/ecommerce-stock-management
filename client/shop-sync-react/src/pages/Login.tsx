import { useState, useEffect } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  useAdminLoginMutation,
  useCustomerLoginMutation,
} from "@/store/api/authApi";
import { useAppSelector } from "@/store/hooks";
import { toast } from "sonner";

const Login = () => {
  const [customerForm, setCustomerForm] = useState({ email: "", password: "" });
  const [adminForm, setAdminForm] = useState({ email: "", password: "" });

  const [adminLogin, { isLoading: adminLoading }] = useAdminLoginMutation();
  const [customerLogin, { isLoading: customerLoading }] =
    useCustomerLoginMutation();

  const { isAuthenticated, user } = useAppSelector((state) => state.auth);
  const navigate = useNavigate();
  const location = useLocation();

  const from = location.state?.from?.pathname || "/";

  // Redirect if already authenticated
  useEffect(() => {
    if (isAuthenticated && user) {
      if (user.role === "admin") {
        navigate("/admin", { replace: true });
      } else {
        navigate(from, { replace: true });
      }
    }
  }, [isAuthenticated, user, navigate, from]);

  const handleCustomerSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const response = await customerLogin(customerForm).unwrap();
      if (response.success) {
        toast.success("Login successful!");
        navigate(from, { replace: true });
      }
    } catch (error: unknown) {
      console.error("Customer login error:", error);
      toast.error(error?.data?.message || "Login failed");
    }
  };

  const handleAdminSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const response = await adminLogin(adminForm).unwrap();
      if (response.success) {
        toast.success("Admin login successful!");
        navigate("/admin", { replace: true });
      }
    } catch (error: unknown) {
      console.error("Admin login error:", error);
      toast.error(error?.data?.message || "Admin login failed");
    }
  };

  const isLoading = adminLoading || customerLoading;

  return (
    <div className="min-h-screen flex items-center justify-center bg-background px-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <CardTitle className="text-2xl font-bold">Welcome Back</CardTitle>
          <CardDescription>Sign in to your account</CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs defaultValue="customer" className="w-full">
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="customer">Customer</TabsTrigger>
              <TabsTrigger value="admin">Admin</TabsTrigger>
            </TabsList>

            <TabsContent value="customer">
              <form onSubmit={handleCustomerSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="customer-email">Email</Label>
                  <Input
                    id="customer-email"
                    type="email"
                    placeholder="Enter your email"
                    value={customerForm.email}
                    onChange={(e) =>
                      setCustomerForm((prev) => ({
                        ...prev,
                        email: e.target.value,
                      }))
                    }
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="customer-password">Password</Label>
                  <Input
                    id="customer-password"
                    type="password"
                    placeholder="Enter your password"
                    value={customerForm.password}
                    onChange={(e) =>
                      setCustomerForm((prev) => ({
                        ...prev,
                        password: e.target.value,
                      }))
                    }
                    required
                  />
                </div>
                <Button type="submit" className="w-full" disabled={isLoading}>
                  {isLoading ? "Signing in..." : "Sign In"}
                </Button>
              </form>
            </TabsContent>

            <TabsContent value="admin">
              <form onSubmit={handleAdminSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="admin-email">Admin Email</Label>
                  <Input
                    id="admin-email"
                    type="email"
                    placeholder="Enter admin email"
                    value={adminForm.email}
                    onChange={(e) =>
                      setAdminForm((prev) => ({
                        ...prev,
                        email: e.target.value,
                      }))
                    }
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="admin-password">Password</Label>
                  <Input
                    id="admin-password"
                    type="password"
                    placeholder="Enter admin password"
                    value={adminForm.password}
                    onChange={(e) =>
                      setAdminForm((prev) => ({
                        ...prev,
                        password: e.target.value,
                      }))
                    }
                    required
                  />
                </div>
                <Button type="submit" className="w-full" disabled={isLoading}>
                  {isLoading ? "Signing in..." : "Admin Sign In"}
                </Button>
              </form>
            </TabsContent>
          </Tabs>

          <div className="mt-4 text-center text-sm">
            <span className="text-muted-foreground">
              Don't have an account?{" "}
            </span>
            <Link to="/register" className="text-primary hover:underline">
              Register here
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default Login;
