import { apiPostForm, apiDelete } from '@/lib/api-client'
import type { ProductMedia } from '@store/shared-types'

const PRODUCT_TYPE = 'App\\Modules\\Catalog\\Models\\Product'

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

  remove(imageUuid: string): Promise<void> {
    return apiDelete<void>(`/catalog/images/${imageUuid}`)
  },
}
