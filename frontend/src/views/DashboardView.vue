<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import PromoClaimForm from '@/components/promo/PromoClaimForm.vue'
import PromoHistoryFilters from '@/components/promo/PromoHistoryFilters.vue'
import PromoHistoryList from '@/components/promo/PromoHistoryList.vue'
import AppHeader from '@/components/ui/AppHeader.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useAuth } from '@/composables/useAuth'
import { toApiError } from '@/services/apiClient'
import {
  claimPromoCode,
  fetchPromoClaimHistory,
  revokePromoClaim,
} from '@/services/promoService'
import type { PaginationMeta } from '@/types/api'
import type { PromoClaim, PromoClaimStatus } from '@/types/promo'
import { formatMinorUnits } from '@/utils/money'

const { player, signOut, updateBalance } = useAuth()

const signingOut = ref(false)

const code = ref('')
const claiming = ref(false)
const claimError = ref<string | null>(null)
const claimFieldErrors = ref<Record<string, string[]>>({})
const claimSuccess = ref<string | null>(null)

const claims = ref<PromoClaim[]>([])
const historyMeta = ref<PaginationMeta | null>(null)
const historyStatus = ref<PromoClaimStatus | null>(null)
const historyLoading = ref(false)
const historyLoadingMore = ref(false)
const historyError = ref<string | null>(null)

const revokingClaimId = ref<number | null>(null)
const revokeErrorClaimId = ref<number | null>(null)
const revokeErrorMessage = ref<string | null>(null)

const hasMoreHistory = computed(
  () => historyMeta.value !== null && historyMeta.value.current_page < historyMeta.value.last_page,
)

/**
 * Loads one backend page. Appending keeps the records already on screen and
 * adds the next page underneath; otherwise the list is replaced.
 */
async function loadHistory(page = 1, append = false): Promise<void> {
  if (append) {
    historyLoadingMore.value = true
  } else {
    historyLoading.value = true
  }

  historyError.value = null

  try {
    const response = await fetchPromoClaimHistory({
      page,
      status: historyStatus.value ?? undefined,
    })

    claims.value = append ? [...claims.value, ...response.data] : response.data
    historyMeta.value = response.meta
  } catch (error) {
    historyError.value = toApiError(error).message

    // A failed append leaves what is already loaded alone.
    if (!append) {
      claims.value = []
      historyMeta.value = null
    }
  } finally {
    historyLoading.value = false
    historyLoadingMore.value = false
  }
}

onMounted(() => {
  void loadHistory()
})

function handleFilterChange(status: PromoClaimStatus | null): void {
  historyStatus.value = status
  // A different filter is a different list, so what was loaded is discarded.
  void loadHistory()
}

function handleLoadMore(): void {
  if (historyLoadingMore.value || !hasMoreHistory.value || historyMeta.value === null) {
    return
  }

  void loadHistory(historyMeta.value.current_page + 1, true)
}

async function handleRevoke(claimId: number): Promise<void> {
  // One revocation at a time, so a second click cannot debit twice.
  if (revokingClaimId.value !== null) {
    return
  }

  revokingClaimId.value = claimId
  revokeErrorClaimId.value = null
  revokeErrorMessage.value = null

  try {
    const result = await revokePromoClaim(claimId)

    updateBalance(result.balance)

    // Reload from the first page so the record shows its new status and loses
    // its revoke button.
    await loadHistory()
  } catch (error) {
    // The backend's own reason is shown against the record it belongs to.
    revokeErrorMessage.value = toApiError(error).message
    revokeErrorClaimId.value = claimId
  } finally {
    revokingClaimId.value = null
  }
}

async function handleSignOut(): Promise<void> {
  signingOut.value = true

  // Always resolves: signing out locally cannot fail.
  await signOut()

  signingOut.value = false
}

async function handleClaim(): Promise<void> {
  // The button is already disabled while a request runs; this also covers a
  // submit arriving from the keyboard.
  if (claiming.value) {
    return
  }

  claiming.value = true
  claimError.value = null
  claimFieldErrors.value = {}
  claimSuccess.value = null

  try {
    const result = await claimPromoCode(code.value)

    updateBalance(result.balance)
    claimSuccess.value = `Bonus of ${formatMinorUnits(result.bonus_amount)} credited.`
    code.value = ''

    // The new record is the newest, so the first page is where it appears.
    // The current filter is kept rather than silently reset.
    void loadHistory()
  } catch (error) {
    const apiError = toApiError(error)

    claimFieldErrors.value = apiError.fieldErrors

    // Validation messages belong on the field; a refused code needs the
    // backend's own reason shown as a general message.
    claimError.value =
      Object.keys(apiError.fieldErrors).length > 0 ? null : apiError.message
  } finally {
    claiming.value = false
  }
}
</script>

