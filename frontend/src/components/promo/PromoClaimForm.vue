<script setup lang="ts">
import { computed } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

interface Props {
  loading?: boolean
  /** Shown when the failure was not tied to the field itself. */
  errorMessage?: string | null
  /** Per-field messages from a 422 response, keyed by field name. */
  fieldErrors?: Record<string, string[]>
  successMessage?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  errorMessage: null,
  fieldErrors: () => ({}),
  successMessage: null,
})

const emit = defineEmits<{
  submit: []
}>()

const code = defineModel<string>({ required: true })

const codeError = computed(() => props.fieldErrors.code?.[0])

// Only an empty field is held back. Any other code is sent to the backend,
// which stays the validation authority, so its message can be shown against
// the field instead of the submission being silently blocked here.
const canSubmit = computed(() => code.value.trim() !== '' && !props.loading)

function handleSubmit(): void {
  if (!canSubmit.value) {
    return
  }

  emit('submit')
}
</script>

<template>
  <form class="promo-form" @submit.prevent="handleSubmit">
    <BaseAlert v-if="successMessage" variant="success">{{ successMessage }}</BaseAlert>
    <BaseAlert v-if="errorMessage" variant="error">{{ errorMessage }}</BaseAlert>

    <BaseInput
      v-model="code"
      label="Promo code"
      type="text"
      autocomplete="off"
      placeholder="WELCOME50"
      hint="6 to 12 Latin letters or digits."
      required
      :disabled="loading"
      :error="codeError"
    />

    <BaseButton
      class="promo-form__submit"
      type="submit"
      :loading="loading"
      :disabled="!canSubmit"
    >
      {{ loading ? 'Claiming' : 'Claim bonus' }}
    </BaseButton>
  </form>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.promo-form {
  display: flex;
  flex-direction: column;
  gap: $space-sm;

  &__submit {
    // Full width on a narrow screen, only as wide as its label once there is
    // room for it.
    align-self: stretch;

    @include respond-from('sm') {
      align-self: flex-start;
    }
  }
}
</style>
