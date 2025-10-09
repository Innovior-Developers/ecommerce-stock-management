import { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { toast } from "sonner";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Package,
  Edit,
  Trash2,
  Eye,
  DollarSign,
  X,
  Loader2,
} from "lucide-react"; // ✅ Add Loader2
import {
  useCreateProductMutation,
  useUpdateProductMutation,
} from "../../store/api/adminApi";

// Update the schema to handle images better
const productSchema = z.object({
  name: z.string().min(1, "Name is required").max(255, "Name too long"),
  description: z.string().min(1, "Description is required"),
  price: z.number().min(0, "Price must be non-negative"),
  category: z.string().min(1, "Category is required"),
  stock_quantity: z.number().min(0, "Stock must be non-negative").default(0),
  status: z.enum(["active", "inactive"]).default("active"),
  image_url: z.string().url().optional().or(z.literal("")),
  weight: z.number().min(0).optional(),
  meta_title: z.string().max(255).optional(),
  meta_description: z.string().optional(),
});

type ProductFormValues = z.infer<typeof productSchema>;

interface Product {
  _id?: string; // ✅ Make optional
  id?: string; // ✅ Add this as optional
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
  sku?: string;
  images?: Array<{
    url: string;
    is_primary?: boolean;
    filename?: string;
    path?: string;
  }>;
}

interface Category {
  _id: string;
  name: string;
  status: string;
}

interface ProductFormProps {
  product?: Product;
  categories: Category[];
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  onSubmit: (data: ProductFormValues) => void;
  onUpdate?: (id: string, data: Partial<ProductFormValues>) => void;
  isLoading?: boolean;
  mode: "create" | "edit";
}

