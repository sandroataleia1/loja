'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  systemSettingsService,
  type SystemSettings,
  type SettingsSection,
} from '@/services/system-settings.service'

export const SYSTEM_SETTINGS_KEYS = {
  ALL:      ['system-settings']        as const,
  FEATURES: ['system-settings', 'features'] as const,
}

export function useSystemSettings() {
  return useQuery({
    queryKey: SYSTEM_SETTINGS_KEYS.ALL,
    queryFn:  () => systemSettingsService.getAll(),
    staleTime: 5 * 60 * 1000,
  })
}

export function useUpdateSection<S extends SettingsSection>(section: S) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Partial<SystemSettings[S]>) =>
      systemSettingsService.updateSection(section, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: SYSTEM_SETTINGS_KEYS.ALL })
    },
  })
}

export function useFeatureFlags() {
  return useQuery({
    queryKey: SYSTEM_SETTINGS_KEYS.FEATURES,
    queryFn:  () => systemSettingsService.getFeatures(),
    staleTime: 5 * 60 * 1000,
  })
}

export function useUpdateFeatures() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (features: Record<string, boolean>) =>
      systemSettingsService.updateFeatures(features),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: SYSTEM_SETTINGS_KEYS.FEATURES })
    },
  })
}
