'use client'

import { useFeatureFlags } from '@/features/system-settings/hooks'

export function useCatalogFeatures() {
  const { data: flags = [] } = useFeatureFlags()

  const flagMap = flags.reduce<Record<string, boolean>>((acc, f) => {
    acc[f.feature] = f.is_enabled
    return acc
  }, {})

  return {
    hasLotControl:     flagMap['inventory.lot_control'] ?? false,
    hasStorageAddress: flagMap['inventory.address'] ?? false,
  }
}
