import { apiClient, setAuthToken } from '@/services/apiClient'
import type {
  CurrentPlayerResponse,
  LoginCredentials,
  LoginResponse,
  Player,
} from '@/types/auth'

const TOKEN_STORAGE_KEY = 'promo-bonus-token'

export function readStoredToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_STORAGE_KEY)
  } catch {
    // Storage can be unavailable, for instance in private browsing. The
    // session then lasts only as long as the page.
    return null
  }
}

function storeToken(token: string): void {
  try {
    localStorage.setItem(TOKEN_STORAGE_KEY, token)
  } catch {
    // As above: remembering the token is best effort.
  }
}

export function clearStoredToken(): void {
  try {
    localStorage.removeItem(TOKEN_STORAGE_KEY)
  } catch {
    // As above.
  }
}

/**
 * Signs the player in and keeps the issued token for later requests.
 *
 * Throws the underlying axios error; callers pass it through `toApiError`.
 */
export async function login(credentials: LoginCredentials): Promise<LoginResponse> {
  const { data } = await apiClient.post<LoginResponse>('/login', credentials)

  storeToken(data.token)
  setAuthToken(data.token)

  return data
}

/**
 * Resolves the player behind the currently attached token.
 */
export async function fetchCurrentPlayer(): Promise<Player> {
  const { data } = await apiClient.get<CurrentPlayerResponse>('/me')

  return data.player
}

/**
 * Revokes the token on the server and forgets it locally.
 *
 * The local part runs even when the request fails, so a player is never stuck
 * in a session they cannot leave.
 */
export async function logout(): Promise<void> {
  try {
    await apiClient.post('/logout')
  } finally {
    clearStoredToken()
    setAuthToken(null)
  }
}
