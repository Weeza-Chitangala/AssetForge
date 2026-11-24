export interface NamedEntity {
  id: string | number | null;
  name: string | null;
  code?: string | null;
}

export interface User {
  id: number;
  name: string;
  email: string;
  employee_number?: string | null;
  job_title?: string | null;
  status: string;
  tenant: { id: string; name?: string | null };
  organization: { id: string | null; name?: string | null };
  company: { id: string | null; name?: string | null };
  department: { id: string | null; name?: string | null };
  team: { id: string | null; name?: string | null };
  roles: string[];
  permissions: string[];
  created_at?: string;
  updated_at?: string;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PaginatedPayload<T> {
  data: T[];
  meta: PaginationMeta;
  links?: Record<string, string | null>;
}

export interface EquipmentAsset {
  id: string;
  asset_tag: string;
  serial_number: string;
  name: string | null;
  category: { id: string | null; name?: string | null; code?: string | null };
  manufacturer: { id: string | null; name?: string | null; code?: string | null };
  model: { id: string | null; name?: string | null; model_number?: string | null };
  location: { id: string | null; name?: string | null; building?: string | null; floor?: string | null };
  status: { id: string | null; name?: string | null; color?: string | null; is_deployable?: boolean | null };
  assigned_to: { id: number | null; name?: string | null; email?: string | null; employee_number?: string | null };
  assigned_date?: string | null;
  specs: {
    operating_system?: string | null;
    processor?: string | null;
    ram?: string | null;
    storage?: string | null;
    mac_address?: string | null;
    ip_address?: string | null;
  };
  procurement: {
    supplier?: string | null;
    order_number?: string | null;
    purchase_date?: string | null;
    purchase_cost?: string | number | null;
    warranty_expiry_date?: string | null;
  };
  condition: string;
  notes?: string | null;
  image_url?: string | null;
  qr_code_url?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface AssetMovement {
  id: string;
  action_type: string;
  movement_date: string | null;
  condition: string | null;
  notes: string | null;
  from: {
    user?: { id: number; name: string } | null;
    location?: { id: string; name: string } | null;
    department?: { id: string; name: string } | null;
    status?: { id: string; name: string } | null;
  };
  to: {
    user?: { id: number; name: string } | null;
    location?: { id: string; name: string } | null;
    department?: { id: string; name: string } | null;
    status?: { id: string; name: string } | null;
  };
  performer: { id: number | null; name?: string | null };
  created_at?: string;
}

export interface LabelData {
  asset_tag: string;
  serial_number: string;
  name: string | null;
  manufacturer: string | null;
  model: string | null;
  category: string | null;
  status: string | null;
  assigned_to: string;
  qr_endpoint: string;
}

export interface DashboardData {
  kpis: {
    total_assets: number;
    total_valuation: string;
    assets_in_repair: number;
    total_repair_spend: string;
    active_warranties: number;
    expiring_warranties: number;
    expired_warranties: number;
  };
  breakdowns: {
    by_status: Array<{ id: string; name: string; color: string; count: number }>;
    by_category: Array<{ id: string; name: string; code: string; count: number }>;
  };
  activity: {
    recent_repairs: Array<{
      id: string;
      job_number: string;
      asset_tag: string | null;
      fault: string;
      status: string;
      created_at: string;
    }>;
    recent_movements: Array<{
      id: string;
      asset_tag: string | null;
      action_type: string;
      performer: string | null;
      notes: string | null;
      created_at: string;
    }>;
  };
}

export interface Warranty {
  id: string;
  asset: {
    id: string;
    asset_tag?: string | null;
    serial_number?: string | null;
    name?: string | null;
  };
  provider_name: string;
  policy_number?: string | null;
  warranty_type?: string | null;
  status: 'active' | 'expiring_soon' | 'expired';
  days_remaining: number;
  start_date: string;
  end_date: string;
  service_level?: string | null;
  support_contact: {
    phone?: string | null;
    email?: string | null;
    portal_url?: string | null;
  };
  oem_lookup_url?: string | null;
  terms_and_conditions?: string | null;
  created_at?: string;
}

export interface RepairUpdate {
  id: string;
  status_from?: string | null;
  status_to: string;
  comments: string;
  author?: { id: number; name: string } | null;
  created_at: string;
}

export interface RepairJob {
  id: string;
  job_number: string;
  repair_status: string;
  repair_center: string;
  vendor_name?: string | null;
  cost?: string | number | null;
  start_date?: string | null;
  completion_date?: string | null;
  fault_description: string;
  diagnostic_notes?: string | null;
  resolution_summary?: string | null;
  asset: {
    id: string;
    asset_tag?: string | null;
    serial_number?: string | null;
    name?: string | null;
  };
  fault_type: {
    id: string | null;
    name?: string | null;
    severity?: string | null;
  };
  technician: {
    id: number | null;
    name?: string | null;
    email?: string | null;
  };
  updates?: RepairUpdate[];
  created_at?: string;
  updated_at?: string;
}

export interface Category {
  id: string;
  name: string;
  code?: string | null;
  description?: string | null;
  status: string;
  created_at?: string;
}

export interface Manufacturer {
  id: string;
  name: string;
  code?: string | null;
  website?: string | null;
  support_phone?: string | null;
  support_email?: string | null;
  status: string;
  created_at?: string;
}

export interface AssetModel {
  id: string;
  name: string;
  model_number?: string | null;
  manufacturer: { id: string; name?: string | null };
  category: { id: string | null; name?: string | null };
  description?: string | null;
  status: string;
  created_at?: string;
}

export interface Location {
  id: string;
  name: string;
  code?: string | null;
  company: { id: string | null; name?: string | null };
  building?: string | null;
  floor?: string | null;
  room?: string | null;
  address?: string | null;
  status: string;
  created_at?: string;
}

export interface Department {
  id: string;
  name: string;
  code: string;
  description?: string | null;
  company: { id: string; name?: string | null };
  status: string;
  created_at?: string;
}

export interface Supplier {
  id: string;
  name: string;
  code?: string | null;
  contact_person?: string | null;
  email?: string | null;
  phone?: string | null;
  website?: string | null;
  address?: string | null;
  status: string;
  created_at?: string;
}

export interface Company {
  id: string;
  organization_id: string;
  organization_name?: string | null;
  name: string;
  code: string;
  status: string;
}

export interface AssetStatus {
  id: string;
  name: string;
  code: string;
  color: string;
  is_deployable: boolean;
  is_archived: boolean;
  description?: string | null;
}

export interface FaultType {
  id: string;
  name: string;
  code?: string | null;
  severity?: string | null;
  description?: string | null;
}

export interface AuditLog {
  id: number;
  log_name: string;
  description: string;
  event: string | null;
  subject_type: string | null;
  subject_id: string | number | null;
  causer: { id: number; name: string; email: string } | null;
  properties: Record<string, unknown>;
  created_at: string;
  timestamp: string;
}

export interface GlobalSearchResponse {
  assets: Array<{
    id: string;
    type: 'asset';
    title: string;
    subtitle: string;
    status?: string;
    assigned_to?: string;
  }>;
  repairs: Array<{
    id: string;
    type: 'repair';
    title: string;
    subtitle: string;
    status?: string;
    asset_tag?: string;
  }>;
  warranties: Array<{
    id: string;
    type: 'warranty';
    title: string;
    subtitle: string;
    asset_tag?: string;
    end_date?: string;
  }>;
  users: Array<{
    id: number;
    type: 'user';
    title: string;
    subtitle: string;
    email?: string;
  }>;
  total: number;
}
