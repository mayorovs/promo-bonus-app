<script setup lang="ts">
import { useId } from 'vue'

interface Props {
  label: string
  type?: 'text' | 'email' | 'password'
  autocomplete?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  error?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  autocomplete: undefined,
  placeholder: undefined,
  required: false,
  disabled: false,
  error: undefined,
})

const model = defineModel<string>({ required: true })

// A generated id keeps the label bound to its own input even when several of
// these appear on one page.
const inputId = useId()
const errorId = `${inputId}-error`
</script>

<template>
  <div class="field">
    <label class="field__label" :for="inputId">{{ label }}</label>

    <input
      :id="inputId"
      v-model="model"
      class="field__input"
      :class="{ 'field__input--invalid': Boolean(props.error) }"
      :type="type"
      :autocomplete="autocomplete"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :aria-invalid="props.error ? true : undefined"
      :aria-describedby="props.error ? errorId : undefined"
    />

    <p v-if="props.error" :id="errorId" class="field__error" role="alert">
      {{ props.error }}
    </p>
  </div>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.field {
  display: flex;
  flex-direction: column;
  gap: $space-3xs;

  &__label {
    @include label;

    color: var(--color-text);
  }

  &__input {
    width: 100%;
    padding: $space-2xs $space-xs;
    color: var(--color-text);
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: $radius-md;
    transition:
      border-color $transition-fast,
      background-color $transition-fast;

    &::placeholder {
      color: var(--color-text-muted);
    }

    &:hover:not(:disabled) {
      border-color: var(--color-text-muted);
    }

    &:focus-visible {
      @include focus-ring;

      border-color: var(--color-primary);
    }

    &:disabled {
      background-color: var(--color-surface-muted);
      cursor: not-allowed;
      opacity: 0.7;
    }

    &--invalid {
      border-color: var(--color-danger);
    }
  }

  &__error {
    @include caption;

    color: var(--color-danger);
  }
}
</style>
