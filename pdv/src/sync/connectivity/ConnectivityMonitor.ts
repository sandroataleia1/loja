type ConnectivityListener = (online: boolean) => void

/**
 * Wraps navigator.onLine + browser online/offline events.
 * Consumers subscribe to get notified on state transitions.
 */
export class ConnectivityMonitor {
  private readonly listeners = new Set<ConnectivityListener>()
  private readonly onOnline  = () => this.notify(true)
  private readonly onOffline = () => this.notify(false)

  constructor() {
    window.addEventListener('online',  this.onOnline)
    window.addEventListener('offline', this.onOffline)
  }

  get isOnline(): boolean {
    return navigator.onLine
  }

  /** Returns an unsubscribe function. */
  subscribe(listener: ConnectivityListener): () => void {
    this.listeners.add(listener)
    return () => this.listeners.delete(listener)
  }

  /** Remove event listeners. Call when the engine is stopped permanently. */
  destroy(): void {
    window.removeEventListener('online',  this.onOnline)
    window.removeEventListener('offline', this.onOffline)
    this.listeners.clear()
  }

  private notify(online: boolean): void {
    for (const fn of this.listeners) fn(online)
  }
}
