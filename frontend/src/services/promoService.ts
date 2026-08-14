import { apiClient } from '@/services/apiClient'
import type {
  ClaimPromoCodeRequest,
  ClaimPromoCodeResponse,
  PromoClaimHistoryQuery,
  PromoClaimHistoryResponse,
  RevokePromoClaimResponse,
} from '@/types/promo'

/**
 * Claims a promo bonus for the authenticated player.
 *
 * Throws the underlying axios error; callers pass it through `toApiError` so
 * the backend's own reason reaches the player.
 */
export async function claimPromoCode(code: string): Promise<ClaimPromoCodeResponse> {
  const payload: ClaimPromoCodeRequest = { code }

  const { data } = await apiClient.post<ClaimPromoCodeResponse>('/promo/claim', payload)

  return data
}

/**
 * Lists the authenticated player's promo attempts, newest first.
 *
 * The page size is fixed by the backend; an omitted status lists every one.
 */
export async function fetchPromoClaimHistory(
  query: PromoClaimHistoryQuery = {},
): Promise<PromoClaimHistoryResponse> {
  const { data } = await apiClient.get<PromoClaimHistoryResponse>('/promo/history', {
    params: {
      page: query.page,
      status: query.status,
    },
  })

  return data
}

/**
 * Reverses a previously applied bonus and returns the resulting balance.
 *
 * Only the player's own applied claim can be revoked; every refusal arrives as
 * an error carrying the backend's own reason.
 */
export async function revokePromoClaim(claimId: number): Promise<RevokePromoClaimResponse> {
  const { data } = await apiClient.patch<RevokePromoClaimResponse>(
    `/promo/${claimId}/revoke`,
  )

  return data
}
