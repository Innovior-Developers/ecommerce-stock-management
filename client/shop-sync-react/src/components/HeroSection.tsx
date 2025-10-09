import { ArrowRight, Sparkles } from "lucide-react";
import { Button } from "@/components/ui/button";
import heroBanner from "@/assets/hero-banner.jpg";

const HeroSection = () => {
  return (
    <section className="relative overflow-hidden bg-gradient-card">
      <div className="container mx-auto px-4 py-12 lg:py-20">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          {/* Left Content */}
          <div className="space-y-8 animate-fade-in">
            <div className="space-y-4">
              <div className="flex items-center gap-2 text-accent">
                <Sparkles className="h-5 w-5" />
                <span className="text-sm font-medium uppercase tracking-wide">
                  New Collection 2024
                </span>
              </div>

              <h1 className="text-4xl lg:text-6xl font-bold leading-tight">
                Discover Premium
                <span className="block bg-gradient-hero bg-clip-text text-transparent">
                  Products
                </span>
              </h1>

              <p className="text-lg text-muted-foreground max-w-md">
                Shop the finest selection of luxury items with unmatched quality
                and style. From electronics to fashion, we've got you covered.
              </p>
            </div>

            <div className="flex flex-col sm:flex-row gap-4">
              <Button variant="hero" size="xl" className="group">
                Shop Now
                <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1" />
              </Button>

              <Button variant="outline" size="xl">
                View Collections
              </Button>
            </div>

            {/* Stats */}
            <div className="flex gap-8 pt-8">
              <div>
                <div className="text-2xl font-bold text-primary">10K+</div>
                <div className="text-sm text-muted-foreground">
                  Happy Customers
                </div>
              </div>
              <div>
                <div className="text-2xl font-bold text-primary">500+</div>
                <div className="text-sm text-muted-foreground">
                  Premium Products
                </div>
              </div>
              <div>
                <div className="text-2xl font-bold text-primary">24/7</div>
                <div className="text-sm text-muted-foreground">Support</div>
              </div>
            </div>
          </div>

          {/* Right Image */}
          <div className="relative animate-slide-up">
            <div className="relative rounded-2xl overflow-hidden shadow-xl">
              <img
                src={heroBanner}
                alt="Premium products showcase featuring luxury items"
                className="w-full h-[500px] object-cover"
              />

              {/* Overlay gradient */}
              <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent" />

              {/* Floating badge */}
              <div className="absolute top-6 right-6 bg-white/95 backdrop-blur rounded-lg p-3 shadow-lg">
                <div className="text-sm font-semibold text-accent">
                  Free Shipping
                </div>
                <div className="text-xs text-muted-foreground">
                  On orders $100+
                </div>
              </div>
            </div>

            {/* Decorative elements */}
            <div className="absolute -top-4 -right-4 w-20 h-20 bg-accent/10 rounded-full blur-xl" />
            <div className="absolute -bottom-4 -left-4 w-32 h-32 bg-primary/10 rounded-full blur-xl" />
          </div>
        </div>
      </div>
    </section>
  );
};

export default HeroSection;
