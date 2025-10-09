import React from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { DollarSign, Package } from "lucide-react";

interface Product {
  _id: string;
  name: string;
  description: string;
  price: number | string; // ✅ Allow both types
  category: string;
  stock_quantity: number | string; // ✅ Allow both types
  status: string;
  image_url?: string;
  weight?: number | string; // ✅ Allow both types
  meta_title?: string;
  meta_description?: string;
  created_at: string;
  images?: Array<{ url: string; is_primary?: boolean; filename?: string }>;
  sku?: string;
}

interface ProductDetailProps {
  product: Product | null;
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
}

const ProductDetail: React.FC<ProductDetailProps> = ({
  product,
  isOpen,
  onOpenChange,
}) => {
  if (!product) return null;

  // ✅ Helper functions to safely parse numbers
  const parsePrice = (price: number | string | undefined): number => {
    if (price === undefined || price === null) return 0;
    const parsed = typeof price === "string" ? parseFloat(price) : price;
    return isNaN(parsed) ? 0 : parsed;
  };

  const parseQuantity = (quantity: number | string | undefined): number => {
    if (quantity === undefined || quantity === null) return 0;
    const parsed =
      typeof quantity === "string" ? parseInt(quantity, 10) : quantity;
    return isNaN(parsed) ? 0 : parsed;
  };

  const parseWeight = (weight: number | string | undefined): number => {
    if (weight === undefined || weight === null) return 0;
    const parsed = typeof weight === "string" ? parseFloat(weight) : weight;
    return isNaN(parsed) ? 0 : parsed;
  };

  // Get all product images
  const images =
    product.images && product.images.length > 0
      ? product.images
      : product.image_url
      ? [{ url: product.image_url }]
      : [];

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  };

  // ✅ Parse values safely
  const productPrice = parsePrice(product.price);
  const productStock = parseQuantity(product.stock_quantity);
  const productWeight = parseWeight(product.weight);

  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[900px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-xl flex items-center gap-2">
            <Package className="h-5 w-5" /> {product.name}
          </DialogTitle>
          <DialogDescription>Product ID: {product._id}</DialogDescription>
        </DialogHeader>

        <Tabs defaultValue="details" className="w-full">
          <TabsList className="grid grid-cols-3 mb-4">
            <TabsTrigger value="details">Details</TabsTrigger>
            <TabsTrigger value="images">Images ({images.length})</TabsTrigger>
            <TabsTrigger value="meta">Meta & SEO</TabsTrigger>
          </TabsList>

          {/* Details Tab */}
          <TabsContent value="details">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Card>
                <CardContent className="pt-6">
                  <div className="space-y-4">
                    <div>
                      <h3 className="text-sm font-medium text-muted-foreground mb-1">
                        Basic Information
                      </h3>
                      <div className="space-y-2">
                        <div className="flex justify-between items-center">
                          <span className="text-sm">Name</span>
                          <span className="font-medium">{product.name}</span>
                        </div>
                        <div className="flex justify-between items-center">
                          <span className="text-sm">Category</span>
                          <Badge variant="outline">{product.category}</Badge>
                        </div>
                        <div className="flex justify-between items-center">
                          <span className="text-sm">SKU</span>
                          <span className="font-mono text-sm">
                            {product.sku || "N/A"}
                          </span>
                        </div>
                        <div className="flex justify-between items-center">
                          <span className="text-sm">Status</span>
                          <Badge
                            variant={
                              product.status === "active"
                                ? "default"
                                : "secondary"
                            }
                          >
                            {product.status}
                          </Badge>
                        </div>
                      </div>
                    </div>

                    <Separator />

                    <div>
                      <h3 className="text-sm font-medium text-muted-foreground mb-1">
                        Pricing & Inventory
                      </h3>
                      <div className="space-y-2">
                        <div className="flex justify-between items-center">
                          <span className="text-sm">Price</span>
                          <div className="flex items-center gap-1">
                            <DollarSign className="h-4 w-4 text-green-600" />
                            <span className="font-medium">
                              ${productPrice.toFixed(2)}
                            </span>
                          </div>
                        </div>
                        <div className="flex justify-between items-center">
                          <span className="text-sm">Stock</span>
                          <div className="flex items-center gap-2">
                            <div
                              className={`w-2 h-2 rounded-full ${getStockStatusColor(
                                productStock
                              )}`}
                            />
                            <span>{productStock} units</span>
                          </div>
                        </div>
                        <div className="flex justify-between items-center">
                          <span className="text-sm">Weight</span>
                          <span>
                            {productWeight > 0
                              ? `${productWeight.toFixed(2)} kg`
                              : "Not specified"}
                          </span>
                        </div>
                      </div>
                    </div>

                    <Separator />

                    <div>
                      <h3 className="text-sm font-medium text-muted-foreground mb-1">
                        Dates
                      </h3>
                      <div className="space-y-2">
                        <div className="flex justify-between items-center">
                          <span className="text-sm">Created</span>
                          <span>{formatDate(product.created_at)}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <div className="space-y-6">
                <Card>
                  <CardContent className="pt-6">
                    <h3 className="text-sm font-medium text-muted-foreground mb-2">
                      Description
                    </h3>
                    <p className="text-sm whitespace-pre-line">
                      {product.description}
                    </p>
                  </CardContent>
                </Card>

                <Card>
                  <CardContent className="pt-6">
                    <h3 className="text-sm font-medium text-muted-foreground mb-2">
                      Primary Image
                    </h3>
                    {images.length > 0 ? (
                      <img
                        src={images[0].url}
                        alt={product.name}
                        className="rounded-md border w-full h-auto object-contain max-h-[300px]"
                        onError={(e) => {
                          e.currentTarget.src = "/placeholder.svg";
                        }}
                      />
                    ) : (
                      <div className="flex items-center justify-center h-[200px] bg-muted rounded-md border">
                        <Package className="h-16 w-16 text-muted-foreground" />
                      </div>
                    )}
                  </CardContent>
                </Card>
              </div>
            </div>
          </TabsContent>

          {/* Images Tab */}
          <TabsContent value="images">
            <Card>
              <CardContent className="pt-6">
                <h3 className="text-lg font-medium mb-4">Product Images</h3>
                {images.length === 0 ? (
                  <div className="text-center p-8 border rounded-md bg-muted/50">
                    <Package className="h-16 w-16 text-muted-foreground mx-auto mb-4" />
                    <p>No images available for this product</p>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {images.map((image, index) => (
                      <div
                        key={index}
                        className="relative border rounded-md overflow-hidden"
                      >
                        <img
                          src={image.url}
                          alt={`${product.name} - image ${index + 1}`}
                          className="w-full h-[200px] object-cover"
                          onError={(e) => {
                            e.currentTarget.src = "/placeholder.svg";
                          }}
                        />
                        {image.is_primary && (
                          <Badge className="absolute top-2 right-2">
                            Primary
                          </Badge>
                        )}
                        {image.filename && (
                          <div className="p-2 bg-background/80 text-xs truncate absolute bottom-0 left-0 right-0">
                            {image.filename}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Meta & SEO Tab */}
          <TabsContent value="meta">
            <Card>
              <CardContent className="pt-6">
                <h3 className="text-lg font-medium mb-4">SEO Information</h3>
                <div className="space-y-4">
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">
                      Meta Title
                    </h4>
                    <p className="bg-muted p-3 rounded-md mt-1">
                      {product.meta_title || product.name || "Not specified"}
                    </p>
                  </div>

                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground">
                      Meta Description
                    </h4>
                    <p className="bg-muted p-3 rounded-md mt-1 whitespace-pre-line">
                      {product.meta_description || "Not specified"}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </DialogContent>
    </Dialog>
  );
};

// Helper function to determine stock status color
const getStockStatusColor = (quantity: number) => {
  if (quantity <= 0) return "bg-red-500";
  if (quantity <= 5) return "bg-orange-500";
  if (quantity <= 10) return "bg-yellow-500";
  return "bg-green-500";
};

export default ProductDetail;
