import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Award, Users, Globe, Heart, Star, Shield } from "lucide-react";
import Header from "@/components/Header";

const About = () => {
  const stats = [
    { icon: Users, label: "Happy Customers", value: "50K+" },
    { icon: Globe, label: "Countries Served", value: "25+" },
    { icon: Award, label: "Years of Excellence", value: "10+" },
    { icon: Star, label: "Average Rating", value: "4.9" },
  ];

  const values = [
    {
      icon: Heart,
      title: "Customer First",
      description: "Every decision we make puts our customers at the center, ensuring the best shopping experience.",
    },
    {
      icon: Shield,
      title: "Quality Assurance",
      description: "We carefully curate every product to meet our high standards of quality and reliability.",
    },
    {
      icon: Globe,
      title: "Global Reach",
      description: "Serving customers worldwide with fast, reliable shipping and local customer support.",
    },
    {
      icon: Award,
      title: "Innovation",
      description: "Constantly evolving our platform to provide cutting-edge shopping experiences.",
    },
  ];

  const team = [
    {
      name: "Sarah Johnson",
      role: "CEO & Founder",
      image: "https://images.unsplash.com/photo-1494790108755-2616c04e8b74?w=300&h=300&fit=crop&crop=face",
      bio: "Visionary leader with 15+ years in e-commerce",
    },
    {
      name: "Michael Chen",
      role: "CTO",
      image: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face",
      bio: "Technology expert passionate about user experience",
    },
    {
      name: "Emily Rodriguez",
      role: "Head of Design",
      image: "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=300&h=300&fit=crop&crop=face",
      bio: "Creative designer focused on beautiful, functional interfaces",
    },
  ];

  return (
    <div className="min-h-screen bg-background">
      <Header />
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary to-primary-dark text-white py-20">
        <div className="container mx-auto px-4 text-center">
          <h1 className="text-5xl font-bold mb-6">About EliteStore</h1>
          <p className="text-xl max-w-3xl mx-auto mb-8">
            We're on a mission to revolutionize online shopping by providing premium products, 
            exceptional service, and an unforgettable customer experience.
          </p>
          <Badge variant="secondary" className="text-lg px-6 py-2">
            Trusted Since 2014
          </Badge>
        </div>
      </div>

      <div className="container mx-auto px-4 py-16">
        {/* Stats Section */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mb-20">
          {stats.map((stat, index) => {
            const Icon = stat.icon;
            return (
              <div key={index} className="text-center">
                <Icon className="h-12 w-12 mx-auto mb-4 text-primary" />
                <div className="text-3xl font-bold text-primary mb-2">{stat.value}</div>
                <div className="text-muted-foreground">{stat.label}</div>
              </div>
            );
          })}
        </div>

        {/* Story Section */}
        <div className="max-w-4xl mx-auto mb-20">
          <h2 className="text-3xl font-bold text-center mb-8">Our Story</h2>
          <div className="prose prose-lg mx-auto text-muted-foreground">
            <p className="text-lg leading-relaxed mb-6">
              Founded in 2014 by a team of passionate entrepreneurs, EliteStore began as a small startup 
              with a big dream: to make premium products accessible to everyone, everywhere. What started 
              in a garage with just three people has grown into a global marketplace serving over 50,000 
              happy customers across 25 countries.
            </p>
            <p className="text-lg leading-relaxed mb-6">
              Our journey hasn't always been easy, but our commitment to quality, innovation, and customer 
              satisfaction has remained unwavering. We believe that shopping should be more than just a 
              transaction – it should be an experience that delights and inspires.
            </p>
            <p className="text-lg leading-relaxed">
              Today, EliteStore continues to push boundaries in e-commerce, leveraging cutting-edge 
              technology and human-centered design to create shopping experiences that our customers love 
              and trust.
            </p>
          </div>
        </div>

        {/* Values Section */}
        <div className="mb-20">
          <h2 className="text-3xl font-bold text-center mb-12">Our Values</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {values.map((value, index) => {
              const Icon = value.icon;
              return (
                <Card key={index} className="text-center hover:shadow-lg transition-shadow">
                  <CardContent className="p-6">
                    <Icon className="h-12 w-12 mx-auto mb-4 text-primary" />
                    <h3 className="text-xl font-bold mb-3">{value.title}</h3>
                    <p className="text-muted-foreground">{value.description}</p>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* Team Section */}
        <div className="mb-20">
          <h2 className="text-3xl font-bold text-center mb-12">Meet Our Team</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {team.map((member, index) => (
              <Card key={index} className="text-center hover:shadow-lg transition-shadow">
                <CardContent className="p-6">
                  <img
                    src={member.image}
                    alt={member.name}
                    className="w-24 h-24 rounded-full mx-auto mb-4 object-cover"
                  />
                  <h3 className="text-xl font-bold mb-2">{member.name}</h3>
                  <Badge variant="secondary" className="mb-3">
                    {member.role}
                  </Badge>
                  <p className="text-muted-foreground">{member.bio}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Mission Section */}
        <Card className="bg-gradient-to-r from-primary/5 to-primary/10">
          <CardContent className="p-12 text-center">
            <h2 className="text-3xl font-bold mb-6">Our Mission</h2>
            <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
              To empower people around the world by providing access to premium products, 
              exceptional service, and innovative shopping experiences that enhance their lives 
              and exceed their expectations.
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default About;