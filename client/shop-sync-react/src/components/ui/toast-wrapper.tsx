import { Toaster as Sonner } from "sonner";

export function ToastWrapper() {
  return (
    <Sonner
      position="top-right"
      expand={false}
      richColors
      closeButton
      duration={4000}
      toastOptions={{
        style: {
          background: "hsl(var(--background))",
          color: "hsl(var(--foreground))",
          border: "1px solid hsl(var(--border))",
        },
        className: "toast",
      }}
    />
  );
}
