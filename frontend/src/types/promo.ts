import type { PaginatedResponse } from '@/types/api'

export interface ClaimPromoCodeRequest {
  code: string
}

export interface ClaimPromoCodeResponse {
  /** Credited amount as an integer in minor units. */
  bonus_amount: number
  /** The player's balance after crediting, in minor units. */
  balance: number
}

export type PromoClaimStatus = 'applied' | 'rejected' | 'revoked'

export type PromoClaimRejectionReason =
  | 'promo_code_not_found'
  | 'promo_code_expired'
  | 'already_claimed'

export interface PromoClaim {
  id: number
  code: string
  /** Minor units, or null for an attempt that moved no money. */
  bonus_amount: number | null
  status: PromoClaimStatus
  rejection_reason: PromoClaimRejectionReason | null
  /** ISO 8601, UTC. */
  created_at: string
}

export interface RevokePromoClaimResponse {
  status: PromoClaimStatus
  /** The player's balance after the reversal, in minor units. */
  balance: number
}

export type PromoClaimHistoryResponse = PaginatedResponse<PromoClaim>

export interface PromoClaimHistoryQuery {
  page?: number
  /** Omitted to list every status. */
  status?: PromoClaimStatus
}
