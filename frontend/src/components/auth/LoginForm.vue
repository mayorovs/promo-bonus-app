<script setup lang="ts">
import { computed, ref } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import type { LoginCredentials } from '@/types/auth'

interface Props {
  loading?: boolean
  /** Shown above the fields when the failure was not field specific. */
  errorMessage?: string | null
  /** Per-field messages from a 422 response, keyed by field name. */
  fieldErrors?: Record<string, string[]>
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  errorMessage: null,
  fieldErrors: () => ({}),
})

const emit = defineEmits<{
  submit: [credentials: LoginCredentials]
}>()

const email = ref('')
const password = ref('')

// A practical shape check, not a specification. It spares the player a round
// trip for an obvious typo; the backend's rule stays authoritative.
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

// The message is held back until the player has left the field or tried to
// submit, so it does not appear while the address is still being typed.
const emailBlurred = ref(false)
const submitAttempted = ref(false)

const emailFormatValid = computed(() => EMAIL_PATTERN.test(email.value.trim()))

const showEmailFormatError = computed(
  () =>
    (emailBlurred.value || submitAttempted.value) &&
    email.value.trim() !== '' &&
    !emailFormatValid.value,
)

// Recomputed from the current value, so correcting the address clears it.
// A backend message still shows when there is nothing wrong with the format.
const emailError = computed(() =>
  showEmailFormatError.value
    ? 'Enter a valid email address.'
    : props.fieldErrors.email?.[0],
)

const passwordError = computed(() => props.fieldErrors.password?.[0])

// Only a shortcut for the player; the backend stays the authority on what is
// actually valid.
const canSubmit = computed(
  () => email.value.trim() !== '' && password.value !== '' && !props.loading,
)

function handleSubmit(): void {
  submitAttempted.value = true

  if (!canSubmit.value || !emailFormatValid.value) {
    return
  }

  emit('submit', { email: email.value.trim(), password: password.value })
}
</script>

<template>
  <!-- novalidate silences the browser's own bubbles; the field keeps type
       email for its keyboard and semantics. -->
  <form class="login-form" novalidate @submit.prevent="handleSubmit">
    <BaseAlert v-if="errorMessage" variant="error">{{ errorMessage }}</BaseAlert>

    <!-- focusout rather than blur, because blur does not bubble up to the
         component's root element. -->
    <BaseInput
      v-model="email"
      label="Email"
      type="email"
      autocomplete="email"
      placeholder="player@example.com"
      required
      :disabled="loading"
      :error="emailError"
      @focusout="emailBlurred = true"
    />

    <BaseInput
      v-model="password"
      label="Password"
      type="password"
      autocomplete="current-password"
      required
      :disabled="loading"
      :error="passwordError"
    />

    <BaseButton type="submit" block :loading="loading" :disabled="!canSubmit">
      {{ loading ? 'Signing in' : 'Sign in' }}
    </BaseButton>
  </form>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.login-form {
  display: flex;
  flex-direction: column;
  gap: $space-sm;
}
</style>
