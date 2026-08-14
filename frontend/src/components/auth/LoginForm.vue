<script setup lang="ts">
import { computed, ref } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import type { LoginCredentials } from '@/types/auth'

interface Props {
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
})

const emit = defineEmits<{
  submit: [credentials: LoginCredentials]
}>()

const email = ref('')
const password = ref('')

// Only a shortcut for the user; the backend stays the authority on what is
// actually valid.
const canSubmit = computed(
  () => email.value.trim() !== '' && password.value !== '' && !props.loading,
)

function handleSubmit(): void {
  if (!canSubmit.value) {
    return
  }

  emit('submit', { email: email.value.trim(), password: password.value })
}
</script>

<template>
  <form class="login-form" @submit.prevent="handleSubmit">
    <BaseInput
      v-model="email"
      label="Email"
      type="email"
      autocomplete="email"
      placeholder="player@example.com"
      required
      :disabled="loading"
    />

    <BaseInput
      v-model="password"
      label="Password"
      type="password"
      autocomplete="current-password"
      required
      :disabled="loading"
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
