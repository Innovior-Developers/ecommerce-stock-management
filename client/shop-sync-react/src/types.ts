export interface Category {
  _id: string;
  name: string;
  description: string;
  slug: string;
  parent_id?: string | null;
  image_url?: string;
  status: "active" | "inactive";
  meta_title?: string;
  meta_description?: string;
  created_at: string;
  updated_at: string;
}
