'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { rbacService } from '@/services/rbac.service'
import { MOCK_USERS } from '../mocks/users.mock'
import type { CreateRoleRequest, UpdateRoleRequest, AssignRoleRequest } from '@store/contracts'

// ── Query Keys ──────────────────────────────────────────────────────────────

export const RBAC_QUERY_KEYS = {
  ROLES:       ['rbac', 'roles']       as const,
  ROLE:        (uuid: string) => ['rbac', 'roles', uuid] as const,
  PERMISSIONS: ['rbac', 'permissions'] as const,
  USERS:       ['rbac', 'users']       as const,
  USER:        (uuid: string) => ['rbac', 'users', uuid] as const,
}

// ── Roles ───────────────────────────────────────────────────────────────────

export function useRoles() {
  return useQuery({
    queryKey: RBAC_QUERY_KEYS.ROLES,
    queryFn:  () => rbacService.getRoles(),
    staleTime: 5 * 60 * 1000,
  })
}

export function useRole(uuid: string) {
  return useQuery({
    queryKey: RBAC_QUERY_KEYS.ROLE(uuid),
    queryFn:  () => rbacService.getRole(uuid),
    enabled:  Boolean(uuid),
    staleTime: 5 * 60 * 1000,
  })
}

export function useCreateRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateRoleRequest) => rbacService.createRole(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.ROLES })
    },
  })
}

export function useUpdateRole(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpdateRoleRequest) => rbacService.updateRole(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.ROLES })
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.ROLE(uuid) })
    },
  })
}

export function useDeleteRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => rbacService.deleteRole(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.ROLES })
    },
  })
}

export function useSyncPermissions(roleUuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (permissionIds: string[]) =>
      rbacService.syncPermissions(roleUuid, permissionIds),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.ROLE(roleUuid) })
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.ROLES })
    },
  })
}

// ── Permissions ─────────────────────────────────────────────────────────────

export function usePermissions() {
  return useQuery({
    queryKey: RBAC_QUERY_KEYS.PERMISSIONS,
    queryFn:  () => rbacService.getPermissions(),
    staleTime: 10 * 60 * 1000,
  })
}

// ── Users ────────────────────────────────────────────────────────────────────

export function useUsers() {
  return useQuery({
    queryKey: RBAC_QUERY_KEYS.USERS,
    queryFn:  () => rbacService.getUsers(),
    staleTime: 60 * 1000,
  })
}

export function useCreateUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { name: string; email: string; password: string; role_id: string }) =>
      rbacService.createUser(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.USERS })
    },
  })
}

export function useAssignRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: AssignRoleRequest) => rbacService.assignRole(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.USERS })
    },
  })
}

export function useChangeRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ tenantUserUuid, roleId }: { tenantUserUuid: string; roleId: string }) =>
      rbacService.changeRole(tenantUserUuid, roleId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.USERS })
    },
  })
}

export function useRevokeUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => rbacService.revokeUser(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.USERS })
    },
  })
}

export function useUpdatePin() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ tenantUserUuid, pin }: { tenantUserUuid: string; pin: string }) =>
      rbacService.updatePin(tenantUserUuid, pin),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.USERS })
    },
  })
}

export function useRemovePin() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (tenantUserUuid: string) => rbacService.removePin(tenantUserUuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RBAC_QUERY_KEYS.USERS })
    },
  })
}
