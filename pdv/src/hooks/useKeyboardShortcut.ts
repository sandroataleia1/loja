import { useEffect } from 'react'

interface Options {
  enabled?: boolean
  ctrl?:    boolean
  alt?:     boolean
}

/**
 * Registra um atalho de teclado global.
 * e.preventDefault() é chamado automaticamente.
 */
export function useKeyboardShortcut(
  key: string,
  handler: () => void,
  options: Options = {},
) {
  const { enabled = true, ctrl = false, alt = false } = options

  useEffect(() => {
    if (!enabled) return

    const onKey = (e: KeyboardEvent) => {
      if (ctrl && !e.ctrlKey) return
      if (alt  && !e.altKey)  return
      if (e.key !== key)      return
      e.preventDefault()
      handler()
    }

    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [key, enabled, ctrl, alt, handler])
}
