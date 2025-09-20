import { useGetCategoriesQuery } from "@/store/api/adminApi";
import { useAppSelector } from "@/store/hooks";

export const CategoriesDebug = () => {
  const { isAuthenticated } = useAppSelector((state) => state.auth);
  const { data, isLoading, error } = useGetCategoriesQuery(
    { search: "" },
    { skip: !isAuthenticated }
  );

  return (
    <div className="p-4 bg-blue-50 border border-blue-200 rounded mb-4">
      <h3 className="font-semibold text-blue-800">🔍 Categories Debug</h3>
      <div className="text-sm space-y-1">
        <div>
          <strong>Loading:</strong> {isLoading ? "Yes" : "No"}
        </div>
        <div>
          <strong>Error:</strong> {error ? JSON.stringify(error) : "None"}
        </div>
        <div>
          <strong>Raw Data:</strong> {JSON.stringify(data, null, 2)}
        </div>
        <div>
          <strong>Parsed Categories:</strong>{" "}
          {JSON.stringify(data?.data?.data || data?.data || [], null, 2)}
        </div>
      </div>
    </div>
  );
};
