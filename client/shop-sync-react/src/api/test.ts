import { apiService } from "./Api";

export async function testBackendConnection() {
  try {
    console.log("Testing backend connection...");

    // Test basic health endpoint
    const healthResponse = await apiService.get("/health");
    console.log("Health check:", healthResponse);

    // Test public products endpoint
    const productsResponse = await apiService.get("/products");
    console.log("Public products:", productsResponse);

    return {
      health: healthResponse,
      products: productsResponse,
    };
  } catch (error) {
    console.error("Backend connection test failed:", error);
    throw error;
  }
}
