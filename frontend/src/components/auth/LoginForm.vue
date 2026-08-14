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

// Only a shortcut for the player; the backend stays the authority on what is
// actually valid.
const canSubmit = computed(
  () => email.value.trim() !== '' && password.value !== '' && !props.loading,
)

const emailError = computed(() => props.fieldErrors.email?.[0])
const passwordError = computed(() => props.fieldErrors.password?.[0])

function handleSubmit(): void {
  if (!canSubmit.value) {
    return
  }

  emit('submit', { email: email.value.trim(), password: password.value })
}
</script>

<template>
  <form class="login-form" @submit.prevent="handleSubmit">
    <BaseAlert v-if="errorMessage" variant="error">{{ errorMessage }}</BaseAlert>

    <BaseInput
      v-model="email"
      label="Email"
      type="email"
      autocomplete="email"
      placeholder="player@example.com"
      required
      :disabled="loading"
      :error="emailError"
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
