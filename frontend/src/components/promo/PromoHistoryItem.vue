<script setup lang="ts">
import { computed, nextTick, ref, type ComponentPublicInstance } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { PromoClaim, PromoClaimRejectionReason, PromoClaimStatus } from '@/types/promo'
import { formatDateTime } from '@/utils/date'
import { formatMinorUnits } from '@/utils/money'

interface Props {
  claim: PromoClaim
  /** This record's own revocation is in flight. */
  revoking?: boolean
  /** Another record is being revoked, so this one must wait. */
  disabled?: boolean
  errorMessage?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  revoking: false,
  disabled: false,
  errorMessage: null,
})

const emit = defineEmits<{
  revoke: [claimId: number]
}>()

const STATUS_LABELS: Record<PromoClaimStatus, string> = {
  applied: 'Applied',
  rejected: 'Rejected',
  revoked: 'Revoked',
}

const STATUS_VARIANTS: Record<PromoClaimStatus, 'success' | 'danger' | 'neutral'> = {
  applied: 'success',
  rejected: 'danger',
  revoked: 'neutral',
}

const REJECTION_REASON_LABELS: Record<PromoClaimRejectionReason, string> = {
  promo_code_not_found: 'This promo code does not exist.',
  promo_code_expired: 'This promo code had expired.',
  already_claimed: 'You had already claimed this promo code.',
}

const amount = computed(() =>
  props.claim.bonus_amount === null ? null : formatMinorUnits(props.claim.bonus_amount),
)

const reason = computed(() =>
  props.claim.rejection_reason === null
    ? null
    : REJECTION_REASON_LABELS[props.claim.rejection_reason],
)

// Only a credited bonus can be reversed.
const isRevocable = computed(() => props.claim.status === 'applied')

const confirming = ref(false)
const revokeButton = ref<ComponentPublicInstance | null>(null)
const cancelButton = ref<ComponentPublicInstance | null>(null)

function focus(instance: ComponentPublicInstance | null): void {
  const element = instance?.$el

  if (element instanceof HTMLElement) {
    element.focus()
  }
}

// The button that was activated unmounts as the confirmation opens and closes,
// so focus is moved deliberately; otherwise a keyboard user is dropped back to
// the top of the document.
async function openConfirmation(): Promise<void> {
  confirming.value = true
  await nextTick()
  focus(cancelButton.value)
}

async function cancelConfirmation(): Promise<void> {
  confirming.value = false
  await nextTick()
  focus(revokeButton.value)
}

function confirmRevoke(): void {
  if (props.revoking) {
    return
  }

  emit('revoke', props.claim.id)
}
</script>

<template>
  <li class="claim">
    <div class="claim__row">
      <div class="claim__identity">
        <span class="claim__code">{{ claim.code }}</span>
        <time class="claim__date" :datetime="claim.created_at">
          {{ formatDateTime(claim.created_at) }}
        </time>
      </div>

      <div class="claim__outcome">
        <span v-if="amount !== null" class="claim__amount">{{ amount }}</span>
        <span v-else class="claim__amount claim__amount--none" aria-label="No bonus">&mdash;</span>

        <StatusBadge :variant="STATUS_VARIANTS[claim.status]">
          {{ STATUS_LABELS[claim.status] }}
        </StatusBadge>
      </div>
    </div>

    <p v-if="reason" class="claim__reason">{{ reason }}</p>

    <div v-if="isRevocable" class="claim__actions">
      <BaseButton
        v-if="!confirming"
        ref="revokeButton"
        variant="secondary"
        :disabled="disabled"
        @click="openConfirmation"
      >
        Revoke
      </BaseButton>

      <div
        v-else
        class="claim__confirm"
        role="group"
        :aria-label="`Confirm revoking promo code ${claim.code}`"
      >
        <p class="claim__confirm-text">
          Revoke this bonus? {{ amount }} will be taken back from your balance, and
          the code cannot be claimed again.
        </p>

        <div class="claim__confirm-actions">
          <BaseButton
            ref="cancelButton"
            variant="secondary"
            :disabled="revoking"
            @click="cancelConfirmation"
          >
            Cancel
          </BaseButton>

          <BaseButton :loading="revoking" @click="confirmRevoke">
            Confirm revoke
          </BaseButton>
        </div>
      </div>
    </div>

    <BaseAlert v-if="errorMessage" class="claim__error" variant="error">
      {{ errorMessage }}
    </BaseAlert>
  </li>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.claim {
  padding: $space-2xs 0;
  border-bottom: 1px solid var(--color-border);

  &:last-child {
    border-bottom: 0;
  }

  &__row {
    display: flex;
    flex-direction: column;
    gap: $space-3xs;

    // Side by side once there is room; stacked on a narrow screen.
    @include respond-from('sm') {
      flex-direction: row;
      gap: $space-sm;
      align-items: baseline;
      justify-content: space-between;
    }
  }

  &__identity {
    display: flex;
    flex-direction: column;
    gap: $space-3xs;
    min-width: 0;
  }

  &__code {
    @include label;

    color: var(--color-text);
    word-break: break-all;
  }

  &__date {
    @include caption;

    color: var(--color-text-muted);
  }

  &__outcome {
    display: flex;
    gap: $space-2xs;
    align-items: center;

    @include respond-from('sm') {
      flex-shrink: 0;
    }
  }

  &__amount {
    @include body-sm;

    color: var(--color-text);
    font-variant-numeric: tabular-nums;

    &--none {
      color: var(--color-text-muted);
    }
  }

  &__reason {
    @include caption;

    margin-top: $space-3xs;
    color: var(--color-text-muted);
  }

  &__actions {
    margin-top: $space-2xs;
  }

  &__confirm {
    display: flex;
    flex-direction: column;
    gap: $space-2xs;
    padding: $space-2xs;
    background-color: var(--color-surface-muted);
    border: 1px solid var(--color-border);
    border-radius: $radius-md;
  }

  &__confirm-text {
    @include body-sm;

    color: var(--color-text);
  }

  &__confirm-actions {
    display: flex;
    flex-wrap: wrap;
    gap: $space-2xs;
  }

  &__error {
    margin-top: $space-2xs;
  }
}
</style>
