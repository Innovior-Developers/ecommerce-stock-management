import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Clock, Flame, Tag, Zap } from "lucide-react";
import ProductCard from "@/components/ProductCard";
import Header from "@/components/Header";

const Deals = () => {
  const [activeTab, setActiveTab] = useState("flash");

  const flashDeals = [
    {
      id: 1,
      name: "Premium Wireless Headphones",
      price: 199.99,
      originalPrice: 349.99,
      image: "/src/assets/product-headphones.jpg",
      rating: 4.8,
      stock: 5,
      discount: 43,
      soldCount: 127,
      totalCount: 200,
      timeLeft: "2h 35m",
    },
    {
      id: 2,
      name: "Smart Fitness Watch",
      price: 149.99,
      originalPrice: 299.99,
      image: "/src/assets/product-watch.jpg",
      rating: 4.6,
      stock: 3,
      discount: 50,
      soldCount: 89,
      totalCount: 150,
      timeLeft: "4h 12m",
    },
  ];

  const dailyDeals = [
    {
      id: "3",
      name: "Luxury Leather Bag",
      price: 119.99,
      originalPrice: 159.99,
      image: "/src/assets/product-bag.jpg",
      rating: 4.9,
      stock: 8,
      discount: 25,
      category: "Fashion",
    },
    {
      id: "4",
      name: "Wireless Earbuds Pro",
      price: 129.99,
      originalPrice: 179.99,
      image: "/src/assets/product-headphones.jpg",
      rating: 4.7,
      stock: 12,
      discount: 28,
      category: "Electronics",
    },
  ];

  const categories = [
    { name: "Electronics", discount: "Up to 60% OFF", icon: Zap },
    { name: "Fashion", discount: "Up to 40% OFF", icon: Tag },
    { name: "Home & Garden", discount: "Up to 35% OFF", icon: Flame },
  ];

  return (
    <div className="min-h-screen bg-background">
      <Header />
      {/* Hero Banner */}
      <div className="bg-gradient-to-r from-red-500 to-orange-500 text-white py-16">
        <div className="container mx-auto px-4 text-center">
          <div className="flex items-center justify-center mb-4">
            <Flame className="h-8 w-8 mr-2" />
            <h1 className="text-4xl font-bold">Hot Deals & Offers</h1>
          </div>
          <p className="text-xl mb-8">Don't miss out on these amazing limited-time offers!</p>
          <div className="flex items-center justify-center gap-2">
            <Clock className="h-5 w-5" />
            <span className="text-lg font-medium">Limited Time Only</span>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-8">
        {/* Deal Categories */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
          {categories.map((category, index) => {
            const Icon = category.icon;
            return (
              <Card key={index} className="hover:shadow-lg transition-shadow cursor-pointer">
                <CardContent className="p-6 text-center">
                  <Icon className="h-12 w-12 mx-auto mb-4 text-primary" />
                  <h3 className="text-xl font-bold mb-2">{category.name}</h3>
                  <Badge variant="destructive" className="text-sm">
                    {category.discount}
                  </Badge>
                </CardContent>
              </Card>
            );
          })}
        </div>

        {/* Tabs */}
        <div className="flex justify-center mb-8">
          <div className="flex space-x-1 bg-muted p-1 rounded-lg">
            <Button
              variant={activeTab === "flash" ? "default" : "ghost"}
              onClick={() => setActiveTab("flash")}
              className="px-6"
            >
              <Zap className="h-4 w-4 mr-2" />
              Flash Deals
            </Button>
            <Button
              variant={activeTab === "daily" ? "default" : "ghost"}
              onClick={() => setActiveTab("daily")}
              className="px-6"
            >
              <Tag className="h-4 w-4 mr-2" />
              Daily Deals
            </Button>
          </div>
        </div>

        {/* Flash Deals Tab */}
        {activeTab === "flash" && (
          <div className="space-y-8">
            <div className="text-center mb-8">
              <h2 className="text-3xl font-bold mb-2">⚡ Flash Deals</h2>
              <p className="text-muted-foreground">Limited quantity, limited time!</p>
            </div>
            
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              {flashDeals.map((product) => (
                <Card key={product.id} className="overflow-hidden">
                  <CardContent className="p-0">
                    <div className="grid md:grid-cols-2">
                      <div className="relative">
                        <img
                          src={product.image}
                          alt={product.name}
                          className="w-full h-48 md:h-full object-cover"
                        />
                        <Badge className="absolute top-2 left-2 bg-red-500">
                          -{product.discount}%
                        </Badge>
                      </div>
                      <div className="p-6">
                        <h3 className="text-xl font-bold mb-2">{product.name}</h3>
                        <div className="flex items-center gap-2 mb-4">
                          <span className="text-2xl font-bold text-primary">
                            ${product.price}
                          </span>
                          <span className="text-muted-foreground line-through">
                            ${product.originalPrice}
                          </span>
                        </div>
                        
                        <div className="mb-4">
                          <div className="flex justify-between text-sm mb-2">
                            <span>Sold: {product.soldCount}</span>
                            <span>Available: {product.totalCount - product.soldCount}</span>
                          </div>
                          <Progress 
                            value={(product.soldCount / product.totalCount) * 100} 
                            className="h-2"
                          />
                        </div>
                        
                        <div className="flex items-center justify-between mb-4">
                          <div className="flex items-center text-red-500">
                            <Clock className="h-4 w-4 mr-1" />
                            <span className="font-medium">{product.timeLeft}</span>
                          </div>
                          <span className="text-sm text-muted-foreground">
                            Only {product.stock} left!
                          </span>
                        </div>
                        
                        <Button className="w-full">
                          Buy Now
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        )}

        {/* Daily Deals Tab */}
        {activeTab === "daily" && (
          <div className="space-y-8">
            <div className="text-center mb-8">
              <h2 className="text-3xl font-bold mb-2">🏷️ Daily Deals</h2>
              <p className="text-muted-foreground">Great savings every day</p>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {dailyDeals.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          </div>
        )}

        {/* Newsletter Signup */}
        <Card className="mt-16 bg-gradient-to-r from-primary/5 to-primary/10">
          <CardContent className="p-8 text-center">
            <h3 className="text-2xl font-bold mb-4">Never Miss a Deal!</h3>
            <p className="text-muted-foreground mb-6">
              Subscribe to our newsletter and be the first to know about exclusive offers
            </p>
            <div className="flex max-w-md mx-auto gap-2">
              <input
                type="email"
                placeholder="Enter your email"
                className="flex-1 px-4 py-2 border rounded-md"
              />
              <Button>Subscribe</Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default Deals;