import { apiClient, apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'
import type {
  Brand,
  Category,
  Attribute,
  AttributeGroup,
  Grid,
  Product,
  ProductVariant,
  PaginatedResponse,
  PaginationMeta,
} from '@store/shared-types'
import type { ApiSuccess } from '@store/shared-types'
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

// ── Filters ──────────────────────────────────────────────────────────────────

export interface ProductFilters {
  q?:           string
  status?:      string
  type?:        string
  brand_id?:    string
  category_id?: string
  per_page?:    number
  page?:        number
}

// ── Service ───────────────────────────────────────────────────────────────────

export const catalogService = {

  // ── Brands ──────────────────────────────────────────────────────────────────

  getBrands(): Promise<Brand[]> {
    return apiGet<Brand[]>('/catalog/brands')
  },

  getBrand(uuid: string): Promise<Brand> {
    return apiGet<Brand>(`/catalog/brands/${uuid}`)
  },

  createBrand(data: CreateBrandRequest): Promise<Brand> {
    return apiPost<Brand, CreateBrandRequest>('/catalog/brands', data)
  },

  updateBrand(uuid: string, data: UpdateBrandRequest): Promise<Brand> {
    return apiPut<Brand, UpdateBrandRequest>(`/catalog/brands/${uuid}`, data)
  },

  deleteBrand(uuid: string): Promise<void> {
    return apiDelete<void>(`/catalog/brands/${uuid}`)
  },

  // ── Categories ───────────────────────────────────────────────────────────────

  getCategories(): Promise<Category[]> {
    return apiGet<Category[]>('/catalog/categories')
  },

  getCategory(uuid: string): Promise<Category> {
    return apiGet<Category>(`/catalog/categories/${uuid}`)
  },

  createCategory(data: CreateCategoryRequest): Promise<Category> {
    return apiPost<Category, CreateCategoryRequest>('/catalog/categories', data)
  },

  updateCategory(uuid: string, data: UpdateCategoryRequest): Promise<Category> {
    return apiPut<Category, UpdateCategoryRequest>(`/catalog/categories/${uuid}`, data)
  },

  deleteCategory(uuid: string): Promise<void> {
    return apiDelete<void>(`/catalog/categories/${uuid}`)
  },

  // ── Attribute Groups + Attributes ────────────────────────────────────────────

  getAttributeGroups(): Promise<AttributeGroup[]> {
    return apiGet<AttributeGroup[]>('/catalog/attribute-groups')
  },

  createAttributeGroup(data: CreateAttributeGroupRequest): Promise<AttributeGroup> {
    return apiPost<AttributeGroup, CreateAttributeGroupRequest>('/catalog/attribute-groups', data)
  },

  createAttribute(groupUuid: string, data: CreateAttributeRequest): Promise<Attribute> {
    return apiPost<Attribute, CreateAttributeRequest>(
      `/catalog/attribute-groups/${groupUuid}/attributes`,
      data,
    )
  },

  deleteAttribute(groupUuid: string, attrUuid: string): Promise<void> {
    return apiDelete<void>(`/catalog/attribute-groups/${groupUuid}/attributes/${attrUuid}`)
  },

  // ── Grids ────────────────────────────────────────────────────────────────────

  getGrids(): Promise<Grid[]> {
    return apiGet<Grid[]>('/catalog/grids')
  },

  getGrid(uuid: string): Promise<Grid> {
    return apiGet<Grid>(`/catalog/grids/${uuid}`)
  },

  createGrid(data: CreateGridRequest): Promise<Grid> {
    return apiPost<Grid, CreateGridRequest>('/catalog/grids', data)
  },

  // ── Products (paginated) ─────────────────────────────────────────────────────

  async getProducts(filters?: ProductFilters): Promise<PaginatedResponse<Product>> {
    const params = new URLSearchParams()
    if (filters?.q)           params.set('q', filters.q)
    if (filters?.status)      params.set('status', filters.status)
    if (filters?.type)        params.set('type', filters.type)
    if (filters?.brand_id)    params.set('brand_id', filters.brand_id)
    if (filters?.category_id) params.set('category_id', filters.category_id)
    if (filters?.per_page)    params.set('per_page', String(filters.per_page))
    if (filters?.page)        params.set('page', String(filters.page))
    const qs  = params.toString()
    const url = `/catalog/products${qs ? `?${qs}` : ''}`

    const res  = await apiClient.get<ApiSuccess<Product[]> & { meta?: PaginationMeta }>(url)
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as unknown as PaginationMeta) ?? {
        current_page: 1,
        per_page:     20,
        total:        0,
        last_page:    1,
      },
    }
  },

  getProduct(uuid: string): Promise<Product> {
    return apiGet<Product>(`/catalog/products/${uuid}`)
  },

  createProduct(data: CreateProductRequest): Promise<Product> {
    return apiPost<Product, CreateProductRequest>('/catalog/products', data)
  },

  updateProduct(uuid: string, data: UpdateProductRequest): Promise<Product> {
    return apiPut<Product, UpdateProductRequest>(`/catalog/products/${uuid}`, data)
  },

  deleteProduct(uuid: string): Promise<void> {
    return apiDelete<void>(`/catalog/products/${uuid}`)
  },

  publishProduct(uuid: string): Promise<Product> {
    return apiPost<Product>(`/catalog/products/${uuid}/publish`)
  },

  archiveProduct(uuid: string): Promise<Product> {
    return apiPost<Product>(`/catalog/products/${uuid}/archive`)
  },

  // ── Variants ─────────────────────────────────────────────────────────────────

  getVariants(productUuid: string): Promise<ProductVariant[]> {
    return apiGet<ProductVariant[]>(`/catalog/products/${productUuid}/variants`)
  },

  createVariant(data: CreateVariantRequest): Promise<ProductVariant> {
    return apiPost<ProductVariant, CreateVariantRequest>('/catalog/variants', data)
  },

  updateVariant(uuid: string, data: UpdateVariantRequest): Promise<ProductVariant> {
    return apiPut<ProductVariant, UpdateVariantRequest>(`/catalog/variants/${uuid}`, data)
  },

  deleteVariant(productUuid: string, variantUuid: string): Promise<void> {
    return apiDelete<void>(`/catalog/products/${productUuid}/variants/${variantUuid}`)
  },

  generateVariants(data: GenerateVariantsRequest): Promise<ProductVariant[]> {
    return apiPost<ProductVariant[], GenerateVariantsRequest>('/catalog/variants/generate', data)
  },
}
