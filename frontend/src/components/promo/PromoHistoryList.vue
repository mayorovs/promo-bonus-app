<script setup lang="ts">
import PromoHistoryItem from '@/components/promo/PromoHistoryItem.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseSpinner from '@/components/ui/BaseSpinner.vue'
import type { PromoClaim } from '@/types/promo'

interface Props {
  claims: PromoClaim[]
  /** The first page is loading, so there is nothing to keep on screen yet. */
  loading?: boolean
  /** A further page is loading; the records already shown stay visible. */
  loadingMore?: boolean
  hasMore?: boolean
  errorMessage?: string | null
  /** The record whose revocation is in flight, if any. */
  revokingClaimId?: number | null
  revokeErrorClaimId?: number | null
  revokeErrorMessage?: string | null
}

withDefaults(defineProps<Props>(), {
  loading: false,
  loadingMore: false,
  hasMore: false,
  errorMessage: null,
  revokingClaimId: null,
  revokeErrorClaimId: null,
  revokeErrorMessage: null,
})

defineEmits<{
  retry: []
  loadMore: []
  revoke: [claimId: number]
}>()
</script>

<template>
  <div class="history-list">
    <div v-if="loading" class="history-list__state" role="status">
      <BaseSpinner />
      <p class="history-list__state-text">Loading history</p>
    </div>

    <!-- A failure with nothing on screen takes over; a failure while appending
         is shown below the records so they are not thrown away. -->
    <div v-else-if="errorMessage && claims.length === 0" class="history-list__state">
      <BaseAlert variant="error">{{ errorMessage }}</BaseAlert>
      <BaseButton variant="secondary" @click="$emit('retry')">Try again</BaseButton>
    </div>

    <p v-else-if="claims.length === 0" class="history-list__empty">
      No promo codes here yet.
    </p>

    <template v-else>
      <ul class="history-list__items" role="list">
        <PromoHistoryItem
          v-for="claim in claims"
          :key="claim.id"
          :claim="claim"
          :revoking="revokingClaimId === claim.id"
          :disabled="revokingClaimId !== null && revokingClaimId !== claim.id"
          :error-message="revokeErrorClaimId === claim.id ? revokeErrorMessage : null"
          @revoke="$emit('revoke', $event)"
        />
      </ul>

      <BaseAlert v-if="errorMessage" class="history-list__more-error" variant="error">
        {{ errorMessage }}
      </BaseAlert>

      <!-- Hidden once the last page has been loaded. -->
      <div v-if="hasMore" class="history-list__more">
        <BaseButton
          variant="secondary"
          :loading="loadingMore"
          @click="$emit('loadMore')"
        >
          Load more
        </BaseButton>
      </div>
    </template>
  </div>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.history-list {
  &__state {
    display: flex;
    flex-direction: column;
    gap: $space-xs;
    align-items: center;
    padding: $space-md 0;
    color: var(--color-text-muted);
  }

  &__state-text {
    @include body-sm;
  }

  &__empty {
    @include body-sm;

    padding: $space-md 0;
    color: var(--color-text-muted);
    text-align: center;
  }

  &__items {
    padding: 0;
    margin: 0;
    list-style: none;
  }

  &__more-error {
    margin-top: $space-sm;
  }

  &__more {
    display: flex;
    justify-content: center;
    margin-top: $space-sm;
  }
}
</style>
