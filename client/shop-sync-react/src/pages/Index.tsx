import Header from "@/components/Header";
import HeroSection from "@/components/HeroSection";
import ProductGrid from "@/components/ProductGrid";

const Index = () => {
  return (
    <div className="min-h-screen bg-background">
      <Header />
      <main>
        <HeroSection />
        <ProductGrid />
      </main>
      
      {/* Footer */}
      <footer className="bg-primary text-primary-foreground py-12 mt-16">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <div className="flex items-center space-x-2 mb-4">
                <div className="h-8 w-8 bg-white rounded-lg flex items-center justify-center">
                  <span className="text-primary font-bold text-sm">E</span>
                </div>
                <span className="text-xl font-bold">EliteStore</span>
              </div>
              <p className="text-primary-foreground/80 text-sm">
                Your trusted partner for premium products and exceptional service.
              </p>
            </div>
            
            <div>
              <h4 className="font-semibold mb-4">Quick Links</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="hover:text-accent transition-colors">About Us</a></li>
                <li><a href="#" className="hover:text-accent transition-colors">Contact</a></li>
                <li><a href="#" className="hover:text-accent transition-colors">Shipping</a></li>
                <li><a href="#" className="hover:text-accent transition-colors">Returns</a></li>
              </ul>
            </div>
            
            <div>
              <h4 className="font-semibold mb-4">Categories</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="hover:text-accent transition-colors">Electronics</a></li>
                <li><a href="#" className="hover:text-accent transition-colors">Fashion</a></li>
                <li><a href="#" className="hover:text-accent transition-colors">Home & Garden</a></li>
                <li><a href="#" className="hover:text-accent transition-colors">Sports</a></li>
              </ul>
            </div>
            
            <div>
              <h4 className="font-semibold mb-4">Admin</h4>
              <a 
                href="/admin" 
                className="inline-block bg-accent text-accent-foreground px-4 py-2 rounded-md text-sm font-medium hover:bg-accent-light transition-colors"
              >
                Admin Dashboard
              </a>
            </div>
          </div>
          
          <div className="border-t border-primary-foreground/20 mt-8 pt-6 text-center text-sm text-primary-foreground/60">
            © 2024 EliteStore. All rights reserved.
          </div>
        </div>
      </footer>
    </div>
  );
};

export default Index;
