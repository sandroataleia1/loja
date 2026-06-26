'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { catalogService, type ProductFilters } from '@/services/catalog.service'
import type { Brand, Category } from '@store/shared-types'
import type {
  CreateBrandRequest,
  UpdateBrandRequest,
  CreateCategoryRequest,
  UpdateCategoryRequest,
  CreateAttributeGroupRequest,
  CreateAttributeRequest,
  CreateGridRequest,
  CreateProductRequest,
  UpdateProductRequest,
  CreateVariantRequest,
  UpdateVariantRequest,
  GenerateVariantsRequest,
} from '@store/contracts'

// ── Query Keys ────────────────────────────────────────────────────────────────

export const CATALOG_QUERY_KEYS = {
  BRANDS:           ['brands']                               as const,
  BRAND:            (uuid: string) => ['brands', uuid]       as const,
  CATEGORIES:       ['categories']                           as const,
  CATEGORY:         (uuid: string) => ['categories', uuid]   as const,
  ATTRIBUTE_GROUPS: ['attribute-groups']                     as const,
  GRIDS:            ['grids']                                as const,
  GRID:             (uuid: string) => ['grids', uuid]        as const,
  PRODUCTS:         (filters?: ProductFilters) => ['products', filters] as const,
  PRODUCT:          (uuid: string) => ['products', uuid]     as const,
  VARIANTS:         (productUuid: string) => ['variants', productUuid] as const,
}

// ── Brands ────────────────────────────────────────────────────────────────────

export function useBrands() {
  return useQuery({
    queryKey: CATALOG_QUERY_KEYS.BRANDS,
    queryFn:  () => catalogService.getBrands(),
    staleTime: 5 * 60 * 1000,
  })
}

export function useBrand(uuid: string) {
  return useQuery({
    queryKey: CATALOG_QUERY_KEYS.BRAND(uuid),
    queryFn:  () => catalogService.getBrand(uuid),
    enabled:  Boolean(uuid),
    staleTime: 5 * 60 * 1000,
  })
}

export function useCreateBrand() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateBrandRequest) => catalogService.createBrand(data),
    onSuccess: (brand) => {
      qc.setQueryData<Brand[]>(CATALOG_QUERY_KEYS.BRANDS, (old) => (old ? [...old, brand] : [brand]))
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.BRANDS })
    },
  })
}

export function useUpdateBrand(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpdateBrandRequest) => catalogService.updateBrand(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.BRANDS })
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.BRAND(uuid) })
    },
  })
}

export function useDeleteBrand() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => catalogService.deleteBrand(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.BRANDS })
    },
  })
}

// ── Categories ────────────────────────────────────────────────────────────────

export function useCategories() {
  return useQuery({
    queryKey: CATALOG_QUERY_KEYS.CATEGORIES,
    queryFn:  () => catalogService.getCategories(),
    staleTime: 5 * 60 * 1000,
  })
}

export function useCreateCategory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateCategoryRequest) => catalogService.createCategory(data),
    onSuccess: (category) => {
      qc.setQueryData<Category[]>(CATALOG_QUERY_KEYS.CATEGORIES, (old) => (old ? [...old, category] : [category]))
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.CATEGORIES })
    },
  })
}

export function useUpdateCategory(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpdateCategoryRequest) => catalogService.updateCategory(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.CATEGORIES })
    },
  })
}

export function useDeleteCategory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => catalogService.deleteCategory(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.CATEGORIES })
    },
  })
}

// ── Attribute Groups ──────────────────────────────────────────────────────────

export function useAttributeGroups() {
  return useQuery({
    queryKey: CATALOG_QUERY_KEYS.ATTRIBUTE_GROUPS,
    queryFn:  () => catalogService.getAttributeGroups(),
    staleTime: 5 * 60 * 1000,
  })
}

export function useCreateAttributeGroup() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateAttributeGroupRequest) => catalogService.createAttributeGroup(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.ATTRIBUTE_GROUPS })
    },
  })
}

export function useCreateAttribute(groupUuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateAttributeRequest) => catalogService.createAttribute(groupUuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.ATTRIBUTE_GROUPS })
    },
  })
}

export function useDeleteAttribute(groupUuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (attrUuid: string) => catalogService.deleteAttribute(groupUuid, attrUuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.ATTRIBUTE_GROUPS })
    },
  })
}

// ── Grids ─────────────────────────────────────────────────────────────────────

export function useGrids() {
  return useQuery({
    queryKey: CATALOG_QUERY_KEYS.GRIDS,
    queryFn:  () => catalogService.getGrids(),
    staleTime: 5 * 60 * 1000,
  })
}

export function useCreateGrid() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateGridRequest) => catalogService.createGrid(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.GRIDS })
    },
  })
}

// ── Products ──────────────────────────────────────────────────────────────────

export function useProducts(filters?: ProductFilters) {
  return useQuery({
    queryKey: CATALOG_QUERY_KEYS.PRODUCTS(filters),
    queryFn:  () => catalogService.getProducts(filters),
    staleTime: 2 * 60 * 1000,
  })
}

export function useProduct(uuid: string) {
  return useQuery({
    queryKey: CATALOG_QUERY_KEYS.PRODUCT(uuid),
    queryFn:  () => catalogService.getProduct(uuid),
    enabled:  Boolean(uuid),
    staleTime: 2 * 60 * 1000,
  })
}

export function useCreateProduct() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateProductRequest) => catalogService.createProduct(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products'] })
    },
  })
}

export function useUpdateProduct(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpdateProductRequest) => catalogService.updateProduct(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products'] })
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.PRODUCT(uuid) })
    },
  })
}

export function useDeleteProduct() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => catalogService.deleteProduct(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products'] })
    },
  })
}

export function usePublishProduct(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => catalogService.publishProduct(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.PRODUCT(uuid) })
      qc.invalidateQueries({ queryKey: ['products'] })
    },
  })
}

export function useArchiveProduct(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => catalogService.archiveProduct(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.PRODUCT(uuid) })
      qc.invalidateQueries({ queryKey: ['products'] })
    },
  })
}

// ── Variants ──────────────────────────────────────────────────────────────────

export function useVariants(productUuid: string) {
  return useQuery({
    queryKey: CATALOG_QUERY_KEYS.VARIANTS(productUuid),
    queryFn:  () => catalogService.getVariants(productUuid),
    enabled:  Boolean(productUuid),
    staleTime: 2 * 60 * 1000,
  })
}

export function useCreateVariant() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateVariantRequest) => catalogService.createVariant(data),
    onSuccess: (_, variables) => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.VARIANTS(variables.product_id) })
      qc.invalidateQueries({ queryKey: ['products'] })
    },
  })
}

export function useUpdateVariant(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpdateVariantRequest) => catalogService.updateVariant(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['variants'] })
    },
  })
}

export function useDeleteVariant(productUuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (variantUuid: string) => catalogService.deleteVariant(productUuid, variantUuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.VARIANTS(productUuid) })
      qc.invalidateQueries({ queryKey: ['products'] })
    },
  })
}

export function useGenerateVariants() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: GenerateVariantsRequest) => catalogService.generateVariants(data),
    onSuccess: (_, variables) => {
      qc.invalidateQueries({ queryKey: CATALOG_QUERY_KEYS.VARIANTS(variables.product_id) })
      qc.invalidateQueries({ queryKey: ['products'] })
    },
  })
}
