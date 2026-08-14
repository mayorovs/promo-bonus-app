// Types the environment variables this app reads, so a missing or misspelled
// name is a compile error rather than an undefined at runtime.
interface ImportMetaEnv {
  readonly VITE_API_URL: string
}
