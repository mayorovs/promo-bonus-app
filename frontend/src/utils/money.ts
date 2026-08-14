const MINOR_UNITS_PER_MAJOR = 100

/**
 * Formats an integer amount in minor units for display.
 *
 * The value stays an integer everywhere else; this is the only place it is
 * turned into a decimal, and only for the screen.
 */
export function formatMinorUnits(amount: number): string {
  return (amount / MINOR_UNITS_PER_MAJOR).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}
