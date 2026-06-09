import { db } from './database'
import type {
  CreateProductInput, CreateVariantInput, Product, ProductVariant,
  UpdateProductInput, UpdateVariantInput,
} from '@/types/product'

export const productService = {
  list: (params?: {
    search?: string
    status?: string
    limit?:  number
    offset?: number
  }): Promise<Product[]> =>
    db('product_list', params ?? {}),

  get: (uuid: string): Promise<Product | null> =>
    db('product_get', { uuid }),

  create: (input: CreateProductInput): Promise<Product> =>
    db('product_create', { input }),

  update: (uuid: string, input: UpdateProductInput): Promise<Product | null> =>
    db('product_update', { uuid, input }),

  delete: (uuid: string): Promise<boolean> =>
    db('product_delete', { uuid }),

  // ---- Variants ------------------------------------------------

  listVariants: (productUuid: string): Promise<ProductVariant[]> =>
    db('variant_list', { product_uuid: productUuid }),

  getVariant: (uuid: string): Promise<ProductVariant | null> =>
    db('variant_get', { uuid }),

  createVariant: (input: CreateVariantInput): Promise<ProductVariant> =>
    db('variant_create', { input }),

  updateVariant: (uuid: string, input: UpdateVariantInput): Promise<ProductVariant | null> =>
    db('variant_update', { uuid, input }),
}
