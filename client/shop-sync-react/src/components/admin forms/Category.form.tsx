import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
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
import { Folder, Edit, Trash2, Eye, Package } from "lucide-react";

const categorySchema = z.object({
  name: z.string().min(1, "Name is required").max(255, "Name too long"),
  description: z.string().optional(),
  parent_id: z.string().optional(),
  image_url: z.string().url().optional().or(z.literal("")),
  meta_title: z.string().max(255).optional(),
  meta_description: z.string().optional(),
  status: z.enum(["active", "inactive"]),
  sort_order: z.number().min(0).default(0),
});

type CategoryFormValues = z.infer<typeof categorySchema>;

interface Category {
  _id: string;
  name: string;
  description?: string;
  slug: string;
  status: string;
  sort_order: number;
  parent_id?: string;
  created_at: string;
  updated_at?: string;
  products_count?: number;
  image_url?: string;
  meta_title?: string;
  meta_description?: string;
}

interface CategoryFormProps {
  category?: Category;
  categories?: Category[];
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  onSubmit: (data: CategoryFormValues) => void;
  onUpdate?: (id: string, data: CategoryFormValues) => void;
  isLoading?: boolean;
  mode: "create" | "edit";
}

export const CategoryForm: React.FC<CategoryFormProps> = ({
  category,
  categories = [],
  isOpen,
  onOpenChange,
  onSubmit,
  onUpdate,
  isLoading = false,
  mode,
}) => {
  const form = useForm<CategoryFormValues>({
    resolver: zodResolver(categorySchema),
    defaultValues: {
      name: "",
      description: "",
      parent_id: undefined, // ✅ FIX: Use undefined instead of empty string
      image_url: "",
      meta_title: "",
      meta_description: "",
      status: "active",
      sort_order: 0,
    },
  });

  // Update form when category changes
  useEffect(() => {
    if (isOpen) {
      if (mode === "edit" && category) {
        form.reset({
          name: category.name || "",
          description: category.description || "",
          parent_id: category.parent_id || undefined, // ✅ FIX: undefined instead of ""
          image_url: category.image_url || "",
          meta_title: category.meta_title || "",
          meta_description: category.meta_description || "",
          status: (category.status as "active" | "inactive") || "active",
          sort_order: category.sort_order || 0,
        });
      } else {
        form.reset({
          name: "",
          description: "",
          parent_id: undefined, // ✅ FIX: undefined instead of ""
          image_url: "",
          meta_title: "",
          meta_description: "",
          status: "active",
          sort_order: 0,
        });
      }
    }
  }, [isOpen, category, mode, form]);

  const handleSubmit = (data: CategoryFormValues) => {
    console.log("📝 Category Form Submit:", { mode, data, category });

    if (mode === "edit" && category && onUpdate) {
      console.log("✏️ Updating category:", category._id);
      onUpdate(category._id, data);
    } else {
      console.log("➕ Creating new category");
      onSubmit(data);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[600px] max-h-[85vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {mode === "edit" ? "Edit Category" : "Add New Category"}
          </DialogTitle>
          <DialogDescription>
            {mode === "edit"
              ? "Update the category information below."
              : "Create a new product category for your store."}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-4">
          {/* Basic Information */}
          <div className="space-y-4">
            <h4 className="font-semibold text-sm">Basic Information</h4>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name">Category Name *</Label>
                <Input
                  id="name"
                  placeholder="e.g., Electronics, Clothing"
                  {...form.register("name")}
                />
                {form.formState.errors.name && (
                  <p className="text-sm text-destructive">
                    {form.formState.errors.name.message}
                  </p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="parent_id">Parent Category</Label>
                <Select
                  value={form.watch("parent_id") || "none"} // ✅ FIX: Use "none" as placeholder
                  onValueChange={(value) =>
                    form.setValue(
                      "parent_id",
                      value === "none" ? undefined : value
                    )
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder="No parent (root category)" />
                  </SelectTrigger>
                  <SelectContent>
                    {/* ✅ FIX: Use "none" instead of empty string */}
                    <SelectItem value="none">
                      No Parent (Root Category)
                    </SelectItem>
                    {categories
                      .filter((cat) => cat._id !== category?._id)
                      .filter((cat) => cat.status === "active")
                      .map((cat) => (
                        <SelectItem key={cat._id} value={cat._id}>
                          {cat.name}
                        </SelectItem>
                      ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                placeholder="Brief description of this category"
                rows={3}
                {...form.register("description")}
              />
            </div>
          </div>

          {/* Status & Ordering */}
          <div className="space-y-4">
            <h4 className="font-semibold text-sm">Status & Ordering</h4>

            <div className="grid grid-cols-2 gap-4">
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

              <div className="space-y-2">
                <Label htmlFor="sort_order">Sort Order</Label>
                <Input
                  id="sort_order"
                  type="number"
                  min="0"
                  placeholder="0"
                  {...form.register("sort_order", { valueAsNumber: true })}
                />
                <p className="text-xs text-muted-foreground">
                  Lower numbers appear first
                </p>
              </div>
            </div>
          </div>

          {/* Image */}
          <div className="space-y-2">
            <Label htmlFor="image_url">Category Image URL (Optional)</Label>
            <Input
              id="image_url"
              type="url"
              placeholder="https://example.com/category-image.jpg"
              {...form.register("image_url")}
            />
          </div>

          {/* SEO Fields */}
          <div className="space-y-4">
            <h4 className="font-semibold text-sm">
              SEO Information (Optional)
            </h4>

            <div className="space-y-2">
              <Label htmlFor="meta_title">Meta Title</Label>
              <Input
                id="meta_title"
                placeholder="SEO title for search engines"
                {...form.register("meta_title")}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="meta_description">Meta Description</Label>
              <Textarea
                id="meta_description"
                placeholder="Brief description for search engine results"
                rows={2}
                {...form.register("meta_description")}
              />
            </div>
          </div>

          {/* Actions */}
          <div className="flex justify-end gap-2 pt-4 border-t">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                form.reset();
                onOpenChange(false);
              }}
              disabled={isLoading}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading
                ? "Saving..."
                : mode === "edit"
                ? "Update Category"
                : "Create Category"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};

interface CategoryCardProps {
  category: Category;
  onEdit: (category: Category) => void;
  onDelete: (id: string) => void;
  onView: (category: Category) => void;
}

export const CategoryCard: React.FC<CategoryCardProps> = ({
  category,
  onEdit,
  onDelete,
  onView,
}) => {
  return (
    <Card className="hover:shadow-lg transition-shadow">
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
              <Folder className="h-6 w-6 text-primary" />
            </div>
            <div>
              <CardTitle className="text-base line-clamp-1">
                {category.name}
              </CardTitle>
              <div className="flex items-center gap-2 text-sm text-muted-foreground mt-1">
                <Package className="h-3 w-3" />
                <span>{category.products_count || 0} products</span>
              </div>
            </div>
          </div>
          <Badge
            variant={category.status === "active" ? "default" : "secondary"}
            className="shrink-0"
          >
            {category.status}
          </Badge>
        </div>
      </CardHeader>
      <CardContent className="pt-0">
        <p className="text-sm text-muted-foreground mb-4 line-clamp-2 min-h-[2.5rem]">
          {category.description || "No description provided"}
        </p>

        <div className="flex items-center justify-between pt-3 border-t">
          <span className="text-xs text-muted-foreground">
            Created: {new Date(category.created_at).toLocaleDateString()}
          </span>

          <div className="flex items-center gap-1">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => onView(category)}
              className="h-8 w-8 p-0"
            >
              <Eye className="h-4 w-4" />
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => onEdit(category)}
              className="h-8 w-8 p-0"
            >
              <Edit className="h-4 w-4" />
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => {
                if (
                  window.confirm(
                    `Are you sure you want to delete "${category.name}"? This action cannot be undone.`
                  )
                ) {
                  onDelete(category._id);
                }
              }}
              className="h-8 w-8 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
            >
              <Trash2 className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default CategoryForm;
