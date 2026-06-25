import { apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'
import type { Role, Permission, TenantUserData } from '@store/shared-types'
import type {
  CreateRoleRequest,
  UpdateRoleRequest,
  AssignRoleRequest,
} from '@store/contracts'

const BASE = '/rbac'

export const rbacService = {
  // ── Roles ────────────────────────────────────────────────────────────────

  getRoles(): Promise<Role[]> {
    return apiGet<Role[]>(`${BASE}/roles`)
  },

  getRole(uuid: string): Promise<Role> {
    return apiGet<Role>(`${BASE}/roles/${uuid}`)
  },

  createRole(data: CreateRoleRequest): Promise<Role> {
    return apiPost<Role, CreateRoleRequest>(`${BASE}/roles`, data)
  },

  updateRole(uuid: string, data: UpdateRoleRequest): Promise<Role> {
    return apiPut<Role, UpdateRoleRequest>(`${BASE}/roles/${uuid}`, data)
  },

  deleteRole(uuid: string): Promise<void> {
    return apiDelete<void>(`${BASE}/roles/${uuid}`)
  },

  syncPermissions(roleUuid: string, permissionIds: string[]): Promise<Role> {
    return apiPost<Role, { permission_ids: string[] }>(
      `${BASE}/roles/${roleUuid}/sync-permissions`,
      { permission_ids: permissionIds }
    )
  },

  // ── Permissions ──────────────────────────────────────────────────────────

  getPermissions(): Promise<Permission[]> {
    return apiGet<Permission[]>(`${BASE}/permissions`)
  },

  // ── Users (TenantUsers) ──────────────────────────────────────────────────

  getUsers(): Promise<TenantUserData[]> {
    return apiGet<TenantUserData[]>(`${BASE}/users`)
  },

  getUser(uuid: string): Promise<TenantUserData> {
    return apiGet<TenantUserData>(`${BASE}/users/${uuid}`)
  },

  createUser(data: { name: string; email: string; password: string; role_id: string }): Promise<TenantUserData> {
    return apiPost<TenantUserData, typeof data>(`${BASE}/users`, data)
  },

  lookupByEmail(email: string): Promise<{ uuid: string; name: string; email: string }> {
    return apiGet<{ uuid: string; name: string; email: string }>(
      `${BASE}/users/lookup/by-email?email=${encodeURIComponent(email)}`
    )
  },

  assignRole(data: AssignRoleRequest): Promise<TenantUserData> {
    return apiPost<TenantUserData, AssignRoleRequest>(`${BASE}/users/assign`, data)
  },

  changeRole(tenantUserUuid: string, roleId: string): Promise<TenantUserData> {
    return apiPut<TenantUserData, { role_id: string }>(
      `${BASE}/users/${tenantUserUuid}/role`,
      { role_id: roleId }
    )
  },

  revokeUser(uuid: string): Promise<void> {
    return apiDelete<void>(`${BASE}/users/${uuid}/revoke`)
  },

  grantStoreAccess(userUuid: string, storeId: string): Promise<void> {
    return apiPost<void, { store_id: string }>(
      `${BASE}/users/${userUuid}/stores`,
      { store_id: storeId }
    )
  },

  revokeStoreAccess(userUuid: string, storeId: string): Promise<void> {
    return apiDelete<void>(`${BASE}/users/${userUuid}/stores/${storeId}`)
  },

  updatePin(tenantUserUuid: string, pin: string): Promise<void> {
    return apiPut<void, { pin: string }>(`${BASE}/users/${tenantUserUuid}/pin`, { pin })
  },

  removePin(tenantUserUuid: string): Promise<void> {
    return apiDelete<void>(`${BASE}/users/${tenantUserUuid}/pin`)
  },
}
