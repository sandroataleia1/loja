'use client'

import { useAuth } from '@/hooks/use-auth'
import { UsersTable } from './users-table'

export function UsersTableWrapper() {
  const { user } = useAuth()
  return <UsersTable currentUserId={user?.uuid} />
}
