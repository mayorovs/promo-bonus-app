export interface LoginCredentials {
  email: string
  password: string
}

export interface Player {
  id: number
  name: string
  email: string
  /** Money as an integer in minor units, never a float. */
  balance: number
}

export interface LoginResponse {
  token: string
  player: Player
}

export interface CurrentPlayerResponse {
  player: Player
}
