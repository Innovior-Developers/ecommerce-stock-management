import { useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Minus, Plus, Trash2, ShoppingBag } from "lucide-react";
import { Link } from "react-router-dom";
import Header from "@/components/Header";
import { useAppSelector, useAppDispatch } from "@/store/hooks";
import { removeFromCart, updateQuantity } from "@/store/slices/cartSlice";
import { toast } from "sonner";

const Cart = () => {
  const navigate = useNavigate();
  const dispatch = useAppDispatch();

  const { items: cartItems, total } = useAppSelector((state) => state.cart);
  const { isAuthenticated } = useAppSelector((state) => state.auth); // ✅ Get auth state

  const updateItemQuantity = (id: string, newQuantity: number) => {
    if (newQuantity === 0) {
      dispatch(removeFromCart(id));
      toast.success("Item removed from cart");
    } else {
      const item = cartItems.find((item) => item.id === id);
      if (item && newQuantity > item.stock_quantity) {
        toast.error(`Only ${item.stock_quantity} items available in stock`);
        return;
      }
      dispatch(updateQuantity({ id, quantity: newQuantity }));
    }
  };

  const removeItem = (id: string) => {
    dispatch(removeFromCart(id));
    toast.success("Item removed from cart");
  };

  const handleCheckout = () => {
    // ✅ If not authenticated, redirect to login
    if (!isAuthenticated) {
      toast.info("Please login to continue checkout");
      navigate("/login", { state: { from: "/checkout" } });
      return;
    }
    navigate("/checkout");
  };

  const subtotal = total;
  const tax = subtotal * 0.1; // 10% tax
  const shipping = subtotal > 100 ? 0 : 15.99;
  const finalTotal = subtotal + tax + shipping;

  if (cartItems.length === 0) {
    return (
      <div className="min-h-screen bg-background">
        <Header />
        <div className="container mx-auto px-4 py-16">
          <Card className="max-w-md mx-auto text-center p-8">
            <ShoppingBag className="h-16 w-16 mx-auto text-muted-foreground mb-4" />
            <h2 className="text-2xl font-bold mb-2">Your cart is empty</h2>
            <p className="text-muted-foreground mb-8">
              Add some items to get started
            </p>
            <Link to="/shop">
              <Button>Continue Shopping</Button>
            </Link>
          </Card>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <Header />
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-3xl font-bold mb-8">Shopping Cart</h1>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-4">
            {cartItems.map((item) => (
              <Card key={item.id}>
                <CardContent className="p-6">
                  <div className="flex items-center space-x-4">
                    <img
                      src={item.image}
                      alt={item.name}
                      className="w-20 h-20 object-cover rounded-md"
                    />
                    <div className="flex-1">
                      <h3 className="font-semibold">{item.name}</h3>
                      <p className="text-primary font-bold">
                        ${item.price.toFixed(2)}
                      </p>
                      <p className="text-sm text-muted-foreground">
                        In stock: {item.stock_quantity}
                      </p>
                    </div>
                    <div className="flex items-center space-x-2">
                      <Button
                        variant="outline"
                        size="icon"
                        onClick={() =>
                          updateItemQuantity(item.id, item.quantity - 1)
                        }
                      >
                        <Minus className="h-4 w-4" />
                      </Button>
                      <span className="w-12 text-center font-semibold">
                        {item.quantity}
                      </span>
                      <Button
                        variant="outline"
                        size="icon"
                        onClick={() =>
                          updateItemQuantity(item.id, item.quantity + 1)
                        }
                        disabled={item.quantity >= item.stock_quantity}
                      >
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => removeItem(item.id)}
                      className="text-destructive"
                    >
                      <Trash2 className="h-5 w-5" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>

          <div>
            <Card>
              <CardHeader>
                <CardTitle>Order Summary</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex justify-between">
                  <span>Subtotal</span>
                  <span>${subtotal.toFixed(2)}</span>
                </div>
                <div className="flex justify-between">
                  <span>Tax (10%)</span>
                  <span>${tax.toFixed(2)}</span>
                </div>
                <div className="flex justify-between">
                  <span>Shipping</span>
                  <span>
                    {shipping === 0 ? "Free" : `$${shipping.toFixed(2)}`}
                  </span>
                </div>
                {subtotal > 100 && (
                  <p className="text-sm text-green-600">
                    🎉 You've qualified for free shipping!
                  </p>
                )}
                <Separator />
                <div className="flex justify-between font-bold text-lg">
                  <span>Total</span>
                  <span>${finalTotal.toFixed(2)}</span>
                </div>

                {/* ✅ Show login prompt if not authenticated */}
                {!isAuthenticated && (
                  <div className="p-3 bg-muted rounded-md text-sm text-center">
                    Please{" "}
                    <Link to="/login" className="text-primary underline">
                      login
                    </Link>{" "}
                    to checkout
                  </div>
                )}

                <Button className="w-full" onClick={handleCheckout}>
                  {isAuthenticated
                    ? "Proceed to Checkout"
                    : "Login to Checkout"}
                </Button>
                <Link to="/shop">
                  <Button variant="outline" className="w-full">
                    Continue Shopping
                  </Button>
                </Link>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Cart;
