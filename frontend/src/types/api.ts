/**
 * The body the backend returns for every error response: a stable
 * machine-readable code, a human-readable message, and per-field messages when
 * the failure was validation.
 */
export interface ApiErrorResponse {
  code: string
  message: string
  errors?: Record<string, string[]>
}

/**
 * What the UI works with. Transport failures are normalised into this shape
 * too, so a caller never has to tell an axios error from a backend one.
 */
export interface ApiError {
  code: string
  message: string
  fieldErrors: Record<string, string[]>
}
