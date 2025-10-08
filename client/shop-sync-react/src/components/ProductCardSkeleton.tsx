import { Card, CardContent, CardFooter } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

const ProductCardSkeleton = () => {
  return (
    <Card className="overflow-hidden border-0 shadow-md">
      {/* Image Skeleton */}
      <Skeleton className="h-48 w-full" />

      <CardContent className="p-4">
        <div className="space-y-2">
          {/* Category */}
          <Skeleton className="h-3 w-20" />

          {/* Title */}
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-3/4" />

          {/* Price */}
          <Skeleton className="h-6 w-24" />

          {/* Stock Status */}
          <div className="flex items-center justify-between pt-2">
            <Skeleton className="h-4 w-16" />
            <Skeleton className="h-4 w-12" />
          </div>
        </div>
      </CardContent>

      <CardFooter className="p-4 pt-0">
        <Skeleton className="h-10 w-full" />
      </CardFooter>
    </Card>
  );
};

export default ProductCardSkeleton;
