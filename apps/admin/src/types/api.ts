export type { ApiError, ApiResponse, ApiSuccess, PaginationMeta, PaginatedResponse } from '@store/shared-types'

export interface AppError {
  message: string
  status:  number
  errors?: Record<string, string[]>
}
