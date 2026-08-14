import { computed, ref } from 'vue'

export type Theme = 'light' | 'dark'

// Must match the key used by the inline script in index.html, which applies a
// stored theme before first paint.
const STORAGE_KEY = 'promo-bonus-theme'

function readStoredTheme(): Theme | null {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)

    return stored === 'light' || stored === 'dark' ? stored : null
  } catch {
    // Storage can be unavailable, for instance in private browsing. The toggle
    // still works for this session, the choice just cannot be remembered.
    return null
  }
}

function storeTheme(theme: Theme): void {
  try {
    localStorage.setItem(STORAGE_KEY, theme)
  } catch {
    // As above: remembering the choice is best effort.
  }
}

const darkMediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

// Module level, so every caller shares one source of truth.
const storedTheme = ref<Theme | null>(readStoredTheme())
const systemPrefersDark = ref(darkMediaQuery.matches)

// Keeps the label correct when the operating system switches theme while no
// explicit choice has been made.
darkMediaQuery.addEventListener('change', (event) => {
  systemPrefersDark.value = event.matches
})

const activeTheme = computed<Theme>(() =>
  storedTheme.value ?? (systemPrefersDark.value ? 'dark' : 'light'),
)

function applyTheme(theme: Theme | null): void {
  const root = document.documentElement

  if (theme === null) {
    // Absence of the attribute is what makes the stylesheet fall back to the
    // system preference.
    root.removeAttribute('data-theme')

    return
  }

  root.setAttribute('data-theme', theme)
}

// Brings the document in step with storage on load, in case the inline script
// in index.html did not run.
applyTheme(storedTheme.value)

function setTheme(theme: Theme): void {
  storedTheme.value = theme
  storeTheme(theme)
  applyTheme(theme)
}

function toggleTheme(): void {
  setTheme(activeTheme.value === 'dark' ? 'light' : 'dark')
}

export function useTheme() {
  return { activeTheme, toggleTheme }
}
