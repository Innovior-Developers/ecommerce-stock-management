import { useState, useEffect } from "react";
import {
  useGetProductsQuery,
  useCreateProductMutation,
  useUpdateProductMutation,
  useDeleteProductMutation,
  useGetCategoriesQuery,
  useCreateCategoryMutation,
  useUpdateCategoryMutation,
  useDeleteCategoryMutation,
  useGetCustomersQuery,
  useGetOrdersQuery,
  useGetLowStockQuery,
  useGetStockLevelsQuery,
  useUpdateStockMutation,
} from "@/store/api/adminApi";
import { useAppSelector } from "@/store/hooks";
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

// Import form components
import {
  ProductForm,
  ProductCard,
} from "@/components/admin forms/Product.form";
import {
  CategoryForm,
  CategoryCard,
} from "@/components/admin forms/Category.form";
import { InventoryUpdateForm } from "@/components/admin forms/Inventory.form";
import { CategoriesDebug } from "@/components/CategoriesDebug";

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

  // Get user from Redux state
  const { user, isAuthenticated } = useAppSelector((state) => state.auth);

  // RTK Query hooks
  const {
    data: productsResponse,
    isLoading: productsLoading,
  } = useGetProductsQuery({ search: productSearch }, { skip: !isAuthenticated });

  const {
    data: categoriesResponse,
    isLoading: categoriesLoading,
    error: categoriesError,
  } = useGetCategoriesQuery({ search: categorySearch }, { skip: !isAuthenticated });

  const {
    data: customersResponse,
    isLoading: customersLoading,
  } = useGetCustomersQuery({ search: customerSearch }, { skip: !isAuthenticated });

  const {
    data: ordersResponse,
    isLoading: ordersLoading,
  } = useGetOrdersQuery(undefined, { skip: !isAuthenticated });

  const { data: lowStockResponse } = useGetLowStockQuery(undefined, { skip: !isAuthenticated });

  const {
    data: stockLevelsResponse,
    isLoading: stockLevelsLoading,
  } = useGetStockLevelsQuery(undefined, { skip: !isAuthenticated });

  // Mutations
  const [createProduct] = useCreateProductMutation();
  const [updateProduct] = useUpdateProductMutation();
  const [deleteProduct] = useDeleteProductMutation();
  const [createCategory] = useCreateCategoryMutation();
  const [updateCategory] = useUpdateCategoryMutation();
  const [deleteCategory] = useDeleteCategoryMutation();
  const [updateStock] = useUpdateStockMutation();

  // Extract data with proper fallbacks
  const products = productsResponse?.data?.data || productsResponse?.data || [];
  const categories = categoriesResponse?.data?.data || categoriesResponse?.data || [];
  const customers =
    customersResponse?.data?.data || customersResponse?.data || [];
  const orders = ordersResponse?.data?.data || ordersResponse?.data || [];
  const lowStockProducts = lowStockResponse?.data || [];
  const stockLevels = stockLevelsResponse?.data || [];

  // Debug categories in console
  console.log("🏷️ Categories for Product Form:", {
    categoriesResponse,
    categories,
    categoriesLoading,
    categoriesError
  });

  // Handle mutations
  const handleCreateProduct = async (data: unknown) => {
    try {
      await createProduct(data).unwrap();
      setIsAddProductOpen(false);
      toast.success("Product created successfully!");
    } catch (error: unknown) {
      toast.error(error.data?.message || "Failed to create product");
    }
  };

  const handleUpdateProduct = async (id: string, data: unknown) => {
    try {
      await updateProduct({ id, data }).unwrap();
      setIsEditProductOpen(false);
      setSelectedProduct(null);
      toast.success("Product updated successfully!");
    } catch (error: unknown) {
      toast.error(error.data?.message || "Failed to update product");
    }
  };

  const handleDeleteProduct = async (id: string) => {
    try {
      await deleteProduct(id).unwrap();
      toast.success("Product deleted successfully!");
    } catch (error: unknown) {
      toast.error(error.data?.message || "Failed to delete product");
    }
  };

  const handleCreateCategory = async (data: unknown) => {
    try {
      await createCategory(data).unwrap();
      setIsAddCategoryOpen(false);
      toast.success("Category created successfully!");
    } catch (error: unknown) {
      toast.error(error.data?.message || "Failed to create category");
    }
  };

  const handleUpdateCategory = async (id: string, data: unknown) => {
    try {
      await updateCategory({ id, data }).unwrap();
      setIsEditCategoryOpen(false);
      setSelectedCategory(null);
      toast.success("Category updated successfully!");
    } catch (error: unknown) {
      toast.error(error.data?.message || "Failed to update category");
    }
  };

  const handleDeleteCategory = async (id: string) => {
    try {
      await deleteCategory(id).unwrap();
      toast.success("Category deleted successfully!");
    } catch (error: unknown) {
      toast.error(error.data?.message || "Failed to delete category");
    }
  };

  const handleStockUpdate = async (id: string, data: unknown) => {
    try {
      await updateStock({ id, data }).unwrap();
      toast.success("Stock updated successfully!");
    } catch (error: unknown) {
      toast.error(error.data?.message || "Failed to update stock");
    }
  };

  // Filter data
  const filteredProducts = products.filter(
    (product: unknown) =>
      product.name?.toLowerCase().includes(productSearch.toLowerCase()) ||
      product.description?.toLowerCase().includes(productSearch.toLowerCase())
  );

  const filteredCustomers = customers.filter(
    (customer: unknown) =>
      customer.user?.name
        ?.toLowerCase()
        .includes(customerSearch.toLowerCase()) ||
      customer.user?.email?.toLowerCase().includes(customerSearch.toLowerCase())
  );

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
      color: "text-green-600",
    },
    {
      title: "Orders",
      value: orders.length.toString(),
      change: "+8.3%",
      trend: "up",
      icon: ShoppingCart,
      color: "text-purple-600",
    },
  ];

  const getStatusBadge = (status: string) => {
    const variants: Record<string, string> = {
      active: "bg-green-100 text-green-800",
      inactive: "bg-red-100 text-red-800",
      pending: "bg-yellow-100 text-yellow-800",
      in_stock: "bg-green-100 text-green-800",
      low_stock: "bg-yellow-100 text-yellow-800",
      out_of_stock: "bg-red-100 text-red-800",
    };

    return (
      <Badge className={variants[status] || "bg-gray-100 text-gray-800"}>
        {status.replace("_", " ").toUpperCase()}
      </Badge>
    );
  };

  // Show toast if not authenticated (logout error)
  useEffect(() => {
    if (!isAuthenticated) {
      toast.error("Session expired or unauthorized. Please login again.");
    }
  }, [isAuthenticated]);

  // Show a loading screen if authentication is not yet confirmed
  if (!isAuthenticated) {
    return (
      <div className="flex h-screen items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin" />
        <p className="ml-4">Verifying authentication...</p>
      </div>
    );
  }

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
                Welcome back, {user?.name || "Admin"}! Here's what's happening.
              </p>
            </div>
            <div className="flex items-center space-x-4">
              <Button
                onClick={() => setIsAddProductOpen(true)}
                className="flex items-center space-x-2"
              >
                <Plus className="h-4 w-4" />
                <span>Add Product</span>
              </Button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-6">
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
                          className="flex items-center justify-between p-3 border rounded-lg"
                        >
                          <div className="flex items-center space-x-3">
                            <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                            <div>
                              <p className="font-medium">
                                Order #{order._id?.slice(-6)}
                              </p>
                              <p className="text-sm text-muted-foreground">
                                {order.customer?.name || "Customer"}
                              </p>
                            </div>
                          </div>
                          <div className="text-right">
                            <p className="font-medium">
                              ${order.total_amount?.toFixed(2) || "0.00"}
                            </p>
                            <p className="text-sm text-muted-foreground">
                              {new Date(order.created_at).toLocaleDateString()}
                            </p>
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                </CardContent>
              </Card>

              {/* Low Stock Alerts */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <AlertTriangle className="h-5 w-5 text-orange-500" />
                    Low Stock Alerts
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {lowStockProducts.length === 0 ? (
                      <div className="text-center py-4 text-muted-foreground">
                        All products are well stocked!
                      </div>
                    ) : (
                      lowStockProducts.slice(0, 5).map((product: unknown) => (
                        <div
                          key={product._id}
                          className="flex items-center justify-between p-3 border border-orange-200 rounded-lg bg-orange-50"
                        >
                          <div>
                            <p className="font-medium">{product.name}</p>
                            <p className="text-sm text-muted-foreground">
                              Stock: {product.stock_quantity}
                            </p>
                          </div>
                          <Badge variant="outline" className="text-orange-600">
                            Low Stock
                          </Badge>
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
                    <Button onClick={() => setIsAddProductOpen(true)}>
                      <Plus className="h-4 w-4" />
                      Add Product
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
                    <p className="text-muted-foreground">
                      {productSearch
                        ? "No products match your search criteria."
                        : "Get started by adding your first product."}
                    </p>
                    <Button
                      onClick={() => setIsAddProductOpen(true)}
                      className="mt-4"
                    >
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
                        onEdit={(product) => {
                          setSelectedProduct(product);
                          setIsEditProductOpen(true);
                        }}
                        onDelete={(id) => handleDeleteProduct(id)}
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
                    Category Management ({categories.length} categories)
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
                    <Button onClick={() => setIsAddCategoryOpen(true)}>
                      <Plus className="h-4 w-4" />
                      Add Category
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {categoriesLoading ? (
                  <div className="flex items-center justify-center py-8">
                    <Loader2 className="h-8 w-8 animate-spin" />
                  </div>
                ) : categories.length === 0 ? (
                  <div className="text-center py-8">
                    <Tag className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                    <h3 className="text-lg font-semibold">
                      No categories found
                    </h3>
                    <p className="text-muted-foreground">
                      Create categories to organize your products.
                    </p>
                    <Button
                      onClick={() => setIsAddCategoryOpen(true)}
                      className="mt-4"
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      Add Category
                    </Button>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {categories.map((category: unknown) => (
                      <CategoryCard
                        key={category._id}
                        category={category}
                        onEdit={(category) => {
                          setSelectedCategory(category);
                          setIsEditCategoryOpen(true);
                        }}
                        onDelete={(id) => handleDeleteCategory(id)}
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
                  Inventory Management
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
                        isUpdating={false}
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
                            #{order._id?.slice(-6)}
                          </TableCell>
                          <TableCell>
                            <div>
                              <p className="font-medium">
                                {order.customer?.name || "Unknown"}
                              </p>
                              <p className="text-sm text-muted-foreground">
                                {order.customer?.email || "No email"}
                              </p>
                            </div>
                          </TableCell>
                          <TableCell>
                            {getStatusBadge(order.status || "pending")}
                          </TableCell>
                          <TableCell className="font-semibold">
                            ${order.total_amount?.toFixed(2) || "0.00"}
                          </TableCell>
                          <TableCell>
                            {new Date(order.created_at).toLocaleDateString()}
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center space-x-2">
                              <Button variant="ghost" size="sm">
                                <Eye className="h-4 w-4" />
                              </Button>
                              <Button variant="ghost" size="sm">
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
                            <div className="flex items-center space-x-2">
                              <Button variant="ghost" size="sm">
                                <Eye className="h-4 w-4" />
                              </Button>
                              <Button variant="ghost" size="sm">
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
      </main>

<CategoriesDebug /> 
      {/* Forms */}
      <ProductForm
        isOpen={isAddProductOpen}
        onOpenChange={setIsAddProductOpen}
        onSubmit={handleCreateProduct}
        categories={categories} // ✅ Make sure this is being passed
        mode="create"
      />

      <ProductForm
        isOpen={isEditProductOpen}
        onOpenChange={(open) => {
          setIsEditProductOpen(open);
          if (!open) setSelectedProduct(null);
        }}
        onUpdate={(id, data) => handleUpdateProduct(id, data)}
        product={selectedProduct}
        categories={categories} // ✅ Make sure this is being passed
        mode="edit"
      />

      <CategoryForm
        isOpen={isAddCategoryOpen}
        onOpenChange={setIsAddCategoryOpen}
        onSubmit={handleCreateCategory}
        categories={categories || []} // ✅ Provide fallback
        mode="create"
      />

      <CategoryForm
        isOpen={isEditCategoryOpen}
        onOpenChange={(open) => {
          setIsEditCategoryOpen(open);
          if (!open) setSelectedCategory(null);
        }}
        onUpdate={(id, data) => handleUpdateCategory(id, data)}
        category={selectedCategory} // ✅ Correct prop name
        categories={categories || []} // ✅ Provide fallback
        mode="edit"
      />
    </div>
  );
};

export default AdminDashboard;
