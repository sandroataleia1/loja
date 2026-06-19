import { useRef, useCallback } from 'react'

export function usePrintReceipt() {
  const ref = useRef<HTMLDivElement>(null)

  const print = useCallback(() => {
    if (!ref.current) return
    ref.current.classList.add('receipt-printable')
    window.addEventListener(
      'afterprint',
      () => ref.current?.classList.remove('receipt-printable'),
      { once: true },
    )
    window.print()
  }, [])

  return { ref, print }
}
