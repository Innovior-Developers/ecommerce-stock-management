import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Package, AlertTriangle, Plus, Minus, Edit } from "lucide-react";
import { toast } from "sonner";

const stockUpdateSchema = z.object({
  quantity: z.number().min(0, "Quantity must be non-negative"),
  operation: z.enum(["add", "subtract", "set"]),
  reason: z.string().optional(),
});

type StockUpdateFormValues = z.infer<typeof stockUpdateSchema>;

interface Product {
  _id: string;
  name: string;
  sku: string;
  category: string;
  stock_quantity: number;
  price: number;
  status: string;
}

interface InventoryFormProps {
  product: Product;
  onStockUpdate: (productId: string, data: StockUpdateFormValues) => void;
  isUpdating?: boolean;
}

export const InventoryUpdateForm: React.FC<InventoryFormProps> = ({
  product,
  onStockUpdate,
  isUpdating = false,
}) => {
  const [isOpen, setIsOpen] = useState(false);

  const form = useForm<StockUpdateFormValues>({
    resolver: zodResolver(stockUpdateSchema),
    defaultValues: {
      quantity: 0,
      operation: "add",
      reason: "",
    },
  });

  const onSubmit = (values: StockUpdateFormValues) => {
    onStockUpdate(product._id, values);
    setIsOpen(false);
    form.reset();
    toast.success("Stock update request submitted");
  };

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

  return (
    <Card className="w-full">
      <CardHeader>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
              <Package className="h-6 w-6 text-primary" />
            </div>
            <div>
              <CardTitle className="text-lg">{product.name}</CardTitle>
              <p className="text-sm text-muted-foreground">
                SKU: {product.sku}
              </p>
            </div>
          </div>
          <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
              <Button variant="outline" size="sm">
                <Edit className="h-4 w-4 mr-2" />
                Update Stock
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-[400px]">
              <DialogHeader>
                <DialogTitle>Update Stock</DialogTitle>
                <DialogDescription>
                  Adjust the stock quantity for {product.name}
                </DialogDescription>
              </DialogHeader>
              <form
                onSubmit={form.handleSubmit(onSubmit)}
                className="space-y-4"
              >
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="operation">Operation</Label>
                    <Select
                      value={form.watch("operation")}
                      onValueChange={(value) =>
                        form.setValue(
                          "operation",
                          value as "add" | "subtract" | "set"
                        )
                      }
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select operation" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="add">
                          <div className="flex items-center gap-2">
                            <Plus className="h-4 w-4" />
                            Add Stock
                          </div>
                        </SelectItem>
                        <SelectItem value="subtract">
                          <div className="flex items-center gap-2">
                            <Minus className="h-4 w-4" />
                            Remove Stock
                          </div>
                        </SelectItem>
                        <SelectItem value="set">
                          <div className="flex items-center gap-2">
                            <Edit className="h-4 w-4" />
                            Set Quantity
                          </div>
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="quantity">Quantity</Label>
                    <Input
                      id="quantity"
                      type="number"
                      min="0"
                      placeholder="0"
                      {...form.register("quantity", { valueAsNumber: true })}
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="reason">Reason (Optional)</Label>
                  <Input
                    id="reason"
                    placeholder="e.g., Received new shipment, Damaged goods, etc."
                    {...form.register("reason")}
                  />
                </div>
                <div className="flex justify-end gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => setIsOpen(false)}
                  >
                    Cancel
                  </Button>
                  <Button type="submit" disabled={isUpdating}>
                    {isUpdating ? "Updating..." : "Update Stock"}
                  </Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="space-y-1">
            <p className="text-sm text-muted-foreground">Category</p>
            <p className="font-medium">{product.category}</p>
          </div>
          <div className="space-y-1">
            <p className="text-sm text-muted-foreground">Price</p>
            <p className="font-medium">${product.price}</p>
          </div>
          <div className="space-y-1">
            <p className="text-sm text-muted-foreground">Current Stock</p>
            <div className="flex items-center gap-2">
              <span className="text-2xl font-bold">
                {product.stock_quantity}
              </span>
              <div
                className={`w-3 h-3 rounded-full ${getStockStatusColor(
                  product.stock_quantity
                )}`}
              />
            </div>
          </div>
          <div className="space-y-1">
            <p className="text-sm text-muted-foreground">Status</p>
            <div className="flex flex-col gap-1">
              <Badge
                variant={product.status === "active" ? "default" : "secondary"}
              >
                {product.status}
              </Badge>
              <Badge
                variant="outline"
                className={`text-xs ${
                  product.stock_quantity <= 10
                    ? "border-orange-500 text-orange-600"
                    : "border-green-500 text-green-600"
                }`}
              >
                {getStockStatusText(product.stock_quantity)}
              </Badge>
            </div>
          </div>
        </div>

        {product.stock_quantity <= 10 && (
          <div className="mt-4 p-3 bg-warning/10 border border-warning/20 rounded-lg">
            <div className="flex items-center gap-2">
              <AlertTriangle className="h-4 w-4 text-warning" />
              <span className="text-sm font-medium text-warning">
                {product.stock_quantity <= 5
                  ? "Critical Stock Level - Immediate Action Required"
                  : "Low Stock Level - Consider Restocking Soon"}
              </span>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default InventoryUpdateForm;
