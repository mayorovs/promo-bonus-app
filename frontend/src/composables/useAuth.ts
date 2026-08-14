import { readonly, ref } from 'vue'
import { setAuthToken, toApiError } from '@/services/apiClient'
import {
  clearStoredToken,
  fetchCurrentPlayer,
  login as loginRequest,
  logout as logoutRequest,
  readStoredToken,
} from '@/services/authService'
import type { LoginCredentials, Player } from '@/types/auth'

/**
 * `checking` is the state the application starts in, before the stored token
 * has been confirmed with the server.
 */
export type SessionStatus = 'checking' | 'authenticated' | 'guest'

// Module level, so every component sees the same session.
const player = ref<Player | null>(null)
const status = ref<SessionStatus>('checking')

function forgetSession(): void {
  setAuthToken(null)
  player.value = null
  status.value = 'guest'
}

/**
 * Restores the session on start up: attach any stored token and confirm it
 * with the server before showing anything.
 */
async function restoreSession(): Promise<void> {
  const token = readStoredToken()

  if (token === null) {
    status.value = 'guest'

    return
  }

  setAuthToken(token)

  try {
    player.value = await fetchCurrentPlayer()
    status.value = 'authenticated'
  } catch (error) {
    // A token the server rejects is worthless, so it is discarded. Any other
    // failure, such as the server being unreachable, keeps it: the next start
    // may well succeed.
    if (toApiError(error).code === 'UNAUTHENTICATED') {
      clearStoredToken()
    }

    forgetSession()
  }
}

/**
 * Throws the underlying axios error so the caller can render field level
 * messages; the session is only updated on success.
 */
async function signIn(credentials: LoginCredentials): Promise<void> {
  const session = await loginRequest(credentials)

  player.value = session.player
  status.value = 'authenticated'
}

/**
 * Applies a balance the backend has already recorded, so the visible figure
 * matches after an operation that moved money.
 */
function updateBalance(balance: number): void {
  if (player.value === null) {
    return
  }

  player.value = { ...player.value, balance }
}

async function signOut(): Promise<void> {
  try {
    await logoutRequest()
  } catch {
    // Revoking server side is best effort. The token is discarded locally
    // either way, so a failed request never leaves the player stuck in a
    // session they cannot leave, and there is nothing they could act on.
  } finally {
    forgetSession()
  }
}

export function useAuth() {
  return {
    player: readonly(player),
    status: readonly(status),
    restoreSession,
    signIn,
    signOut,
    updateBalance,
  }
}
