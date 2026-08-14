<script setup lang="ts">
import { ref } from 'vue'
import AppHeader from '@/components/ui/AppHeader.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useAuth } from '@/composables/useAuth'
import { formatMinorUnits } from '@/utils/money'

const { player, signOut } = useAuth()

const signingOut = ref(false)

async function handleSignOut(): Promise<void> {
  signingOut.value = true

  // Always resolves: signing out locally cannot fail.
  await signOut()

  signingOut.value = false
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
      <section v-if="player" class="dashboard__card" aria-labelledby="dashboard-heading">
        <h1 id="dashboard-heading" class="dashboard__title">Welcome back</h1>
        <p class="dashboard__subtitle">
          Claiming promo codes and the history follow in the next step.
        </p>

        <dl class="session">
          <dt class="session__label">Player</dt>
          <dd class="session__value">{{ player.name }}</dd>

          <dt class="session__label">Balance</dt>
          <dd class="session__value session__value--balance">
            {{ formatMinorUnits(player.balance) }}
          </dd>
        </dl>
      </section>
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
    align-items: flex-start;
    justify-content: center;
    padding: $space-sm $space-sm $space-xl;
  }

  &__card {
    width: 100%;
    max-width: $layout-form-width;
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
}

.session {
  display: grid;
  gap: $space-3xs $space-sm;

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