export const ProductForm: React.FC<ProductFormProps> = ({
  product,
  categories = [],
  isOpen,
  onOpenChange,
  onSubmit,
  onUpdate,
  isLoading = false,
  mode,
}) => {
  // State for managing images
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const [existingImages, setExistingImages] = useState<
    Array<{ url: string; filename?: string }>
  >(product?.images || []);
  const [imagePreviews, setImagePreviews] = useState<string[]>([]);

  // ✅ Add loading state
  const [isSubmitting, setIsSubmitting] = useState(false);

  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productSchema),
    defaultValues: {
      name: product?.name || "",
      description: product?.description || "",
      price: product?.price || 0,
      category: product?.category || "",
      stock_quantity: product?.stock_quantity || 0,
      status: (product?.status as "active" | "inactive") || "active",
      image_url: product?.image_url || "",
      weight: product?.weight || 0,
      meta_title: product?.meta_title || "",
      meta_description: product?.meta_description || "",
    },
  });

  const [createProduct, { isLoading: isCreating }] = useCreateProductMutation();
  const [updateProduct, { isLoading: isUpdating }] = useUpdateProductMutation();

  // Reset form and images when product changes or modal opens/closes
  useEffect(() => {
    if (isOpen && product && mode === "edit") {
      form.reset({
        name: product.name,
        description: product.description,
        price: product.price,
        category: product.category,
        stock_quantity: product.stock_quantity,
        status: product.status as "active" | "inactive",
        image_url: product.image_url || "",
        weight: product.weight || 0,
        meta_title: product.meta_title || "",
        meta_description: product.meta_description || "",
      });
      setExistingImages(product.images || []);
      setSelectedFiles([]);
      setImagePreviews([]);
    } else if (isOpen && mode === "create") {
      form.reset({
        name: "",
        description: "",
        price: 0,
        category: "",
        stock_quantity: 0,
        status: "active",
        image_url: "",
        weight: 0,
        meta_title: "",
        meta_description: "",
      });
      setExistingImages([]);
      setSelectedFiles([]);
      setImagePreviews([]);
    }
  }, [isOpen, product, mode, form]);

  // Handle file selection
  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (!files) return;

    const newFiles = Array.from(files);
    const totalImages =
      selectedFiles.length + existingImages.length + newFiles.length;

    if (totalImages > 5) {
      toast.error(
        `You can only upload up to 5 images. Currently you have ${
          selectedFiles.length + existingImages.length
        } images.`
      );
      return;
    }

    // Validate file sizes
    const maxFileSize = 10 * 1024 * 1024; // 10MB
    for (const file of newFiles) {
      if (file.size > maxFileSize) {
        toast.error(
          `File "${file.name}" is too large. Maximum size is 10MB per image.`
        );
        return;
      }
    }

    // Create previews
    const newPreviews: string[] = [];
    newFiles.forEach((file) => {
      const reader = new FileReader();
      reader.onloadend = () => {
        newPreviews.push(reader.result as string);
        if (newPreviews.length === newFiles.length) {
          setImagePreviews([...imagePreviews, ...newPreviews]);
        }
      };
      reader.readAsDataURL(file);
    });

    setSelectedFiles([...selectedFiles, ...newFiles]);
    e.target.value = ""; // Reset input
  };

  // Remove selected file
  const removeSelectedFile = (index: number) => {
    const newFiles = selectedFiles.filter((_, i) => i !== index);
    const newPreviews = imagePreviews.filter((_, i) => i !== index);
    setSelectedFiles(newFiles);
    setImagePreviews(newPreviews);
  };

  // Remove existing image
  const removeExistingImage = (index: number) => {
    const newExisting = existingImages.filter((_, i) => i !== index);
    setExistingImages(newExisting);
  };

  const handleSubmit = async (data: ProductFormValues) => {
    console.log("🔍 Form data before processing:", data);
    console.log("🔍 Product object:", product);

    // ✅ Set submitting state
    setIsSubmitting(true);

    try {
      const formData = new FormData();

      // Add form fields
      Object.entries(data).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== "") {
          formData.append(key, String(value));
        }
      });

      // Add new images
      selectedFiles.forEach((file, index) => {
        formData.append(`images[${index}]`, file);
      });

      // For edit mode, send info about existing images to keep
      if (mode === "edit" && existingImages.length > 0) {
        formData.append("existing_images", JSON.stringify(existingImages));
      }

      // Add default values if missing
      if (!formData.has("status")) {
        formData.append("status", "active");
      }
      if (!formData.has("stock_quantity")) {
        formData.append("stock_quantity", "0");
      }

      console.log("📦 FormData contents:");
      for (const [key, value] of formData.entries()) {
        console.log(
          `  ${key}:`,
          value instanceof File ? `File: ${value.name}` : value
        );
      }

      const productId = product?._id || product?.id;

      console.log("🆔 Product ID:", productId);

      if (mode === "edit" && productId && onUpdate) {
        console.log("✏️ Updating product with ID:", productId);

        // ✅ Show loading toast
        const loadingToast = toast.loading("Updating product...");

        try {
          await onUpdate(productId, formData);

          // ✅ Dismiss loading and show success
          toast.dismiss(loadingToast);
          toast.success("Product updated successfully! 🎉", {
            description: `${data.name} has been updated.`,
          });

          // Reset and close
          setSelectedFiles([]);
          setImagePreviews([]);
          setExistingImages([]);
          onOpenChange(false);
          form.reset();
        } catch (error) {
          toast.dismiss(loadingToast);
          toast.error("Failed to update product", {
            description: error?.message || "Please try again.",
          });
        }
      } else if (mode === "edit" && !productId) {
        console.error("❌ Product ID is missing! Cannot update.", product);
        toast.error("Product ID is missing. Cannot update product.");
      } else {
        // ✅ Show loading toast for create
        const loadingToast = toast.loading("Creating product...");

        try {
          await onSubmit(formData);

          toast.dismiss(loadingToast);
          toast.success("Product created successfully! 🎉", {
            description: `${data.name} has been added to your inventory.`,
          });

          // Reset and close
          setSelectedFiles([]);
          setImagePreviews([]);
          setExistingImages([]);
          onOpenChange(false);
          form.reset();
        } catch (error) {
          toast.dismiss(loadingToast);
          toast.error("Failed to create product", {
            description: error?.message || "Please try again.",
          });
        }
      }
    } catch (error) {
      console.error("❌ Form submission error:", error);
      toast.error("Something went wrong", {
        description: "Please check your input and try again.",
      });
    } finally {
      // ✅ Reset submitting state
      setIsSubmitting(false);
    }
  };

  const totalImages = selectedFiles.length + existingImages.length;
  const canAddMore = totalImages < 5;

  // ✅ Combine all loading states
  const isFormLoading = isLoading || isCreating || isUpdating || isSubmitting;

  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {mode === "edit" ? "Edit Product" : "Add New Product"}
          </DialogTitle>
          <DialogDescription>
            {mode === "edit"
              ? "Update the product information."
              : "Add a new product to your store inventory."}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-6">
          {/* Basic Information */}
          <div className="space-y-4">
            <h4 className="font-semibold">Basic Information</h4>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name">Product Name *</Label>
                <Input
                  id="name"
                  placeholder="Enter product name"
                  {...form.register("name")}
                />
                {form.formState.errors.name && (
                  <p className="text-sm text-destructive">
                    {form.formState.errors.name.message}
                  </p>
                )}
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Description *</Label>
              <Textarea
                id="description"
                placeholder="Product description"
                rows={4}
                {...form.register("description")}
              />
              {form.formState.errors.description && (
                <p className="text-sm text-destructive">
                  {form.formState.errors.description.message}
                </p>
              )}
            </div>
          </div>

          {/* Pricing and Inventory */}
          <div className="space-y-4">
            <h4 className="font-semibold">Pricing & Inventory</h4>
            <div className="grid grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label htmlFor="price">Price *</Label>
                <Input
                  id="price"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  {...form.register("price", { valueAsNumber: true })}
                />
                {form.formState.errors.price && (
                  <p className="text-sm text-destructive">
                    {form.formState.errors.price.message}
                  </p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="stock_quantity">Stock Quantity</Label>
                <Input
                  id="stock_quantity"
                  type="number"
                  min="0"
                  placeholder="0"
                  {...form.register("stock_quantity", { valueAsNumber: true })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="weight">Weight (kg)</Label>
                <Input
                  id="weight"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  {...form.register("weight", { valueAsNumber: true })}
                />
              </div>
            </div>
          </div>

          {/* Category and Status */}
          <div className="space-y-4">
            <h4 className="font-semibold">Organization</h4>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="category">Category *</Label>
                <Select
                  value={form.watch("category")}
                  onValueChange={(value) => form.setValue("category", value)}
                >
                  <SelectTrigger>
                    <SelectValue
                      placeholder={
                        categories.length === 0
                          ? "Loading categories..."
                          : "Select category"
                      }
                    />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.length === 0 ? (
                      <SelectItem
                        key="__no_categories" // Add key
                        value="__no_categories"
                        disabled
                        className="text-sm text-muted-foreground"
                      >
                        No categories available. Please create a category first.
                      </SelectItem>
                    ) : (
                      categories
                        .filter((cat) => cat.status === "active")
                        .map((category) => (
                          <SelectItem
                            key={category._id} // This is correct
                            value={category.name}
                          >
                            {category.name}
                          </SelectItem>
                        ))
                    )}
                  </SelectContent>
                </Select>
                {form.formState.errors.category && (
                  <p className="text-sm text-destructive">
                    {form.formState.errors.category.message}
                  </p>
                )}
                {categories.length === 0 && (
                  <p className="text-sm text-muted-foreground">
                    📝 Tip: Create categories first in the Categories tab before
                    adding products.
                  </p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="status">Status</Label>
                <Select
                  value={form.watch("status")}
                  onValueChange={(value) =>
                    form.setValue("status", value as "active" | "inactive")
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="inactive">Inactive</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          {/* Media - Improved Multiple Image Upload */}
          <div className="space-y-4">
            <h4 className="font-semibold">Media</h4>

            {/* Existing Images (Edit Mode) */}
            {mode === "edit" && existingImages.length > 0 && (
              <div className="space-y-2">
                <Label>Current Images ({existingImages.length})</Label>
                <div className="grid grid-cols-5 gap-2">
                  {existingImages.map((image, index) => (
                    <div key={index} className="relative group">
                      <img
                        src={image.url}
                        alt={`Current ${index + 1}`}
                        className="w-full h-20 object-cover rounded border"
                      />
                      <Button
                        type="button"
                        variant="destructive"
                        size="icon"
                        className="absolute top-1 right-1 h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity"
                        onClick={() => removeExistingImage(index)}
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* New Image Previews */}
            {imagePreviews.length > 0 && (
              <div className="space-y-2">
                <Label>New Images ({imagePreviews.length})</Label>
                <div className="grid grid-cols-5 gap-2">
                  {imagePreviews.map((preview, index) => (
                    <div key={index} className="relative group">
                      <img
                        src={preview}
                        alt={`Preview ${index + 1}`}
                        className="w-full h-20 object-cover rounded border"
                      />
                      <Button
                        type="button"
                        variant="destructive"
                        size="icon"
                        className="absolute top-1 right-1 h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity"
                        onClick={() => removeSelectedFile(index)}
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* File Input */}
            <div className="space-y-2">
              <Label htmlFor="images">
                Add Product Images ({totalImages}/5)
              </Label>
              <Input
                id="images"
                type="file"
                multiple
                accept="image/*"
                onChange={handleFileChange}
                disabled={!canAddMore}
                className="cursor-pointer"
              />
              <p className="text-xs text-muted-foreground">
                {canAddMore
                  ? `You can upload ${
                      5 - totalImages
                    } more image(s). Each image max 10MB.`
                  : "Maximum 5 images reached. Remove an image to add more."}
              </p>
            </div>
          </div>

          {/* SEO */}
          <div className="space-y-4">
            <h4 className="font-semibold">SEO Information (Optional)</h4>
            <div className="space-y-2">
              <Label htmlFor="meta_title">Meta Title</Label>
              <Input
                id="meta_title"
                placeholder="SEO title for this product"
                {...form.register("meta_title")}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="meta_description">Meta Description</Label>
              <Textarea
                id="meta_description"
                placeholder="SEO description for this product"
                rows={2}
                {...form.register("meta_description")}
              />
            </div>
          </div>

          <div className="flex justify-end gap-2 pt-4">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setSelectedFiles([]);
                setImagePreviews([]);
                setExistingImages([]);
                onOpenChange(false);
              }}
              disabled={isFormLoading}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={isFormLoading || categories.length === 0}
            >
              {isFormLoading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  {mode === "edit" ? "Updating..." : "Creating..."}
                </>
              ) : categories.length === 0 ? (
                "Create Categories First"
              ) : mode === "edit" ? (
                "Update Product"
              ) : (
                "Create Product"
              )}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};

interface ProductCardProps {
  product: Product;
  onEdit: (product: Product) => void;
  onDelete: (id: string) => void;
  onView: (product: Product) => void;
}

export const ProductCard: React.FC<ProductCardProps> = ({
  product,
  onEdit,
  onDelete,
  onView,
}) => {
  // ✅ Add debug at the top
  useEffect(() => {
    console.log("🎴 ProductCard received product:", {
      _id: product._id,
      id: product.id,
      name: product.name,
    });
  }, [product]);

  const productId = product._id || product.id;

  // ✅ Log the ID that will be used
  console.log("🎴 ProductCard using ID:", productId);

  // Get the primary image URL
  const imageUrl =
    product.images && product.images.length > 0
      ? product.images[0].url
      : product.image_url || "/placeholder.svg";

  // ✅ FIX: Get the correct ID
  const getStockStatusColor = (quantity: number) => {
    if (quantity <= 0) return "bg-red-500";
    if (quantity <= 5) return "bg-orange-500";
    if (quantity <= 10) return "bg-yellow-500";
    return "bg-green-500";
  };

  const getStockStatusText = (quantity: number) => {
    if (quantity <= 0) return "Out of Stock";
    if (quantity <= 5) return "Critical Low";
    if (quantity <= 10) return "Low Stock";
    return "In Stock";
  };

  // ✅ Add debug logging
  console.log(
    "🔍 ProductCard - Product ID:",
    productId,
    "Full product:",
    product
  );

  return (
    <Card className="hover:shadow-lg transition-shadow">
      <CardHeader className="pb-0">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
              <Package className="h-6 w-6 text-primary" />
            </div>
            <div>
              <CardTitle className="text-base line-clamp-1">
                {product.name}
              </CardTitle>
              <p className="text-sm text-muted-foreground">
                SKU: {product.sku || "N/A"}
              </p>
            </div>
          </div>
          <Badge
            variant={product.status === "active" ? "default" : "secondary"}
          >
            {product.status}
          </Badge>
        </div>
      </CardHeader>
      <CardContent className="pt-4">
        {/* Add product image */}
        <div className="mb-4 overflow-hidden rounded-md border">
          <img
            src={imageUrl}
            alt={product.name}
            className="h-48 w-full object-cover"
            onError={(e) => {
              e.currentTarget.src = "/placeholder.svg";
            }}
          />
        </div>

        <div className="space-y-3">
          <p className="text-sm text-muted-foreground line-clamp-2">
            {product.description}
          </p>

          <div className="flex items-center justify-between">
            <div className="flex items-center gap-1">
              <DollarSign className="h-4 w-4 text-green-600" />
              <span className="font-semibold">${product.price}</span>
            </div>
            <div className="flex items-center gap-2">
              <div
                className={`w-2 h-2 rounded-full ${getStockStatusColor(
                  product.stock_quantity
                )}`}
              />
              <span className="text-sm">{product.stock_quantity} units</span>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <Badge variant="outline" className="text-xs">
              {product.category}
            </Badge>
            <Badge
              variant="outline"
              className={`text-xs ${
                product.stock_quantity <= 10
                  ? "border-orange-500 text-orange-600"
                  : ""
              }`}
            >
              {getStockStatusText(product.stock_quantity)}
            </Badge>
          </div>

          <div className="flex items-center justify-between pt-2">
            <span className="text-xs text-muted-foreground">
              Created: {new Date(product.created_at).toLocaleDateString()}
            </span>
            <div className="flex items-center gap-1">
              <Button
                variant="outline"
                size="sm"
                onClick={() => onView(product)}
              >
                <Eye className="h-4 w-4" />
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => onEdit(product)}
              >
                <Edit className="h-4 w-4" />
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  console.log(
                    "🗑️ Delete button clicked, Product ID:",
                    productId
                  );
                  if (!productId) {
                    console.error("❌ Product ID is undefined!", product);
                    alert("Error: Product ID is missing!");
                    return;
                  }
                  onDelete(productId);
                }}
                className="text-destructive hover:bg-destructive hover:text-destructive-foreground"
              >
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default ProductForm;
