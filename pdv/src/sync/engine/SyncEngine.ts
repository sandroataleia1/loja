import { ConnectivityMonitor } from '../connectivity'
import { SyncQueue }           from '../queue'
import { DEFAULT_RETRY_POLICY } from '../retry'
import type { SyncEngineConfig, SyncState } from '../types'
import { SyncWorker }          from '../workers'
import { SyncLogger }          from './SyncLogger'
import { updateSyncStatus, enqueueSync } from '../../db/database'

// Re-export enqueueSync so callers can import it from the sync module
export { enqueueSync }

const DEFAULT_CONFIG: SyncEngineConfig = {
  pollIntervalMs: 30_000,
  batchSize:      10,
  retryPolicy:    DEFAULT_RETRY_POLICY,
  logLevel:       'info',
}

const INITIAL_STATE: SyncState = {
  status:     'idle',
  isOnline:   true,
  pending:    0,
  lastSyncAt: null,
  lastError:  null,
}

type StateChangeHandler = (state: SyncState) => void

export class SyncEngine {
  private static _instance: SyncEngine | null = null

  static getInstance(config?: Partial<SyncEngineConfig>): SyncEngine {
    if (!SyncEngine._instance) {
      SyncEngine._instance = new SyncEngine({ ...DEFAULT_CONFIG, ...config })
    }
    return SyncEngine._instance
  }

  private timer:             ReturnType<typeof setInterval> | null = null
  private unsubscribeConn:   (() => void) | null = null
  private onStateChange:     StateChangeHandler | null = null
  private state:             SyncState = { ...INITIAL_STATE }

  private constructor(
    private readonly config:       SyncEngineConfig,
    private readonly queue:        SyncQueue        = new SyncQueue(),
    private readonly worker:       SyncWorker       = new SyncWorker(),
    private readonly connectivity: ConnectivityMonitor = new ConnectivityMonitor(),
    private readonly logger:       SyncLogger       = new SyncLogger(config.logLevel),
  ) {}

  /** Start polling. Pass a callback to keep the store in sync. */
  start(onStateChange?: StateChangeHandler): void {
    if (this.timer !== null) return

    this.onStateChange = onStateChange ?? null

    // reflect current connectivity immediately
    this.setState({ isOnline: this.connectivity.isOnline })

    // re-trigger sync on reconnect
    this.unsubscribeConn = this.connectivity.subscribe((online) => {
      this.setState({ isOnline: online })
      if (online) void this.triggerSync()
    })

    this.timer = setInterval(() => void this.triggerSync(), this.config.pollIntervalMs)

    this.logger.info('SyncEngine started', { pollIntervalMs: this.config.pollIntervalMs })
    void this.triggerSync()
  }

  stop(): void {
    if (this.timer !== null) {
      clearInterval(this.timer)
      this.timer = null
    }
    this.unsubscribeConn?.()
    this.unsubscribeConn = null
    this.setState({ status: 'idle' })
    void updateSyncStatus({ isSyncing: false })
    this.logger.info('SyncEngine stopped')
  }

  /** Force an immediate sync cycle (no-op if already running or offline). */
  triggerSync(): Promise<void> {
    if (!this.connectivity.isOnline) {
      this.setState({ status: 'offline' })
      return Promise.resolve()
    }
    if (this.state.status === 'running') return Promise.resolve()
    return this.runCycle()
  }

  // ---- private -----------------------------------------------------------

  private async runCycle(): Promise<void> {
    this.setState({ status: 'running', lastError: null })
    this.logger.debug('Sync cycle started')

    // Persist "syncing started" to SQLite sync_status
    void updateSyncStatus({ isSyncing: true, lastError: null })

    try {
      const entries = await this.queue.drain(this.config.batchSize)

      let failedInCycle = 0
      for (const entry of entries) {
        const result = await this.worker.process(entry)

        if (result.ok) {
          await this.queue.markSuccess(entry.uuid)
          this.logger.debug('Entry synced', { uuid: entry.uuid, entity: entry.entity_type })
        } else {
          await this.queue.markFailed(entry.uuid, result.error)
          failedInCycle++
          const level = result.retryable ? 'warn' : 'error'
          this.logger[level]('Entry failed', {
            uuid: entry.uuid, entity: entry.entity_type,
            error: result.error, retryable: result.retryable,
          })
        }
      }

      const pending    = await this.queue.pendingCount()
      const lastSyncAt = new Date().toISOString()
      this.setState({ status: 'idle', pending, lastSyncAt })

      // Persist updated counts and timestamp to SQLite
      void updateSyncStatus({
        isSyncing:   false,
        lastSyncAt,
        lastSyncOk:  failedInCycle === 0,
        pendingCount: pending,
        lastError:   null,
      })

      this.logger.debug('Sync cycle complete', { processed: entries.length, pending })
    } catch (err) {
      const error = err instanceof Error ? err.message : String(err)
      this.setState({ status: 'error', lastError: error })

      // Persist error to SQLite
      void updateSyncStatus({ isSyncing: false, lastSyncOk: false, lastError: error })

      this.logger.error('Sync cycle error', { error })
    }
  }

  private setState(partial: Partial<SyncState>): void {
    this.state = { ...this.state, ...partial }
    this.onStateChange?.(this.state)
  }
}
