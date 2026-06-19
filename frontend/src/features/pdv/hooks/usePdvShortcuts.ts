'use client'

import { useEffect, useCallback } from 'react'

/**
 * F11 → toggle fullscreen from any PDV page.
 * Other shortcuts (F1, F2, F4, F9, F10) are handled within their own components.
 */
export function usePdvF11() {
  const toggle = useCallback(() => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {})
    } else {
      document.exitFullscreen().catch(() => {})
    }
  }, [])

  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.key === 'F11') {
        e.preventDefault()
        toggle()
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [toggle])
}

/**
 * Request fullscreen when mounting; exit when unmounting.
 * Fails silently — fullscreen may be denied by browser policy or user preference.
 */
export function useFullscreen() {
  useEffect(() => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {})
    }
    return () => {
      if (document.fullscreenElement) {
        document.exitFullscreen().catch(() => {})
      }
    }
  }, [])
}
