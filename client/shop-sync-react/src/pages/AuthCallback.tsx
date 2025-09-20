import { useEffect } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { useAppDispatch } from "@/store/hooks";
import { setCredentials } from "@/store/slices/authSlice";
import { authApi } from "@/store/api/authApi";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";

const AuthCallback = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const dispatch = useAppDispatch();

  useEffect(() => {
    const token = searchParams.get("token");
    const error = searchParams.get("error");

    if (error) {
      toast.error("Social login failed. Please try again.");
      navigate("/login", { replace: true });
      return;
    }

    if (token) {
      // Immediately trigger the user fetch using the received token.
      // We use a manual dispatch of the RTK Query thunk.
      const fetchUser = async () => {
        try {
          // Temporarily set the token in localStorage so the API call is authenticated
          localStorage.setItem("auth_token", token);

          const userResponse = await dispatch(
            authApi.endpoints.getCurrentUser.initiate()
          ).unwrap();

          // Now that we have both token and user, set them in the store
          dispatch(setCredentials({ user: userResponse.user, token }));

          toast.success("Successfully logged in!");
          navigate("/", { replace: true });
        } catch (fetchError) {
          console.error(
            "Failed to fetch user data after social login:",
            fetchError
          );
          toast.error("Login failed: Could not verify your details.");
          localStorage.removeItem("auth_token"); // Clean up
          navigate("/login", { replace: true });
        }
      };

      fetchUser();
    } else {
      toast.error("An unexpected error occurred: No token received.");
      navigate("/login", { replace: true });
    }
    // We only want this to run once when the component mounts.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-background text-foreground">
      <Loader2 className="h-8 w-8 animate-spin mb-4" />
      <p className="text-muted-foreground">
        Finalizing your login, please wait...
      </p>
    </div>
  );
};

export default AuthCallback;
