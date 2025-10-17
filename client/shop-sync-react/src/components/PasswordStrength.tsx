import { useMemo } from "react";
import { Progress } from "@/components/ui/progress";
import { cn } from "@/lib/utils";

interface PasswordStrengthProps {
  password: string;
}

export const PasswordStrength = ({ password }: PasswordStrengthProps) => {
  const strength = useMemo(() => {
    if (!password) return { score: 0, label: "", color: "" };

    let score = 0;
    const checks = {
      length: password.length >= 12,
      uppercase: /[A-Z]/.test(password),
      lowercase: /[a-z]/.test(password),
      numbers: /\d/.test(password),
      symbols: /[!@#$%^&*(),.?":{}|<>]/.test(password),
    };

    // Calculate score
    score += checks.length ? 20 : 0;
    score += checks.uppercase ? 20 : 0;
    score += checks.lowercase ? 20 : 0;
    score += checks.numbers ? 20 : 0;
    score += checks.symbols ? 20 : 0;

    // Determine label and color
    if (score === 100) {
      return { score, label: "Very Strong", color: "bg-green-500", checks };
    } else if (score >= 80) {
      return { score, label: "Strong", color: "bg-blue-500", checks };
    } else if (score >= 60) {
      return { score, label: "Moderate", color: "bg-yellow-500", checks };
    } else if (score >= 40) {
      return { score, label: "Weak", color: "bg-orange-500", checks };
    } else {
      return { score, label: "Very Weak", color: "bg-red-500", checks };
    }
  }, [password]);

  if (!password) return null;

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between text-sm">
        <span className="text-muted-foreground">Password strength:</span>
        <span
          className={cn(
            "font-medium",
            strength.score >= 80
              ? "text-green-600"
              : strength.score >= 60
              ? "text-yellow-600"
              : "text-red-600"
          )}
        >
          {strength.label}
        </span>
      </div>

      <Progress value={strength.score} className={cn("h-2", strength.color)} />

      <div className="space-y-1 text-xs text-muted-foreground">
        <div className="flex items-center gap-2">
          {strength.checks.length ? "✅" : "❌"} At least 12 characters
        </div>
        <div className="flex items-center gap-2">
          {strength.checks.uppercase ? "✅" : "❌"} One uppercase letter
        </div>
        <div className="flex items-center gap-2">
          {strength.checks.lowercase ? "✅" : "❌"} One lowercase letter
        </div>
        <div className="flex items-center gap-2">
          {strength.checks.numbers ? "✅" : "❌"} One number
        </div>
        <div className="flex items-center gap-2">
          {strength.checks.symbols ? "✅" : "❌"} One special character
        </div>
      </div>
    </div>
  );
};
