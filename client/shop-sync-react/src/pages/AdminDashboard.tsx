import { useState, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  getAdminProducts,
  createAdminProduct,
  updateAdminProduct,
  deleteAdminProduct,
  getAdminCategories,
  createAdminCategory,
  updateAdminCategory,
  deleteAdminCategory,
  getAdminCustomers,
  getAdminOrders,
  getLowStock,
  getStockLevels,
  updateStock,
} from "@/api/Api";
import {
  Package,
  ShoppingCart,
  Users,
  TrendingUp,
  AlertTriangle,
  Plus,
  Search,
  Filter,
  Tag,
  BarChart3,
  Loader2,
  Eye,
  Edit,
  Mail,
  Phone,
  Calendar,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import Header from "@/components/Header";
import { toast } from "sonner";

// Import the form components
import {
  ProductForm,
  ProductCard,
} from "@/components/admin forms/Product.form";
import {
  CategoryForm,
  CategoryCard,
} from "@/components/admin forms/Category.form";
import { InventoryUpdateForm } from "@/components/admin forms/Inventory.form";
import { DebugPanel } from "@/components/DebugPanel";
import { TokenDebug } from "@/components/TokenDebug";

const AdminDashboard = () => {
  const [activeTab, setActiveTab] = useState("overview");

  // Form states
  const [isAddProductOpen, setIsAddProductOpen] = useState(false);
  const [isEditProductOpen, setIsEditProductOpen] = useState(false);
  const [isAddCategoryOpen, setIsAddCategoryOpen] = useState(false);
  const [isEditCategoryOpen, setIsEditCategoryOpen] = useState(false);

  // Selected items for editing
  const [selectedProduct, setSelectedProduct] = useState<unknown>(null);
  const [selectedCategory, setSelectedCategory] = useState<unknown>(null);

  // Search states
  const [productSearch, setProductSearch] = useState("");
  const [customerSearch, setCustomerSearch] = useState("");
  const [categorySearch, setCategorySearch] = useState("");

  const queryClient = useQueryClient();

  // Queries with better error handling
  const {
    data: productsResponse,
    isLoading: productsLoading,
    error: productsError,
  } = useQuery({
    queryKey: ["admin-products", productSearch],
    queryFn: () => getAdminProducts({ search: productSearch }),
    retry: 2,
    retryDelay: 1000,
  });

  const {
    data: categoriesResponse,
    isLoading: categoriesLoading,
    error: categoriesError,
  } = useQuery({
    queryKey: ["admin-categories", categorySearch],
    queryFn: () => getAdminCategories({ search: categorySearch }),
    retry: 2,
    retryDelay: 1000,
  });

  const {
    data: customersResponse,
    isLoading: customersLoading,
    error: customersError,
  } = useQuery({
    queryKey: ["admin-customers", customerSearch],
    queryFn: () => getAdminCustomers({ search: customerSearch }),
    retry: 2,
    retryDelay: 1000,
  });

  const {
    data: ordersResponse,
    isLoading: ordersLoading,
    error: ordersError,
  } = useQuery({
    queryKey: ["admin-orders"],
    queryFn: () => getAdminOrders(),
    retry: 2,
    retryDelay: 1000,
  });

  const { data: lowStockResponse, error: lowStockError } = useQuery({
    queryKey: ["low-stock"],
    queryFn: () => getLowStock(),
    retry: 2,
    retryDelay: 1000,
  });

  const {
    data: stockLevelsResponse,
    isLoading: stockLevelsLoading,
    error: stockLevelsError,
  } = useQuery({
    queryKey: ["stock-levels"],
    queryFn: () => getStockLevels(),
    retry: 2,
    retryDelay: 1000,
  });

  // Show errors to user
  if (productsError) {
    console.error("Products Error:", productsError);
    toast.error(`Failed to load products: ${productsError.message}`);
  }
  if (categoriesError) {
    console.error("Categories Error:", categoriesError);
    toast.error(`Failed to load categories: ${categoriesError.message}`);
  }
  if (customersError) {
    console.error("Customers Error:", customersError);
    toast.error(`Failed to load customers: ${customersError.message}`);
  }
  if (ordersError) {
    console.error("Orders Error:", ordersError);
    toast.error(`Failed to load orders: ${ordersError.message}`);
  }

  // Product mutations
  const createProductMutation = useMutation({
    mutationFn: createAdminProduct,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-products"] });
      queryClient.invalidateQueries({ queryKey: ["stock-levels"] });
      setIsAddProductOpen(false);
      toast.success("Product created successfully!");
    },
    onError: (error: unknown) => {
      console.error("Create product error:", error);
      toast.error(error.message || "Failed to create product");
    },
  });

  const updateProductMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: unknown }) =>
      updateAdminProduct(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-products"] });
      queryClient.invalidateQueries({ queryKey: ["stock-levels"] });
      setIsEditProductOpen(false);
      setSelectedProduct(null);
      toast.success("Product updated successfully!");
    },
    onError: (error: unknown) => {
      console.error("Update product error:", error);
      toast.error(error.message || "Failed to update product");
    },
  });

  const deleteProductMutation = useMutation({
    mutationFn: deleteAdminProduct,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-products"] });
      queryClient.invalidateQueries({ queryKey: ["stock-levels"] });
      toast.success("Product deleted successfully!");
    },
    onError: (error: unknown) => {
      console.error("Delete product error:", error);
      toast.error(error.message || "Failed to delete product");
    },
  });

  // Category mutations
  const createCategoryMutation = useMutation({
    mutationFn: createAdminCategory,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-categories"] });
      setIsAddCategoryOpen(false);
      toast.success("Category created successfully!");
    },
    onError: (error: unknown) => {
      console.error("Create category error:", error);
      toast.error(error.message || "Failed to create category");
    },
  });

  const updateCategoryMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: unknown }) =>
      updateAdminCategory(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-categories"] });
      setIsEditCategoryOpen(false);
      setSelectedCategory(null);
      toast.success("Category updated successfully!");
    },
    onError: (error: unknown) => {
      console.error("Update category error:", error);
      toast.error(error.message || "Failed to update category");
    },
  });

  const deleteCategoryMutation = useMutation({
    mutationFn: deleteAdminCategory,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-categories"] });
      toast.success("Category deleted successfully!");
    },
    onError: (error: unknown) => {
      console.error("Delete category error:", error);
      toast.error(error.message || "Failed to delete category");
    },
  });

  // Stock update mutation
  const updateStockMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: unknown }) =>
      updateStock(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-products"] });
      queryClient.invalidateQueries({ queryKey: ["stock-levels"] });
      queryClient.invalidateQueries({ queryKey: ["low-stock"] });
      toast.success("Stock updated successfully!");
    },
    onError: (error: unknown) => {
      console.error("Update stock error:", error);
      toast.error(error.message || "Failed to update stock");
    },
  });

  // Extract data with proper fallbacks and logging
  console.log("Raw API Responses:", {
    productsResponse,
    categoriesResponse,
    customersResponse,
    ordersResponse,
    lowStockResponse,
    stockLevelsResponse,
  });

  // Updated data extraction to handle different response structures
  const products = productsResponse?.data?.data || productsResponse?.data || [];
  const categories =
    categoriesResponse?.data?.data || categoriesResponse?.data || [];
  const customers =
    customersResponse?.data?.data || customersResponse?.data || [];
  const orders = ordersResponse?.data?.data || ordersResponse?.data || [];
  const lowStockProducts = lowStockResponse?.data || [];
  const stockLevels = stockLevelsResponse?.data || [];

  console.log("Extracted Data:", {
    products: products.length,
    categories: categories.length,
    customers: customers.length,
    orders: orders.length,
    lowStockProducts: lowStockProducts.length,
    stockLevels: stockLevels.length,
  });

  // Calculate dashboard stats
  const dashboardStats = [
    {
      title: "Total Products",
      value: products.length.toString(),
      change: "+5.2%",
      trend: "up",
      icon: Package,
      color: "text-orange-600",
    },
    {
      title: "Categories",
      value: categories.length.toString(),
      change: "+2.1%",
      trend: "up",
      icon: Tag,
      color: "text-blue-600",
    },
    {
      title: "Customers",
      value: customers.length.toString(),
      change: "+12.5%",
      trend: "up",
      icon: Users,
      color: "text-purple-600",
    },
    {
      title: "Total Orders",
      value: orders.length.toString(),
      change: "+15.3%",
      trend: "up",
      icon: ShoppingCart,
      color: "text-green-600",
    },
  ];

  // Filter functions
  const filteredProducts = products.filter(
    (product: unknown) =>
      product.name?.toLowerCase().includes(productSearch.toLowerCase()) ||
      product.sku?.toLowerCase().includes(productSearch.toLowerCase()) ||
      product.category?.toLowerCase().includes(productSearch.toLowerCase())
  );

  const filteredCustomers = customers.filter(
    (customer: unknown) =>
      customer.user?.name
        ?.toLowerCase()
        .includes(customerSearch.toLowerCase()) ||
      customer.user?.email?.toLowerCase().includes(customerSearch.toLowerCase())
  );

  const filteredCategories = categories.filter(
    (category: unknown) =>
      category.name?.toLowerCase().includes(categorySearch.toLowerCase()) ||
      category.description?.toLowerCase().includes(categorySearch.toLowerCase())
  );

  // Event handlers
  const handleDeleteProduct = (id: string) => {
    if (confirm("Are you sure you want to delete this product?")) {
      deleteProductMutation.mutate(id);
    }
  };

  const handleDeleteCategory = (id: string) => {
    if (confirm("Are you sure you want to delete this category?")) {
      deleteCategoryMutation.mutate(id);
    }
  };

  const handleEditProduct = (product: unknown) => {
    setSelectedProduct(product);
    setIsEditProductOpen(true);
  };

  const handleEditCategory = (category: unknown) => {
    setSelectedCategory(category);
    setIsEditCategoryOpen(true);
  };

  const handleViewProduct = (product: unknown) => {
    toast.info(`Viewing product: ${product.name}`);
  };

  const handleViewCategory = (category: unknown) => {
    toast.info(`Viewing category: ${category.name}`);
  };

  const handleStockUpdate = (productId: string, data: unknown) => {
    updateStockMutation.mutate({ id: productId, data });
  };

  // Helper function for status badges
  const getStatusBadge = (status: string) => {
    const statusMap: Record<string, unknown> = {
      delivered: { variant: "default", color: "text-green-600" },
      processing: { variant: "secondary", color: "text-blue-600" },
      shipped: { variant: "outline", color: "text-orange-600" },
      pending: { variant: "destructive", color: "text-red-600" },
      active: { variant: "default", color: "text-green-600" },
      inactive: { variant: "secondary", color: "text-gray-600" },
    };

    const config = statusMap[status] || {
      variant: "secondary",
      color: "text-gray-600",
    };

    return (
      <Badge variant={config.variant} className={config.color}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Badge>
    );
  };

  // Add this useEffect to handle auth failures
  useEffect(() => {
    const hasAuthErrors = [
      productsError,
      categoriesError,
      customersError,
      ordersError,
      lowStockError,
      stockLevelsError,
    ].some((error) => error?.message === "Unauthenticated.");

    if (hasAuthErrors) {
      console.warn("🚨 Multiple auth failures detected, redirecting to login");
      // Clear any remaining auth data
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user");
      // Redirect to login
      window.location.href = "/login";
    }
  }, [
    productsError,
    categoriesError,
    customersError,
    ordersError,
    lowStockError,
    stockLevelsError,
  ]);

  return (
    <div className="min-h-screen bg-muted/30">
      <Header isAdmin={true} />

      {/* Header */}
      <header className="bg-background border-b">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold">Admin Dashboard</h1>
              <p className="text-muted-foreground">
                Manage your e-commerce store
              </p>
            </div>
            <div className="flex items-center gap-4">
              <Button
                variant="default"
                onClick={() => setIsAddProductOpen(true)}
              >
                <Plus className="h-4 w-4 mr-2" />
                Add Product
              </Button>
              <Button
                variant="outline"
                onClick={() => setIsAddCategoryOpen(true)}
              >
                <Plus className="h-4 w-4 mr-2" />
                Add Category
              </Button>
            </div>
          </div>
        </div>
      </header>

      <div className="container mx-auto px-4 py-6">
        <Tabs
          value={activeTab}
          onValueChange={setActiveTab}
          className="space-y-6"
        >
          <TabsList className="grid w-full grid-cols-6">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="products">Products</TabsTrigger>
            <TabsTrigger value="categories">Categories</TabsTrigger>
            <TabsTrigger value="inventory">Inventory</TabsTrigger>
            <TabsTrigger value="orders">Orders</TabsTrigger>
            <TabsTrigger value="customers">Customers</TabsTrigger>
          </TabsList>

          {/* Overview Tab */}
          <TabsContent value="overview" className="space-y-6">
            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {dashboardStats.map((stat, index) => (
                <Card key={index} className="card-gradient">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                      {stat.title}
                    </CardTitle>
                    <stat.icon className={`h-4 w-4 ${stat.color}`} />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">{stat.value}</div>
                    <p className="text-xs text-muted-foreground flex items-center gap-1">
                      <TrendingUp className="h-3 w-3 text-green-600" />
                      {stat.change} from last month
                    </p>
                  </CardContent>
                </Card>
              ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Recent Orders */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <ShoppingCart className="h-5 w-5" />
                    Recent Orders
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {ordersLoading ? (
                      <div className="flex items-center justify-center py-4">
                        <Loader2 className="h-4 w-4 animate-spin" />
                      </div>
                    ) : orders.length === 0 ? (
                      <div className="text-center py-4 text-muted-foreground">
                        No orders found. Orders will appear here once customers
                        place them.
                      </div>
                    ) : (
                      orders.slice(0, 5).map((order: unknown) => (
                        <div
                          key={order._id}
                          className="flex items-center justify-between p-3 bg-muted/50 rounded-lg"
                        >
                          <div>
                            <p className="font-medium">
                              {order.order_number ||
                                `Order #${order._id?.slice(-6)}`}
                            </p>
                            <p className="text-sm text-muted-foreground">
                              {new Date(order.created_at).toLocaleDateString()}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="font-medium">
                              ${order.total || "0.00"}
                            </p>
                            {getStatusBadge(order.status || "pending")}
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                </CardContent>
              </Card>

              {/* Low Stock Alert */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2 text-warning">
                    <AlertTriangle className="h-5 w-5" />
                    Low Stock Alerts
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {lowStockProducts.length === 0 ? (
                      <div className="text-center py-4 text-muted-foreground">
                        No low stock items. All products are well stocked!
                      </div>
                    ) : (
                      lowStockProducts
                        .slice(0, 5)
                        .map((product: unknown, index: number) => (
                          <div
                            key={index}
                            className="flex items-center justify-between p-3 bg-warning/10 rounded-lg border border-warning/20"
                          >
                            <div>
                              <p className="font-semibold">{product.name}</p>
                              <p className="text-sm text-muted-foreground">
                                SKU: {product.sku}
                              </p>
                            </div>
                            <div className="text-right">
                              <p className="text-warning font-bold">
                                {product.current_stock ||
                                  product.stock_quantity}{" "}
                                left
                              </p>
                              <p className="text-xs text-muted-foreground">
                                Status: {product.status}
                              </p>
                            </div>
                          </div>
                        ))
                    )}
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Products Tab */}
          <TabsContent value="products" className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="flex items-center gap-2">
                    <Package className="h-5 w-5" />
                    Product Management ({filteredProducts.length} products)
                  </CardTitle>
                  <div className="flex items-center gap-2">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        placeholder="Search products..."
                        className="pl-10 w-64"
                        value={productSearch}
                        onChange={(e) => setProductSearch(e.target.value)}
                      />
                    </div>
                    <Button variant="outline">
                      <Filter className="h-4 w-4" />
                      Filter
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {productsLoading ? (
                  <div className="flex items-center justify-center py-8">
                    <Loader2 className="h-8 w-8 animate-spin" />
                  </div>
                ) : filteredProducts.length === 0 ? (
                  <div className="text-center py-8">
                    <Package className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                    <h3 className="text-lg font-semibold">No products found</h3>
                    <p className="text-muted-foreground mb-4">
                      {productSearch
                        ? "No products match your search criteria."
                        : "Start by adding your first product."}
                    </p>
                    <Button onClick={() => setIsAddProductOpen(true)}>
                      <Plus className="h-4 w-4 mr-2" />
                      Add Product
                    </Button>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {filteredProducts.map((product: unknown) => (
                      <ProductCard
                        key={product._id}
                        product={product}
                        onEdit={handleEditProduct}
                        onDelete={handleDeleteProduct}
                        onView={handleViewProduct}
                      />
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Categories Tab */}
          <TabsContent value="categories" className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="flex items-center gap-2">
                    <Tag className="h-5 w-5" />
                    Category Management ({filteredCategories.length} categories)
                  </CardTitle>
                  <div className="flex items-center gap-2">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        placeholder="Search categories..."
                        className="pl-10 w-64"
                        value={categorySearch}
                        onChange={(e) => setCategorySearch(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {categoriesLoading ? (
                  <div className="flex items-center justify-center py-8">
                    <Loader2 className="h-8 w-8 animate-spin" />
                  </div>
                ) : filteredCategories.length === 0 ? (
                  <div className="text-center py-8">
                    <Tag className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                    <h3 className="text-lg font-semibold">
                      No categories found
                    </h3>
                    <p className="text-muted-foreground mb-4">
                      {categorySearch
                        ? "No categories match your search criteria."
                        : "Start by adding your first category."}
                    </p>
                    <Button onClick={() => setIsAddCategoryOpen(true)}>
                      <Plus className="h-4 w-4 mr-2" />
                      Add Category
                    </Button>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {filteredCategories.map((category: unknown) => (
                      <CategoryCard
                        key={category._id}
                        category={category}
                        onEdit={handleEditCategory}
                        onDelete={handleDeleteCategory}
                        onView={handleViewCategory}
                      />
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Inventory Tab */}
          <TabsContent value="inventory" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <BarChart3 className="h-5 w-5" />
                  Inventory Management ({products.length} products)
                </CardTitle>
              </CardHeader>
              <CardContent>
                {stockLevelsLoading ? (
                  <div className="flex items-center justify-center py-8">
                    <Loader2 className="h-8 w-8 animate-spin" />
                  </div>
                ) : products.length === 0 ? (
                  <div className="text-center py-8">
                    <BarChart3 className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                    <h3 className="text-lg font-semibold">No inventory data</h3>
                    <p className="text-muted-foreground">
                      Add products to start managing inventory.
                    </p>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {products.map((product: unknown) => (
                      <InventoryUpdateForm
                        key={product._id}
                        product={product}
                        onStockUpdate={handleStockUpdate}
                        isUpdating={updateStockMutation.isPending}
                      />
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Orders Tab */}
          <TabsContent value="orders" className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle>
                    Order Management ({orders.length} orders)
                  </CardTitle>
                  <div className="flex items-center gap-2">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        placeholder="Search orders..."
                        className="pl-10 w-64"
                      />
                    </div>
                    <Button variant="outline">
                      <Filter className="h-4 w-4" />
                      Filter
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {ordersLoading ? (
                  <div className="flex items-center justify-center py-8">
                    <Loader2 className="h-8 w-8 animate-spin" />
                  </div>
                ) : orders.length === 0 ? (
                  <div className="text-center py-8">
                    <ShoppingCart className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                    <h3 className="text-lg font-semibold">No orders yet</h3>
                    <p className="text-muted-foreground">
                      Orders will appear here when customers make purchases.
                    </p>
                  </div>
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Order ID</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Total</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Actions</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {orders.slice(0, 10).map((order: unknown) => (
                        <TableRow key={order._id}>
                          <TableCell className="font-mono">
                            {order.order_number || `#${order._id?.slice(-6)}`}
                          </TableCell>
                          <TableCell>
                            {order.customer?.user?.name || "Unknown Customer"}
                          </TableCell>
                          <TableCell>
                            {getStatusBadge(order.status || "pending")}
                          </TableCell>
                          <TableCell className="font-semibold">
                            ${order.total || "0.00"}
                          </TableCell>
                          <TableCell>
                            {new Date(order.created_at).toLocaleDateString()}
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Button variant="outline" size="sm">
                                View
                              </Button>
                              <Button variant="outline" size="sm">
                                Edit
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Customers Tab */}
          <TabsContent value="customers" className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="flex items-center gap-2">
                    <Users className="h-5 w-5" />
                    Customer Management ({filteredCustomers.length} customers)
                  </CardTitle>
                  <div className="flex items-center gap-2">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        placeholder="Search customers..."
                        className="pl-10 w-64"
                        value={customerSearch}
                        onChange={(e) => setCustomerSearch(e.target.value)}
                      />
                    </div>
                    <Button variant="outline">
                      <Filter className="h-4 w-4" />
                      Filter
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {customersLoading ? (
                  <div className="flex items-center justify-center py-8">
                    <Loader2 className="h-8 w-8 animate-spin" />
                  </div>
                ) : filteredCustomers.length === 0 ? (
                  <div className="text-center py-8">
                    <Users className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                    <h3 className="text-lg font-semibold">
                      No customers found
                    </h3>
                    <p className="text-muted-foreground">
                      {customerSearch
                        ? "No customers match your search criteria."
                        : "Customers will appear here when they register."}
                    </p>
                  </div>
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Customer</TableHead>
                        <TableHead>Contact</TableHead>
                        <TableHead>Orders</TableHead>
                        <TableHead>Total Spent</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Joined</TableHead>
                        <TableHead>Last Order</TableHead>
                        <TableHead>Actions</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {filteredCustomers.map((customer: unknown) => (
                        <TableRow key={customer._id}>
                          <TableCell>
                            <div className="flex items-center gap-3">
                              <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                <span className="text-sm font-medium text-primary">
                                  {customer.user?.name
                                    ?.split(" ")
                                    .map((n: string) => n[0])
                                    .join("") || "N/A"}
                                </span>
                              </div>
                              <div>
                                <p className="font-medium">
                                  {customer.user?.name || "Unknown"}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                  ID: {customer._id?.slice(-6)}
                                </p>
                              </div>
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className="space-y-1">
                              <div className="flex items-center gap-2 text-sm">
                                <Mail className="h-3 w-3" />
                                <span className="text-muted-foreground">
                                  {customer.user?.email || "No email"}
                                </span>
                              </div>
                              <div className="flex items-center gap-2 text-sm">
                                <Phone className="h-3 w-3" />
                                <span className="text-muted-foreground">
                                  {customer.phone || "No phone"}
                                </span>
                              </div>
                            </div>
                          </TableCell>
                          <TableCell className="text-center">
                            {customer.orders_count || 0}
                          </TableCell>
                          <TableCell className="font-semibold">
                            ${customer.total_spent?.toFixed(2) || "0.00"}
                          </TableCell>
                          <TableCell>
                            {getStatusBadge(customer.user?.status || "active")}
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-1 text-sm text-muted-foreground">
                              <Calendar className="h-3 w-3" />
                              {new Date(
                                customer.created_at
                              ).toLocaleDateString()}
                            </div>
                          </TableCell>
                          <TableCell className="text-sm text-muted-foreground">
                            {customer.last_order_date
                              ? new Date(
                                  customer.last_order_date
                                ).toLocaleDateString()
                              : "Never"}
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Button variant="outline" size="sm">
                                <Eye className="h-4 w-4" />
                              </Button>
                              <Button variant="outline" size="sm">
                                <Mail className="h-4 w-4" />
                              </Button>
                              <Button variant="outline" size="sm">
                                <Edit className="h-4 w-4" />
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>

      {/* Form Dialogs */}
      <ProductForm
        isOpen={isAddProductOpen}
        onOpenChange={setIsAddProductOpen}
        categories={categories}
        onSubmit={(data) => createProductMutation.mutate(data)}
        isLoading={createProductMutation.isPending}
        mode="create"
      />

      <ProductForm
        isOpen={isEditProductOpen}
        onOpenChange={setIsEditProductOpen}
        product={selectedProduct}
        categories={categories}
        onUpdate={(id, data) => updateProductMutation.mutate({ id, data })}
        isLoading={updateProductMutation.isPending}
        mode="edit"
      />

      <CategoryForm
        isOpen={isAddCategoryOpen}
        onOpenChange={setIsAddCategoryOpen}
        categories={categories}
        onSubmit={(data) => createCategoryMutation.mutate(data)}
        isLoading={createCategoryMutation.isPending}
        mode="create"
      />

      <CategoryForm
        isOpen={isEditCategoryOpen}
        onOpenChange={setIsEditCategoryOpen}
        category={selectedCategory}
        categories={categories}
        onUpdate={(id, data) => updateCategoryMutation.mutate({ id, data })}
        isLoading={updateCategoryMutation.isPending}
        mode="edit"
      />

      <DebugPanel />
      <TokenDebug />
    </div>
  );
};

export default AdminDashboard;
