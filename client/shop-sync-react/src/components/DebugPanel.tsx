import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { testBackendConnection } from "@/api/test";

export const DebugPanel = () => {
  const [debugInfo, setDebugInfo] = useState<unknown>(null);
  const [isLoading, setIsLoading] = useState(false);

  const runTests = async () => {
    setIsLoading(true);
    try {
      const results = await testBackendConnection();
      setDebugInfo(results);
    } catch (error) {
      setDebugInfo({ error: error.message });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Card className="mb-6">
      <CardHeader>
        <CardTitle>Debug Panel</CardTitle>
      </CardHeader>
      <CardContent>
        <Button onClick={runTests} disabled={isLoading}>
          {isLoading ? "Testing..." : "Test Backend Connection"}
        </Button>

        {debugInfo && (
          <pre className="mt-4 p-4 bg-gray-100 rounded text-xs overflow-auto">
            {JSON.stringify(debugInfo, null, 2)}
          </pre>
        )}
      </CardContent>
    </Card>
  );
};
