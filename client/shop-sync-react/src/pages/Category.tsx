import { useState } from "react";
import { useParams } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from "@/components/ui/breadcrumb";
import ProductCard from "@/components/ProductCard";
import Header from "@/components/Header";

const Category = () => {
  const { categoryName } = useParams();
  const [sortBy, setSortBy] = useState("featured");

  // Mock category data
  const categoryData = {
    electronics: {
      name: "Electronics",
      description: "Cutting-edge technology and gadgets for modern life",
      image: "/src/assets/hero-banner.jpg",
    },
    fashion: {
      name: "Fashion",
      description: "Trendy clothing and accessories for every style",
      image: "/src/assets/product-bag.jpg",
    },
    home: {
      name: "Home & Garden",
      description: "Everything you need for a beautiful home and garden",
      image: "/src/assets/hero-banner.jpg",
    },
  };

  const currentCategory = categoryData[categoryName as keyof typeof categoryData] || {
    name: "Category",
    description: "Explore our collection",
    image: "/src/assets/hero-banner.jpg",
  };

  const categoryProducts = [
    {
      id: "1",
      name: "Premium Wireless Headphones",
      price: 299.99,
      originalPrice: 349.99,
      image: "/src/assets/product-headphones.jpg",
      rating: 4.8,
      stock: 15,
      discount: 14,
      category: "Electronics",
    },
    {
      id: "2",
      name: "Smart Fitness Watch",
      price: 199.99,
      image: "/src/assets/product-watch.jpg",
      rating: 4.6,
      stock: 8,
      category: "Electronics",
    },
    {
      id: "3",
      name: "Luxury Leather Bag",
      price: 159.99,
      image: "/src/assets/product-bag.jpg",
      rating: 4.9,
      stock: 3,
      category: "Fashion",
    },
    {
      id: "4",
      name: "Wireless Earbuds Pro",
      price: 179.99,
      image: "/src/assets/product-headphones.jpg",
      rating: 4.7,
      stock: 12,
      category: "Electronics",
    },
  ];

  const sortOptions = [
    { value: "featured", label: "Featured" },
    { value: "price-low", label: "Price: Low to High" },
    { value: "price-high", label: "Price: High to Low" },
    { value: "newest", label: "Newest First" },
    { value: "rating", label: "Highest Rated" },
  ];

  return (
    <div className="min-h-screen bg-background">
      <Header />
      {/* Category Hero */}
      <div className="relative h-64 bg-gradient-primary">
        <div className="absolute inset-0 bg-black/40" />
        <div className="relative container mx-auto px-4 h-full flex items-center">
          <div className="text-white">
            <Breadcrumb className="mb-4">
              <BreadcrumbList className="text-white/80">
                <BreadcrumbItem>
                  <BreadcrumbLink href="/" className="text-white/80 hover:text-white">
                    Home
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator className="text-white/60" />
                <BreadcrumbItem>
                  <BreadcrumbLink href="/shop" className="text-white/80 hover:text-white">
                    Shop
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator className="text-white/60" />
                <BreadcrumbItem>
                  <BreadcrumbPage className="text-white">
                    {currentCategory.name}
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>
            <h1 className="text-4xl font-bold mb-2">{currentCategory.name}</h1>
            <p className="text-xl text-white/90">{currentCategory.description}</p>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-8">
        {/* Category Info and Sort */}
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
          <div>
            <p className="text-muted-foreground">
              Showing {categoryProducts.length} products in {currentCategory.name}
            </p>
          </div>
          
          <div className="flex items-center gap-2">
            <span className="text-sm font-medium">Sort by:</span>
            <Select value={sortBy} onValueChange={setSortBy}>
              <SelectTrigger className="w-[180px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {sortOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* Products Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
          {categoryProducts.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>

        {/* Load More */}
        <div className="text-center">
          <Button variant="outline" size="lg">
            Load More Products
          </Button>
        </div>
      </div>
    </div>
  );
};

export default Category;