'use client'

import { useQuery } from '@tanstack/react-query'
import { releasesService, type Release } from '@/services/releases.service'

// Versão atual do sistema — atualizar ao fazer deploy de nova versão
export const APP_VERSION = 'v0.1.0'

export function useLatestRelease() {
  return useQuery<Release | null>({
    queryKey:  ['releases', 'latest'],
    queryFn:   () => releasesService.getLatest(),
    staleTime: 60 * 60 * 1000, // 1 hora
    retry:     false,
  })
}

export function useHasUpdate() {
  const { data: release } = useLatestRelease()
  if (!release) return false
  return release.tag_name !== APP_VERSION
}
