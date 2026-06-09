import { useEffect } from 'react'
import { RouterProvider } from 'react-router-dom'
import { router } from '@/routes/router'
import { useSyncStore }    from '@/stores/syncStore'
import { useCatalogStore } from '@/stores/catalogStore'
import { syncEngine } from '@/sync'

export function App() {
  const setSyncState  = useSyncStore((s) => s.setSyncState)
  const loadCatalog   = useCatalogStore((s) => s.load)

  useEffect(() => {
    syncEngine.start(setSyncState)
    return () => syncEngine.stop()
  }, [setSyncState])

  // Carrega catálogo em background no startup — uma vez só
  useEffect(() => {
    loadCatalog()
  }, [loadCatalog])

  return <RouterProvider router={router} />
}
