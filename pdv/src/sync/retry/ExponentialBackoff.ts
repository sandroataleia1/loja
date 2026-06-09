import type { RetryPolicyConfig } from '../types'

/**
 * Computes the delay for the next retry attempt using exponential backoff
 * with optional jitter to spread retries across clients.
 *
 * @param attempt  Zero-based attempt index (0 = first retry after initial failure).
 * @param config   Retry policy configuration.
 * @returns        Delay in milliseconds.
 */
export function nextDelayMs(attempt: number, config: RetryPolicyConfig): number {
  const base    = config.baseDelayMs * Math.pow(config.multiplier, attempt)
  const capped  = Math.min(base, config.maxDelayMs)
  // jitter: ±jitterFactor% of capped value
  const jitter  = capped * config.jitterFactor * (Math.random() * 2 - 1)
  return Math.max(0, Math.round(capped + jitter))
}
