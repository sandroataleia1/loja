import type { RetryPolicyConfig } from '../types'

/**
 * HTTP status codes that must NOT be retried (permanent client errors).
 * 429 (Too Many Requests) is intentionally excluded — it is retryable.
 */
export const NON_RETRYABLE_STATUS = new Set([400, 401, 403, 404, 409, 422])

export const DEFAULT_RETRY_POLICY: RetryPolicyConfig = {
  maxAttempts:  5,
  baseDelayMs:  1_000,
  maxDelayMs:   60_000,
  multiplier:   2,
  jitterFactor: 0.15,
}

export function shouldRetry(attempts: number, config: RetryPolicyConfig): boolean {
  return attempts < config.maxAttempts
}

/** Returns false for 4xx client errors that won't succeed on retry. */
export function isRetryableStatus(httpStatus: number): boolean {
  return !NON_RETRYABLE_STATUS.has(httpStatus)
}
