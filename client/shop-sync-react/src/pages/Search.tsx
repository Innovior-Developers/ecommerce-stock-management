import { useState } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Search as SearchIcon, Filter } from "lucide-react";
import ProductCard from "@/components/ProductCard";
import Header from "@/components/Header";

const Search = () => {
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedFilters, setSelectedFilters] = useState<string[]>([]);

  // Mock search results
  const searchResults = [
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
  ];

  const categories = ["Electronics", "Fashion", "Home & Garden", "Sports", "Books"];
  const priceRanges = ["Under $50", "$50 - $100", "$100 - $200", "Over $200"];

  const handleFilterToggle = (filter: string) => {
    setSelectedFilters(prev =>
      prev.includes(filter)
        ? prev.filter(f => f !== filter)
        : [...prev, filter]
    );
  };

  return (
    <div className="min-h-screen bg-background">
      <Header />
      <div className="container mx-auto px-4 py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-4">Search Products</h1>
          
          {/* Search Bar */}
          <div className="relative mb-6">
            <SearchIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
            <Input
              placeholder="Search for products..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10 pr-4 py-3 text-base"
            />
          </div>

          {/* Filters */}
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <Filter className="h-4 w-4" />
              <span className="font-medium">Filters:</span>
            </div>
            
            <div className="space-y-3">
              <div>
                <h3 className="font-medium mb-2">Categories</h3>
                <div className="flex flex-wrap gap-2">
                  {categories.map((category) => (
                    <Badge
                      key={category}
                      variant={selectedFilters.includes(category) ? "default" : "secondary"}
                      className="cursor-pointer"
                      onClick={() => handleFilterToggle(category)}
                    >
                      {category}
                    </Badge>
                  ))}
                </div>
              </div>
              
              <div>
                <h3 className="font-medium mb-2">Price Range</h3>
                <div className="flex flex-wrap gap-2">
                  {priceRanges.map((range) => (
                    <Badge
                      key={range}
                      variant={selectedFilters.includes(range) ? "default" : "secondary"}
                      className="cursor-pointer"
                      onClick={() => handleFilterToggle(range)}
                    >
                      {range}
                    </Badge>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Search Results */}
        <div className="mb-6">
          <p className="text-muted-foreground">
            Showing {searchResults.length} results {searchQuery && `for "${searchQuery}"`}
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {searchResults.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>

        {/* No Results */}
        {searchResults.length === 0 && (
          <div className="text-center py-16">
            <h2 className="text-2xl font-bold mb-4">No results found</h2>
            <p className="text-muted-foreground mb-8">
              Try adjusting your search or filters to find what you're looking for.
            </p>
            <Button onClick={() => setSearchQuery("")}>Clear Search</Button>
          </div>
        )}
      </div>
    </div>
  );
};

export default Search;