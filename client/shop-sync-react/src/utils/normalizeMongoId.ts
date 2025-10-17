/**
 * Normalizes MongoDB ObjectID format to plain string
 * Handles both {$oid: "..."} and plain string formats
 */
export const normalizeMongoId = (id: unknown): string | undefined => {
  if (!id) return undefined;

  // If it's an object with $oid property
  if (typeof id === "object" && id.$oid) {
    return id.$oid;
  }

  // If it's already a string
  if (typeof id === "string") {
    return id;
  }

  return undefined;
};

/**
 * Recursively normalizes all _id and id fields in an object
 */
export const normalizeMongoObject = <T extends Record<string, unknown>>(
  obj: T
): T => {
  if (!obj || typeof obj !== "object") return obj;

  const normalized = { ...obj };

  // Normalize _id field
  if (normalized._id) {
    normalized._id = normalizeMongoId(normalized._id);
  }

  // Normalize id field
  if (normalized.id) {
    normalized.id = normalizeMongoId(normalized.id);
  }

  // Recursively normalize nested objects
  Object.keys(normalized).forEach((key) => {
    if (Array.isArray(normalized[key])) {
      normalized[key] = normalized[key].map((item: unknown) =>
        typeof item === "object" ? normalizeMongoObject(item) : item
      );
    } else if (normalized[key] && typeof normalized[key] === "object") {
      normalized[key] = normalizeMongoObject(normalized[key]);
    }
  });

  return normalized;
};

/**
 * Normalizes array of MongoDB documents
 */
export const normalizeMongoArray = <T extends Record<string, unknown>>(
  arr: T[]
): T[] => {
  if (!Array.isArray(arr)) return [];
  return arr.map((item) => normalizeMongoObject(item));
};
