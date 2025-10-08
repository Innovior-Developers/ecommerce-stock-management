import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import ProductGrid from "@/components/ProductGrid";
import Header from "@/components/Header";

const Shop = () => {
  const [selectedCategory, setSelectedCategory] = useState<string>("all");
  const [sortBy, setSortBy] = useState<string>("featured");

  const categories = [
    { id: "all", name: "All Products", count: 156 },
    { id: "electronics", name: "Electronics", count: 45 },
    { id: "fashion", name: "Fashion", count: 67 },
    { id: "home", name: "Home & Garden", count: 23 },
    { id: "sports", name: "Sports & Outdoors", count: 21 },
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
      <div className="container mx-auto px-4 py-8">
        {/* Page Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Shop All Products</h1>
          <p className="text-muted-foreground">
            Discover our complete collection of premium products
          </p>
        </div>

        {/* Product Grid with filters */}
        <ProductGrid
          itemsPerPage={12}
          showHeader={false} // ✅ Hide duplicate header
          showFilters={true} // ✅ Show filters and sort
        />
      </div>
    </div>
  );
};

export default Shop;
