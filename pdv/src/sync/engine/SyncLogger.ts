import type { LogLevel, SyncLogEntry } from '../types'

const LEVEL_RANK: Record<LogLevel, number> = { debug: 0, info: 1, warn: 2, error: 3 }

export class SyncLogger {
  constructor(private readonly minLevel: LogLevel = 'info') {}

  log(level: LogLevel, message: string, context?: Record<string, unknown>): void {
    if (LEVEL_RANK[level] < LEVEL_RANK[this.minLevel]) return

    const entry: SyncLogEntry = {
      level,
      message,
      context,
      timestamp: new Date().toISOString(),
    }

    const prefix = `[sync:${level}] ${entry.timestamp}`
    if (level === 'error')     console.error(prefix, message, context ?? '')
    else if (level === 'warn') console.warn(prefix,  message, context ?? '')
    else                       console.log(prefix,   message, context ?? '')
  }

  debug(message: string, context?: Record<string, unknown>): void { this.log('debug', message, context) }
  info (message: string, context?: Record<string, unknown>): void { this.log('info',  message, context) }
  warn (message: string, context?: Record<string, unknown>): void { this.log('warn',  message, context) }
  error(message: string, context?: Record<string, unknown>): void { this.log('error', message, context) }
}
