import { useAppSelector } from "@/store/hooks";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { testBackendConnection } from "@/api/test";

export const DebugPanel = () => {
  const auth = useAppSelector((state) => state.auth);
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

  if (process.env.NODE_ENV !== "development") return null;

  return (
    <Card className="fixed bottom-4 right-4 z-50 w-80 max-h-96 overflow-auto">
      <CardHeader>
        <CardTitle className="text-sm">Auth Debug</CardTitle>
      </CardHeader>
      <CardContent className="text-xs">
        <pre>{JSON.stringify(auth, null, 2)}</pre>
      </CardContent>
    </Card>
  );
};
