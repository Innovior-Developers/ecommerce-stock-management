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
  updateAdminCustomer,
  deleteAdminCustomer,
  getAdminOrders,
  updateAdminOrder,
  getLowStock,
  getStockLevels,
} from "@/api/Api";
import {
  Package,
  ShoppingCart,
  Users,
  DollarSign,
  TrendingUp,
  AlertTriangle,
  Plus,
  Search,
  Filter,
  Edit,
  Trash2,
  Eye,
  Mail,
  Phone,
  MapPin,
  Calendar,
  Star,
  Tag,
  Folder,
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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import Header from "@/components/Header";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";

const AdminDashboard = () => {
  const [activeTab, setActiveTab] = useState("overview");
  const [isAddProductOpen, setIsAddProductOpen] = useState(false);
  const [isAddCategoryOpen, setIsAddCategoryOpen] = useState(false);

  // Search states
  const [productSearch, setProductSearch] = useState("");
  const [customerSearch, setCustomerSearch] = useState("");
  const [categorySearch, setCategorySearch] = useState("");

  const queryClient = useQueryClient();

  // Products queries and mutations
  const { data: productsResponse, isLoading: productsLoading } = useQuery({
    queryKey: ["admin-products", productSearch],
    queryFn: () => getAdminProducts({ search: productSearch }),
  });

  const { data: categoriesResponse, isLoading: categoriesLoading } = useQuery({
    queryKey: ["admin-categories", categorySearch],
    queryFn: () => getAdminCategories({ search: categorySearch }),
  });

  const { data: customersResponse, isLoading: customersLoading } = useQuery({
    queryKey: ["admin-customers", customerSearch],
    queryFn: () => getAdminCustomers({ search: customerSearch }),
  });

  const { data: ordersResponse, isLoading: ordersLoading } = useQuery({
    queryKey: ["admin-orders"],
    queryFn: () => getAdminOrders(),
  });

  const { data: lowStockResponse } = useQuery({
    queryKey: ["low-stock"],
    queryFn: () => getLowStock(),
  });

  // Mutations
  const createProductMutation = useMutation({
    mutationFn: createAdminProduct,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-products"] });
      setIsAddProductOpen(false);
      toast.success("Product created successfully!");
    },
    onError: (error: unknown) => {
      toast.error(error.message || "Failed to create product");
    },
  });

  const createCategoryMutation = useMutation({
    mutationFn: createAdminCategory,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-categories"] });
      setIsAddCategoryOpen(false);
    },
  });

  const deleteProductMutation = useMutation({
    mutationFn: deleteAdminProduct,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-products"] });
    },
  });

  const deleteCategoryMutation = useMutation({
    mutationFn: deleteAdminCategory,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-categories"] });
    },
  });

  // Extract data from API responses
  const products = productsResponse?.data?.data || [];
  const categories = categoriesResponse?.data || [];
  const customers = customersResponse?.data?.data || [];
  const orders = ordersResponse?.data || [];
  const lowStockProducts = lowStockResponse?.data || [];

  // Calculate dashboard stats from real data
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

  const handleCreateProduct = (formData: FormData) => {
    const productData = {
      name: formData.get("name"),
      sku: formData.get("sku"),
      category: formData.get("category"),
      price: parseFloat(formData.get("price") as string),
      stock_quantity: parseInt(formData.get("stock") as string),
      description: formData.get("description"),
      status: "active",
    };

    createProductMutation.mutate(productData);
  };

  const handleCreateCategory = (formData: FormData) => {
    const categoryData = {
      name: formData.get("name"),
      description: formData.get("description"),
      status: "active",
      sort_order: 0,
    };

    createCategoryMutation.mutate(categoryData);
  };

  // Use real orders data instead of mock data
  const recentOrders = orders.slice(0, 5).map((order: unknown) => ({
    id: order._id || order.id,
    customer:
      order.customer?.user?.name || order.customer_name || "Unknown Customer",
    status: order.status,
    total: `$${order.total}`,
    date: new Date(order.created_at).toLocaleDateString(),
  }));

  // Add helper function for status badges
  const getStatusBadge = (status: string) => {
    const statusMap = {
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
      <Badge variant={config.variant as unknown} className={config.color}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Badge>
    );
  };

  return (
    <div className="min-h-screen bg-muted/30">
      {/* Admin Navbar */}
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
              <Dialog
                open={isAddProductOpen}
                onOpenChange={setIsAddProductOpen}
              >
                <DialogTrigger asChild>
                  <Button variant="default">
                    <Plus className="h-4 w-4" />
                    Add Product
                  </Button>
                </DialogTrigger>
                <DialogContent className="sm:max-w-[600px]">
                  <DialogHeader>
                    <DialogTitle>Add New Product</DialogTitle>
                    <DialogDescription>
                      Create a new product for your store.
                    </DialogDescription>
                  </DialogHeader>
                  <form
                    onSubmit={(e) => {
                      e.preventDefault();
                      const formData = new FormData(e.target as HTMLFormElement);
                      handleCreateProduct(formData);
                    }}
                    className="space-y-4"
                  >
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label htmlFor="productName">Product Name</Label>
                        <Input
                          id="productName"
                          name="name"
                          placeholder="Enter product name"
                          required
                        />
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="productSku">SKU</Label>
                        <Input
                          id="productSku"
                          name="sku"
                          placeholder="Enter SKU"
                          required
                        />
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label htmlFor="productPrice">Price</Label>
                        <Input
                          id="productPrice"
                          name="price"
                          type="number"
                          step="0.01"
                          placeholder="0.00"
                          required
                        />
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="productStock">Stock Quantity</Label>
                        <Input
                          id="productStock"
                          name="stock"
                          type="number"
                          placeholder="0"
                          required
                        />
                      </div>
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="productCategory">Category</Label>
                      <Select name="category" required>
                        <SelectTrigger>
                          <SelectValue placeholder="Select category" />
                        </SelectTrigger>
                        <SelectContent>
                          {categories.map((category: unknown) => (
                            <SelectItem key={category._id} value={category.name}>
                              {category.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="productDescription">Description</Label>
                      <Textarea
                        id="productDescription"
                        name="description"
                        placeholder="Product description"
                        rows={3}
                      />
                    </div>

                    <div className="flex justify-end gap-2">
                      <Button
                        type="button"
                        variant="outline"
                        onClick={() => setIsAddProductOpen(false)}
                      >
                        Cancel
                      </Button>
                      <Button type="submit" disabled={createProductMutation.isPending}>
                        {createProductMutation.isPending ? "Adding..." : "Add Product"}
                      </Button>
                    </div>
                  </form>
                </DialogContent>
              </Dialog>
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
          <TabsList className="grid w-full grid-cols-5">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="products">Products</TabsTrigger>
            <TabsTrigger value="categories">Categories</TabsTrigger>
            <TabsTrigger value="orders">Orders</TabsTrigger>
            <TabsTrigger value="customers">Customers</TabsTrigger>
          </TabsList>

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
                    ) : (
                      orders.slice(0, 5).map((order: unknown) => (
                        <div
                          key={order._id}
                          className="flex items-center justify-between p-3 bg-muted/50 rounded-lg"
                        >
                          <div>
                            <p className="font-medium">{order.order_number}</p>
                            <p className="text-sm text-muted-foreground">
                              {new Date(order.created_at).toLocaleDateString()}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="font-medium">${order.total}</p>
                            <Badge
                              variant={
                                order.status === "delivered"
                                  ? "default"
                                  : "secondary"
                              }
                            >
                              {order.status}
                            </Badge>
                          </div>
                        </div>
                      ))
                   ) }
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
                    {lowStockProducts.map((product, index) => (
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
                            {product.stock} left
                          </p>
                          <p className="text-xs text-muted-foreground">
                            Min: {product.threshold}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <TabsContent value="orders" className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle>Order Management</CardTitle>
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
                    {recentOrders.map((order) => (
                      <TableRow key={order.id}>
                        <TableCell className="font-mono">{order.id}</TableCell>
                        <TableCell>{order.customer}</TableCell>
                        <TableCell>{getStatusBadge(order.status)}</TableCell>
                        <TableCell className="font-semibold">
                          {order.total}
                        </TableCell>
                        <TableCell>{order.date}</TableCell>
                        <TableCell>
                          <Button variant="outline" size="sm">
                            View Details
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="products" className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="flex items-center gap-2">
                    <Package className="h-5 w-5" />
                    Product Management
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
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Product</TableHead>
                        <TableHead>SKU</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead>Price</TableHead>
                        <TableHead>Stock</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Actions</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {filteredProducts.map((product: unknown) => (
                        <TableRow key={product._id}>
                          <TableCell>
                            <div className="flex items-center gap-3">
                              <div className="w-10 h-10 bg-muted rounded-md flex items-center justify-center">
                                <Package className="h-5 w-5 text-muted-foreground" />
                              </div>
                              <div>
                                <p className="font-medium">{product.name}</p>
                                <p className="text-sm text-muted-foreground">
                                  {product.description?.substring(0, 30)}...
                                </p>
                              </div>
                            </div>
                          </TableCell>
                          <TableCell className="font-mono">
                            {product.sku}
                          </TableCell>
                          <TableCell>{product.category}</TableCell>
                          <TableCell className="font-semibold">
                            ${product.price}
                          </TableCell>
                          <TableCell>
                            <span
                              className={
                                product.stock_quantity === 0
                                  ? "text-destructive"
                                  : product.stock_quantity < 5
                                  ? "text-warning"
                                  : ""
                              }
                            >
                              {product.stock_quantity}
                            </span>
                          </TableCell>
                          <TableCell>
                            <Badge
                              variant={
                                product.status === "active"
                                  ? "default"
                                  : "secondary"
                              }
                            >
                              {product.status}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Button variant="outline" size="sm">
                                <Eye className="h-4 w-4" />
                              </Button>
                              <Button variant="outline" size="sm">
                                <Edit className="h-4 w-4" />
                              </Button>
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleDeleteProduct(product._id)}
                              >
                                <Trash2 className="h-4 w-4" />
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

          <TabsContent value="categories" className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="flex items-center gap-2">
                    <Tag className="h-5 w-5" />
                    Category Management
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
                    <Dialog
                      open={isAddCategoryOpen}
                      onOpenChange={setIsAddCategoryOpen}
                    >
                      <DialogTrigger asChild>
                        <Button variant="default">
                          <Plus className="h-4 w-4" />
                          Add Category
                        </Button>
                      </DialogTrigger>
                      <DialogContent className="max-w-md">
                        <DialogHeader>
                          <DialogTitle>Add New Category</DialogTitle>
                          <DialogDescription>
                            Create a new product category.
                          </DialogDescription>
                        </DialogHeader>
                        <form
                          onSubmit={(e) => {
                            e.preventDefault();
                            const formData = new FormData(e.target as HTMLFormElement);
                            handleCreateCategory(formData);
                          }}
                          className="space-y-4"
                        >
                          <div className="space-y-2">
                            <Label htmlFor="categoryName">Category Name</Label>
                            <Input
                              id="categoryName"
                              name="name"
                              placeholder="Enter category name"
                              required
                            />
                          </div>
                          <div className="space-y-2">
                            <Label htmlFor="categoryDescription">Description</Label>
                            <Textarea
                              id="categoryDescription"
                              name="description"
                              placeholder="Category description"
                              rows={3}
                            />
                          </div>
                          <div className="space-y-2">
                            <Label htmlFor="categorySortOrder">Sort Order</Label>
                            <Input
                              id="categorySortOrder"
                              name="sort_order"
                              type="number"
                              placeholder="0"
                              defaultValue="0"
                            />
                          </div>
                          <div className="flex justify-end gap-2">
                            <Button
                              type="button"
                              variant="outline"
                              onClick={() => setIsAddCategoryOpen(false)}
                            >
                              Cancel
                            </Button>
                            <Button type="submit" disabled={createCategoryMutation.isPending}>
                              {createCategoryMutation.isPending ? "Adding..." : "Add Category"}
                            </Button>
                          </div>
                        </form>
                      </DialogContent>
                    </Dialog>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Category</TableHead>
                      <TableHead>Description</TableHead>
                      <TableHead>Products</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Created Date</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {filteredCategories.map((category) => (
                      <TableRow key={category.id}>
                        <TableCell>
                          <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-primary/10 rounded-md flex items-center justify-center">
                              <Folder className="h-5 w-5 text-primary" />
                            </div>
                            <div>
                              <p className="font-medium">{category.name}</p>
                            </div>
                          </div>
                        </TableCell>
                        <TableCell className="text-muted-foreground max-w-xs">
                          {category.description}
                        </TableCell>
                        <TableCell className="text-center">
                          {category.products}
                        </TableCell>
                        <TableCell>{getStatusBadge(category.status)}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-1 text-sm text-muted-foreground">
                            <Calendar className="h-3 w-3" />
                            {category.createdDate}
                          </div>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm">
                              <Eye className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="sm">
                              <Edit className="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="sm">
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="customers" className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="flex items-center gap-2">
                    <Users className="h-5 w-5" />
                    Customer Management
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
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Customer</TableHead>
                      <TableHead>Contact</TableHead>
                      <TableHead>Orders</TableHead>
                      <TableHead>Total Spent</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Join Date</TableHead>
                      <TableHead>Last Order</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {filteredCustomers.map((customer) => (
                      <TableRow key={customer.id}>
                        <TableCell>
                          <div className="flex items-center gap-3">
                            <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                              <span className="text-sm font-medium text-primary">
                                {customer.name
                                  .split(" ")
                                  .map((n) => n[0])
                                  .join("")}
                              </span>
                            </div>
                            <div>
                              <p className="font-medium">{customer.name}</p>
                              <p className="text-sm text-muted-foreground">
                                {customer.email}
                              </p>
                            </div>
                          </div>
                        </TableCell>
                        <TableCell>
                          <div className="space-y-1">
                            <div className="flex items-center gap-2 text-sm">
                              <Mail className="h-3 w-3" />
                              <span className="text-muted-foreground">
                                {customer.email}
                              </span>
                            </div>
                            <div className="flex items-center gap-2 text-sm">
                              <Phone className="h-3 w-3" />
                              <span className="text-muted-foreground">
                                {customer.phone}
                              </span>
                            </div>
                          </div>
                        </TableCell>
                        <TableCell className="text-center">
                          {customer.orders}
                        </TableCell>
                        <TableCell className="font-semibold">
                          ${customer.totalSpent.toFixed(2)}
                        </TableCell>
                        <TableCell>{getStatusBadge(customer.status)}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-1 text-sm text-muted-foreground">
                            <Calendar className="h-3 w-3" />
                            {customer.joinDate}
                          </div>
                        </TableCell>
                        <TableCell className="text-sm text-muted-foreground">
                          {customer.lastOrder}
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
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
};

export default AdminDashboard;
