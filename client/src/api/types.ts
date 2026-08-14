export interface User {
  id: number
  email: string
  display_name: string | null
  is_admin: boolean
  is_approved: boolean
  created_at: string | null
}

export interface Rsvp {
  id: number
  user: User
  created_at: string | null
}

export interface Block {
  id: number
  starts_at: string
  ends_at: string
  botc_app_code: string | null
  recurring_schedule_id: number | null
  is_past: boolean
  rsvp_count: number
  viewer_has_rsvped?: boolean
  created_at: string | null
  rsvps?: Rsvp[]
}

export interface RecurringSchedule {
  id: number
  day_of_week: number
  start_time: string
  duration_minutes: number
  is_active: boolean
  created_by_user_id: number
  created_at: string | null
}

export interface Paginated<T> {
  data: T[]
  meta: { page: number; per_page: number; total: number }
}
