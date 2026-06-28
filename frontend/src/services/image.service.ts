import { apiPostForm, apiDelete, apiClient } from '@/lib/api-client'
import type { ApiResponse, ProductMedia } from '@store/shared-types'

const PRODUCT_TYPE = 'App\\Modules\\Catalog\\Models\\Product'

async function apiPatch<T>(path: string, data: unknown): Promise<T> {
  const res = await apiClient.patch<ApiResponse<T>>(path, data)
  const body = res.data
  if (!body.success) throw new Error((body as { message: string }).message)
  return (body as { data: T }).data
}

export const imageService = {
  upload(productUuid: string, file: File, isPrimary = false, altText?: string): Promise<ProductMedia> {
    const form = new FormData()
    form.append('imageable_id',   productUuid)
    form.append('imageable_type', PRODUCT_TYPE)
    form.append('file',           file)
    form.append('is_primary',     isPrimary ? '1' : '0')
    if (altText) form.append('alt_text', altText)
    return apiPostForm<ProductMedia>('/catalog/images', form)
  },

  setPrimary(imageUuid: string): Promise<ProductMedia> {
    return apiPatch<ProductMedia>(`/catalog/images/${imageUuid}`, { is_primary: true })
  },

  updateAltText(imageUuid: string, altText: string): Promise<ProductMedia> {
    return apiPatch<ProductMedia>(`/catalog/images/${imageUuid}`, { alt_text: altText })
  },

  remove(imageUuid: string): Promise<void> {
    return apiDelete<void>(`/catalog/images/${imageUuid}`)
  },
}
