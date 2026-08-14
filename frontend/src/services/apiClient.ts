import axios, { type AxiosInstance } from 'axios'
import type { ApiError, ApiErrorResponse } from '@/types/api'

export const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    Accept: 'application/json',
  },
})

/**
 * Attaches the bearer token to every later request, or removes it on sign out.
 */
export function setAuthToken(token: string | null): void {
  if (token === null) {
    delete apiClient.defaults.headers.common.Authorization

    return
  }

  apiClient.defaults.headers.common.Authorization = `Bearer ${token}`
}

/**
 * Normalises anything thrown by a request into one shape, so callers never
 * have to distinguish a backend error from a network failure.
 */
export function toApiError(error: unknown): ApiError {
  if (axios.isAxiosError<ApiErrorResponse>(error)) {
    const data = error.response?.data

    // The backend's own message is preserved rather than replaced, so the
    // player sees the actual reason.
    if (data && typeof data.message === 'string') {
      return {
        code: typeof data.code === 'string' ? data.code : 'UNEXPECTED_ERROR',
        message: data.message,
        fieldErrors: data.errors ?? {},
      }
    }

    return {
      code: 'NETWORK_ERROR',
      message: 'The server could not be reached. Please try again.',
      fieldErrors: {},
    }
  }

  return {
    code: 'UNEXPECTED_ERROR',
    message: 'Something went wrong. Please try again.',
    fieldErrors: {},
  }
}
