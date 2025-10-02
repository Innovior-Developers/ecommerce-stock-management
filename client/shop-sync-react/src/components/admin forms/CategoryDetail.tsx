import React from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Folder, Package, Tag, Calendar, Link as LinkIcon } from "lucide-react";

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

interface CategoryDetailProps {
  category: Category | null;
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  categories?: Category[]; // For finding parent category name
}

const CategoryDetail: React.FC<CategoryDetailProps> = ({
  category,
  isOpen,
  onOpenChange,
  categories = [],
}) => {
  if (!category) return null;

  // Find parent category if exists
  const parentCategory = category.parent_id
    ? categories.find((cat) => cat._id === category.parent_id)
    : null;

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[700px] max-h-[85vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-xl flex items-center gap-2">
            <Folder className="h-5 w-5" /> {category.name}
          </DialogTitle>
          <DialogDescription>
            Category ID: {category._id} • Slug: {category.slug}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Category Image */}
          {category.image_url && (
            <Card>
              <CardContent className="pt-6">
                <img
                  src={category.image_url}
                  alt={category.name}
                  className="w-full h-48 object-cover rounded-md border"
                  onError={(e) => {
                    e.currentTarget.src = "/placeholder.svg";
                  }}
                />
              </CardContent>
            </Card>
          )}

          {/* Basic Information */}
          <Card>
            <CardContent className="pt-6">
              <h3 className="text-sm font-medium text-muted-foreground mb-4">
                Basic Information
              </h3>
              <div className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm">Name</span>
                  <span className="font-medium">{category.name}</span>
                </div>

                <div className="flex justify-between items-center">
                  <span className="text-sm">Slug (SEO URL)</span>
                  <div className="flex items-center gap-2">
                    <LinkIcon className="h-3 w-3 text-muted-foreground" />
                    <code className="text-sm bg-muted px-2 py-1 rounded">
                      {category.slug}
                    </code>
                  </div>
                </div>

                <div className="flex justify-between items-center">
                  <span className="text-sm">Status</span>
                  <Badge
                    variant={
                      category.status === "active" ? "default" : "secondary"
                    }
                  >
                    {category.status}
                  </Badge>
                </div>

                <div className="flex justify-between items-center">
                  <span className="text-sm">Sort Order</span>
                  <Badge variant="outline">{category.sort_order}</Badge>
                </div>

                {parentCategory && (
                  <div className="flex justify-between items-center">
                    <span className="text-sm">Parent Category</span>
                    <Badge variant="outline">{parentCategory.name}</Badge>
                  </div>
                )}

                <div className="flex justify-between items-center">
                  <span className="text-sm">Products</span>
                  <div className="flex items-center gap-2">
                    <Package className="h-4 w-4 text-primary" />
                    <span className="font-semibold">
                      {category.products_count || 0} products
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Description */}
          {category.description && (
            <Card>
              <CardContent className="pt-6">
                <h3 className="text-sm font-medium text-muted-foreground mb-2">
                  Description
                </h3>
                <p className="text-sm whitespace-pre-line">
                  {category.description}
                </p>
              </CardContent>
            </Card>
          )}

          {/* SEO Information */}
          <Card>
            <CardContent className="pt-6">
              <h3 className="text-sm font-medium text-muted-foreground mb-4 flex items-center gap-2">
                <Tag className="h-4 w-4" />
                SEO Information
              </h3>
              <div className="space-y-4">
                <div>
                  <h4 className="text-sm font-medium mb-1">Meta Title</h4>
                  <p className="bg-muted p-3 rounded-md text-sm">
                    {category.meta_title || category.name || "Not specified"}
                  </p>
                </div>

                <div>
                  <h4 className="text-sm font-medium mb-1">Meta Description</h4>
                  <p className="bg-muted p-3 rounded-md text-sm whitespace-pre-line">
                    {category.meta_description ||
                      category.description ||
                      "Not specified"}
                  </p>
                </div>

                <Separator />

                <div>
                  <h4 className="text-sm font-medium mb-2">
                    SEO URL Structure
                  </h4>
                  <div className="bg-muted p-3 rounded-md">
                    <code className="text-xs">/category/{category.slug}</code>
                  </div>
                  <p className="text-xs text-muted-foreground mt-2">
                    This URL is optimized for search engines and easy to
                    remember
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Timestamps */}
          <Card>
            <CardContent className="pt-6">
              <h3 className="text-sm font-medium text-muted-foreground mb-4 flex items-center gap-2">
                <Calendar className="h-4 w-4" />
                Timestamps
              </h3>
              <div className="space-y-2">
                <div className="flex justify-between items-center text-sm">
                  <span className="text-muted-foreground">Created</span>
                  <span>{formatDate(category.created_at)}</span>
                </div>
                {category.updated_at && (
                  <div className="flex justify-between items-center text-sm">
                    <span className="text-muted-foreground">Last Updated</span>
                    <span>{formatDate(category.updated_at)}</span>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default CategoryDetail;