<template>
  <div class="dashboard">
    <AppHeader>
      <BaseButton variant="secondary" :loading="signingOut" @click="handleSignOut">
        Sign out
      </BaseButton>
    </AppHeader>

    <main class="dashboard__main">
      <div class="dashboard__grid">
        <!-- Source order is also the single column order on smaller screens:
             balance, claim form, history. -->
        <div class="dashboard__column">
          <section v-if="player" class="dashboard__card" aria-labelledby="balance-heading">
            <h1 id="balance-heading" class="dashboard__title">Welcome back</h1>

            <dl class="session">
              <dt class="session__label">Player</dt>
              <dd class="session__value">{{ player.name }}</dd>

              <dt class="session__label">Balance</dt>
              <dd class="session__value session__value--balance">
                {{ formatMinorUnits(player.balance) }}
              </dd>
            </dl>
          </section>

          <section class="dashboard__card" aria-labelledby="claim-heading">
            <h2 id="claim-heading" class="dashboard__title">Claim a promo bonus</h2>
            <p class="dashboard__subtitle">
              Enter a promo code to credit its bonus to your balance.
            </p>

            <PromoClaimForm
              v-model="code"
              :loading="claiming"
              :error-message="claimError"
              :field-errors="claimFieldErrors"
              :success-message="claimSuccess"
              @submit="handleClaim"
            />
          </section>
        </div>

        <div class="dashboard__column">
          <section class="dashboard__card" aria-labelledby="history-heading">
            <h2 id="history-heading" class="dashboard__title">History</h2>
            <p class="dashboard__subtitle">Your promo code attempts, newest first.</p>

            <PromoHistoryFilters
              :model-value="historyStatus"
              :disabled="historyLoading"
              class="dashboard__filters"
              @update:model-value="handleFilterChange"
            />

            <PromoHistoryList
              :claims="claims"
              :loading="historyLoading"
              :loading-more="historyLoadingMore"
              :has-more="hasMoreHistory"
              :error-message="historyError"
              :revoking-claim-id="revokingClaimId"
              :revoke-error-claim-id="revokeErrorClaimId"
              :revoke-error-message="revokeErrorMessage"
              @retry="loadHistory()"
              @load-more="handleLoadMore"
              @revoke="handleRevoke"
            />
          </section>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.dashboard {
  display: flex;
  flex-direction: column;
  min-height: 100vh;

  &__main {
    display: flex;
    flex: 1;
    justify-content: center;
    padding: $space-sm $space-sm $space-xl;
  }

  &__grid {
    display: grid;
    // One column on phones and tablets; the columns appear only once there is
    // room for a 360px sidebar beside a wider history card.
    grid-template-columns: 1fr;
    gap: $space-sm;
    // Both columns start at the top rather than stretching to match heights.
    align-items: start;
    width: 100%;
    // The same edge as the header, so the page has no stray empty margins.
    max-width: $layout-max-width;

    @include respond-from('lg') {
      grid-template-columns: $layout-sidebar-width minmax(0, 1fr);
    }
  }

  &__column {
    display: flex;
    flex-direction: column;
    gap: $space-sm;
    // Lets a long code wrap instead of stretching the column.
    min-width: 0;
  }

  &__card {
    padding: $space-md $space-sm;
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: $radius-lg;
    box-shadow: var(--shadow-md);

    @include respond-from('sm') {
      padding: $space-lg $space-md;
    }
  }

  &__title {
    margin-bottom: $space-3xs;
  }

  &__subtitle {
    @include body-sm;

    margin-bottom: $space-md;
    color: var(--color-text-muted);
  }

  &__filters {
    margin-bottom: $space-2xs;
  }
}

.session {
  display: grid;
  gap: $space-3xs $space-sm;
  margin-top: $space-sm;

  &__label {
    @include caption;

    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  &__value {
    @include body;

    margin: 0 0 $space-xs;
    color: var(--color-text);

    &--balance {
      @include heading-md;

      margin-bottom: 0;
      font-variant-numeric: tabular-nums;
    }
  }
}
</style>
