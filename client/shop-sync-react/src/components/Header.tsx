import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
  Search,
  ShoppingCart,
  User,
  Menu,
  Heart,
  Settings,
  LogOut,
  Shield,
} from "lucide-react";
import { useAppSelector } from "@/store/hooks";
import { useLogoutMutation } from "@/store/api/authApi";
import { toast } from "sonner";

interface HeaderProps {
  isAdmin?: boolean;
}

const Header = ({ isAdmin = false }: HeaderProps) => {
  const [searchQuery, setSearchQuery] = useState("");
  const navigate = useNavigate();

  const { isAuthenticated, user } = useAppSelector((state) => state.auth);
  const cartItemCount = useAppSelector((state) => state.cart.itemCount);
  const [logout, { isLoading: isLoggingOut }] = useLogoutMutation();

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      navigate(`/search?q=${encodeURIComponent(searchQuery)}`);
    }
  };

  const handleLogout = async () => {
    try {
      await logout().unwrap();
      toast.success("Logged out successfully");
      navigate("/");
    } catch (error: unknown) {
      if (error?.status === 401) {
        toast.error(
          error?.data?.message || "Session expired. You have been logged out."
        );
        navigate("/login");
      } else {
        toast.error("Logout failed");
      }
    }
  };

  // ✅ OPTION 1: Hide cart only on admin dashboard page (recommended)
  const shouldShowCart = !isAdmin;

  // ✅ OPTION 2: Show cart for everyone (if you want admins to shop too)
  // const shouldShowCart = true;

  // ✅ OPTION 3: Hide cart only for admin users everywhere
  // const shouldShowCart = user?.role !== "admin";

  return (
    <header className="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 sticky top-0 z-50">
      <div className="container mx-auto px-4">
        <div className="flex h-16 items-center justify-between">
          {/* Logo */}
          <Link
            to="/"
            className="flex items-center space-x-2 hover:opacity-80 transition-opacity"
          >
            <div className="h-8 w-8 bg-primary rounded-md flex items-center justify-center">
              <span className="text-primary-foreground font-bold text-sm">
                S
              </span>
            </div>
            <span className="font-bold text-xl">ShopSync</span>
          </Link>

          {/* Search Bar - Hidden on mobile */}
          {!isAdmin && (
            <form
              onSubmit={handleSearch}
              className="hidden md:flex flex-1 max-w-md mx-8"
            >
              <div className="relative w-full">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                <Input
                  type="search"
                  placeholder="Search products..."
                  className="pl-10 pr-4"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
              </div>
            </form>
          )}

          {/* Navigation Links - Hidden on mobile */}
          <nav className="hidden lg:flex items-center space-x-6">
            <Link
              to="/"
              className="text-sm font-medium hover:text-primary transition-colors duration-200"
            >
              Home
            </Link>
            <Link
              to="/shop"
              className="text-sm font-medium hover:text-primary transition-colors duration-200"
            >
              Shop
            </Link>
            <Link
              to="/deals"
              className="text-sm font-medium hover:text-primary transition-colors duration-200"
            >
              Deals
            </Link>
            <Link
              to="/about"
              className="text-sm font-medium hover:text-primary transition-colors duration-200"
            >
              About
            </Link>
            <Link
              to="/contact"
              className="text-sm font-medium hover:text-primary transition-colors duration-200"
            >
              Contact
            </Link>
            {isAuthenticated && user?.role === "admin" && (
              <Link
                to="/admin"
                className="text-sm font-medium hover:text-primary transition-colors duration-200 text-primary font-semibold"
              >
                Admin
              </Link>
            )}
          </nav>

          {/* Navigation Icons */}
          <div className="flex items-center space-x-2">
            {/* ✅ Cart Icon - Show based on shouldShowCart logic */}
            {shouldShowCart && (
              <Link to="/cart">
                <Button
                  variant="ghost"
                  size="icon"
                  className="relative hover:bg-primary/10 transition-colors"
                  aria-label="Shopping cart"
                >
                  <ShoppingCart className="h-5 w-5" />
                  {cartItemCount > 0 && (
                    <Badge className="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs animate-in zoom-in-50">
                      {cartItemCount > 99 ? "99+" : cartItemCount}
                    </Badge>
                  )}
                </Button>
              </Link>
            )}

            {/* Wishlist Icon - Optional */}
            {shouldShowCart && isAuthenticated && (
              <Link to="/wishlist">
                <Button
                  variant="ghost"
                  size="icon"
                  className="hover:bg-primary/10 transition-colors"
                  aria-label="Wishlist"
                >
                  <Heart className="h-5 w-5" />
                </Button>
              </Link>
            )}

            {/* User Menu */}
            {isAuthenticated ? (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button
                    variant="ghost"
                    className="relative h-8 w-8 rounded-full hover:bg-primary/10"
                  >
                    <Avatar className="h-8 w-8">
                      <AvatarImage src={user?.avatar} alt={user?.name} />
                      <AvatarFallback className="bg-primary text-primary-foreground">
                        {user?.name?.charAt(0)?.toUpperCase() || "U"}
                      </AvatarFallback>
                    </Avatar>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-56" align="end" forceMount>
                  <div className="flex items-center justify-start gap-2 p-2">
                    <div className="flex flex-col space-y-1 leading-none">
                      <p className="font-medium">{user?.name}</p>
                      <p className="w-48 truncate text-sm text-muted-foreground">
                        {user?.email}
                      </p>
                    </div>
                  </div>
                  <DropdownMenuSeparator />

                  {user?.role === "admin" && (
                    <>
                      <DropdownMenuItem asChild>
                        <Link
                          to="/admin"
                          className="flex items-center cursor-pointer"
                        >
                          <Shield className="mr-2 h-4 w-4" />
                          Admin Dashboard
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                    </>
                  )}

                  <DropdownMenuItem asChild>
                    <Link
                      to="/profile"
                      className="flex items-center cursor-pointer"
                    >
                      <User className="mr-2 h-4 w-4" />
                      Profile
                    </Link>
                  </DropdownMenuItem>

                  {user?.role !== "admin" && (
                    <DropdownMenuItem asChild>
                      <Link
                        to="/orders"
                        className="flex items-center cursor-pointer"
                      >
                        <Settings className="mr-2 h-4 w-4" />
                        Orders
                      </Link>
                    </DropdownMenuItem>
                  )}

                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    onClick={handleLogout}
                    disabled={isLoggingOut}
                    className="flex items-center text-red-600 cursor-pointer focus:text-red-600"
                  >
                    <LogOut className="mr-2 h-4 w-4" />
                    {isLoggingOut ? "Logging out..." : "Log out"}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            ) : (
              <div className="flex items-center space-x-2">
                <Button variant="ghost" size="sm" asChild>
                  <Link to="/login">Login</Link>
                </Button>
                <Button size="sm" asChild>
                  <Link to="/register">Sign Up</Link>
                </Button>
              </div>
            )}

            {/* Mobile menu */}
            <Button variant="ghost" size="icon" className="lg:hidden">
              <Menu className="h-5 w-5" />
              <span className="sr-only">Menu</span>
            </Button>
          </div>
        </div>
      </div>
    </header>
  );
};

export default Header;
