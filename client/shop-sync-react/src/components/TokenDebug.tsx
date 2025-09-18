import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export const TokenDebug = () => {
  const token = localStorage.getItem("auth_token");
  const user = localStorage.getItem("user");

  return (
    <Card className="mb-6 border-orange-200">
      <CardHeader>
        <CardTitle className="text-orange-600">Token Debug Info</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-2 text-sm">
          <div>
            <strong>Token exists:</strong> {token ? "✅ Yes" : "❌ No"}
          </div>
          {token && (
            <div>
              <strong>Token preview:</strong> {token.substring(0, 20)}...
            </div>
          )}
          <div>
            <strong>User data:</strong> {user ? "✅ Yes" : "❌ No"}
          </div>
          {user && (
            <div>
              <strong>User role:</strong> {JSON.parse(user)?.role || "Unknown"}
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
};
