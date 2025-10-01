import { ShoppingCart, Eye, Heart } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardFooter } from "@/components/ui/card";

// Update the interface to handle both id formats
interface Product {
  _id?: string;
  id?: string; // ✅ Add this
  name: string;
  description: string;
  price: number;
  category: string;
  stock_quantity: number;
  status: string;
  image_url?: string;
  weight?: number;
  meta_title?: string;
  meta_description?: string;
  created_at: string;
}

interface ProductCardProps {
  product: Product;
  onAddToCart?: (productId: string) => void;
  onQuickView?: (productId: string) => void;
  onToggleFavorite?: (productId: string) => void;
  onEdit?: (product: Product) => void;
  onDelete?: (productId: string) => void;
}

const ProductCard = ({ product, ...props }: ProductCardProps) => {
  // ✅ Corrected and simplified image URL logic
  const imageUrl =
    product.images && product.images.length > 0
      ? product.images[0].url
      : product.image_url || "/assets/default-product.jpg";

  const getStockStatus = () => {
    // The property is stock_quantity, not stock
    if (product.stock_quantity === 0)
      return { status: "out", label: "Out of Stock", className: "stock-out" };
    if (product.stock_quantity <= 5)
      return {
        status: "low",
        label: `${product.stock_quantity} left`,
        className: "stock-low",
      };
    return { status: "in", label: "In Stock", className: "stock-in" };
  };

  const stockInfo = getStockStatus();
  const isOnSale =
    product.originalPrice && product.originalPrice > product.price;

  const productId = product._id || product.id; // ✅ Handle both formats

  return (
    <Card className="product-card group overflow-hidden border-0 shadow-md hover:shadow-lg bg-card">
      <div className="relative overflow-hidden">
        <img
          src={imageUrl}
          alt={product.name}
          className="h-48 w-full object-cover transition-transform duration-300 group-hover:scale-105"
        />

        {/* Overlay badges */}
        <div className="absolute top-2 left-2 flex flex-col gap-1">
          {product.isNew && (
            <Badge variant="secondary" className="bg-info text-info-foreground">
              New
            </Badge>
          )}
          {isOnSale && (
            <Badge
              variant="destructive"
              className="bg-price-sale text-primary-foreground"
            >
              Sale
            </Badge>
          )}
        </div>

        {/* Quick action buttons */}
        <div className="absolute top-2 right-2 flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
          <Button
            size="icon"
            variant="secondary"
            className="h-8 w-8 bg-white/90 hover:bg-white"
            onClick={() => onToggleFavorite?.(product.id)}
          >
            <Heart className="h-4 w-4" />
          </Button>
          <Button
            size="icon"
            variant="secondary"
            className="h-8 w-8 bg-white/90 hover:bg-white"
            onClick={() => onQuickView?.(product.id)}
          >
            <Eye className="h-4 w-4" />
          </Button>
        </div>
      </div>

      <CardContent className="p-4">
        <div className="space-y-2">
          <p className="text-xs text-muted-foreground uppercase tracking-wide">
            {product.category}
          </p>
          <h3 className="font-semibold text-foreground line-clamp-2">
            {product.name}
          </h3>

          <div className="flex items-center gap-2">
            <span className={isOnSale ? "price-sale" : "price-current"}>
              ${product.price.toFixed(2)}
            </span>
            {isOnSale && (
              <span className="price-original">
                ${product.originalPrice?.toFixed(2)}
              </span>
            )}
          </div>

          <div className="flex items-center justify-between">
            <span className={stockInfo.className}>{stockInfo.label}</span>
            {product.rating && (
              <div className="flex items-center gap-1">
                <span className="text-xs text-muted-foreground">
                  ⭐ {product.rating.toFixed(1)}
                </span>
              </div>
            )}
          </div>
        </div>
      </CardContent>

      <CardFooter className="p-4 pt-0">
        <Button
          className="w-full"
          variant={stockInfo.status === "out" ? "outline" : "cart"}
          disabled={stockInfo.status === "out"}
          onClick={() => onAddToCart?.(product.id)}
        >
          <ShoppingCart className="h-4 w-4" />
          {stockInfo.status === "out" ? "Out of Stock" : "Add to Cart"}
        </Button>
      </CardFooter>
    </Card>
  );
};

export default ProductCard;
