import axios from 'axios'

// Always cross-origin, always send the session cookie.
export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
})
