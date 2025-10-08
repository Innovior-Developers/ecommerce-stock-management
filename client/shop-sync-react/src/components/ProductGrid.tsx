import { useState, useMemo } from "react";
import { Loader2 } from "lucide-react";
import ProductCard from "./ProductCard";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import { useGetProductsQuery, Product } from "@/store/api/productsApi";
import { useAppDispatch } from "@/store/hooks";
import { addToCart } from "@/store/slices/cartSlice";
import { toast } from "sonner";

interface ProductGridProps {
  itemsPerPage?: number;
  showHeader?: boolean; // ✅ Control header visibility
  showFilters?: boolean; // ✅ Control filters visibility
  title?: string; // ✅ Custom title
  description?: string; // ✅ Custom description
}

const ProductGrid = ({
  itemsPerPage = 8,
  showHeader = true,
  showFilters = true,
  title = "Featured Products",
  description = "Discover our curated collection of premium items",
}: ProductGridProps) => {
  const [selectedCategory, setSelectedCategory] = useState<string>("all");
  const [sortBy, setSortBy] = useState<string>("featured");
  const [currentPage, setCurrentPage] = useState(1);
  const [searchQuery, setSearchQuery] = useState("");

  const dispatch = useAppDispatch();

  // Fetch products from API
  const {
    data: productsResponse,
    isLoading,
    error,
  } = useGetProductsQuery({
    search: searchQuery,
  });

  const products = productsResponse?.data || [];

  // Get unique categories from products
  const categories = useMemo(() => {
    const uniqueCategories = new Set(products.map((p) => p.category));
    return [
      { id: "all", name: "All Products", count: products.length },
      ...Array.from(uniqueCategories).map((cat) => ({
        id: cat.toLowerCase(),
        name: cat,
        count: products.filter((p) => p.category === cat).length,
      })),
    ];
  }, [products]);

  const sortOptions = [
    { value: "featured", label: "Featured" },
    { value: "price-low", label: "Price: Low to High" },
    { value: "price-high", label: "Price: High to Low" },
    { value: "name", label: "Name: A-Z" },
  ];

  // Filter and sort products
  const filteredAndSortedProducts = useMemo(() => {
    let filtered = [...products];

    // Filter by category
    if (selectedCategory !== "all") {
      filtered = filtered.filter(
        (product) => product.category.toLowerCase() === selectedCategory
      );
    }

    // Filter active products only
    filtered = filtered.filter((product) => product.status === "active");

    // Sort products
    filtered.sort((a, b) => {
      const getPrice = (price: number | string | undefined): number => {
        if (typeof price === "string") return parseFloat(price) || 0;
        return price || 0;
      };

      switch (sortBy) {
        case "price-low":
          return getPrice(a.price) - getPrice(b.price);
        case "price-high":
          return getPrice(b.price) - getPrice(a.price);
        case "name":
          return a.name.localeCompare(b.name);
        default:
          return 0;
      }
    });

    return filtered;
  }, [products, selectedCategory, sortBy]);

  // Pagination
  const totalPages = Math.ceil(filteredAndSortedProducts.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const currentProducts = filteredAndSortedProducts.slice(startIndex, endIndex);

  // Reset to first page when filters change
  useMemo(() => {
    setCurrentPage(1);
  }, [selectedCategory, sortBy]);

  const handleAddToCart = (product: Product) => {
    const parsePrice = (price: number | string | undefined): number => {
      if (price === undefined || price === null) return 0;
      const parsed = typeof price === "string" ? parseFloat(price) : price;
      return isNaN(parsed) ? 0 : parsed;
    };

    const productId = product._id || product.id;

    if (!productId) {
      toast.error("Unable to add product to cart");
      console.error("Product missing ID:", product);
      return;
    }

    dispatch(
      addToCart({
        id: productId,
        name: product.name,
        price: parsePrice(product.price),
        quantity: 1,
        image:
          product.images?.[0]?.url ||
          product.image_url ||
          "/assets/default-product.jpg",
        stock_quantity: product.stock_quantity,
      })
    );
    toast.success(`${product.name} added to cart`);
  };

  // Loading state
  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-20">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="text-center py-20">
        <p className="text-red-500">
          Failed to load products. Please try again later.
        </p>
      </div>
    );
  }

  // Empty state
  if (products.length === 0) {
    return (
      <div className="text-center py-20">
        <p className="text-muted-foreground">
          No products available at the moment.
        </p>
      </div>
    );
  }

  return (
    <section className="py-12 bg-background">
      <div className="container mx-auto px-4">
        {/* Header - Conditionally render */}
        {showHeader && (
          <div className="mb-8">
            <h2 className="text-3xl font-bold mb-2">{title}</h2>
            <p className="text-muted-foreground">{description}</p>
          </div>
        )}

        {/* Filters and Controls - Conditionally render */}
        {showFilters && (
          <div className="flex flex-col md:flex-row gap-4 mb-8">
            {/* Category Filter */}
            <div className="flex-1">
              <div className="flex items-center gap-2 flex-wrap">
                {categories.map((category) => (
                  <Button
                    key={category.id}
                    variant={
                      selectedCategory === category.id ? "default" : "outline"
                    }
                    onClick={() => setSelectedCategory(category.id)}
                    className="rounded-full"
                  >
                    {category.name}
                    <Badge variant="secondary" className="ml-2">
                      {category.count}
                    </Badge>
                  </Button>
                ))}
              </div>
            </div>

            {/* Sort Controls */}
            <div className="flex gap-2">
              <Select value={sortBy} onValueChange={setSortBy}>
                <SelectTrigger className="w-[200px]">
                  <SelectValue placeholder="Sort by" />
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
        )}

        {/* Products Grid */}
        {currentProducts.length === 0 ? (
          <Card className="p-12 text-center">
            <p className="text-muted-foreground">
              No products found matching your filters.
            </p>
          </Card>
        ) : (
          <>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              {currentProducts.map((product) => {
                const productKey =
                  product._id || product.id || `product-${product.name}`;

                return (
                  <ProductCard
                    key={productKey}
                    product={product}
                    onAddToCart={handleAddToCart}
                  />
                );
              })}
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="mt-12">
                <Pagination>
                  <PaginationContent>
                    <PaginationItem>
                      <PaginationPrevious
                        onClick={() =>
                          setCurrentPage((p) => Math.max(1, p - 1))
                        }
                        className={
                          currentPage === 1
                            ? "pointer-events-none opacity-50"
                            : ""
                        }
                      />
                    </PaginationItem>

                    {Array.from({ length: totalPages }, (_, i) => i + 1).map(
                      (page) => (
                        <PaginationItem key={page}>
                          <PaginationLink
                            onClick={() => setCurrentPage(page)}
                            isActive={currentPage === page}
                          >
                            {page}
                          </PaginationLink>
                        </PaginationItem>
                      )
                    )}

                    <PaginationItem>
                      <PaginationNext
                        onClick={() =>
                          setCurrentPage((p) => Math.min(totalPages, p + 1))
                        }
                        className={
                          currentPage === totalPages
                            ? "pointer-events-none opacity-50"
                            : ""
                        }
                      />
                    </PaginationItem>
                  </PaginationContent>
                </Pagination>
              </div>
            )}
          </>
        )}
      </div>
    </section>
  );
};

export default ProductGrid;
