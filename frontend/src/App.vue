<script setup lang="ts">
import { onMounted } from 'vue'
import BaseSpinner from '@/components/ui/BaseSpinner.vue'
import { useAuth } from '@/composables/useAuth'
import DashboardView from '@/views/DashboardView.vue'
import LoginView from '@/views/LoginView.vue'

const { status, restoreSession } = useAuth()

// Nothing is shown until the stored token has been confirmed with the server,
// so a stale token never flashes an authenticated screen.
onMounted(() => {
  void restoreSession()
})
</script>

<template>
  <div v-if="status === 'checking'" class="session-check" role="status">
    <BaseSpinner />
    <p class="session-check__text">Checking your session</p>
  </div>

  <DashboardView v-else-if="status === 'authenticated'" />

  <LoginView v-else />
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.session-check {
  --spinner-size: 2rem;

  display: flex;
  flex-direction: column;
  gap: $space-xs;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  color: var(--color-text-muted);

  &__text {
    @include body-sm;
  }
}
</style>
