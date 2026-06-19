import { apiGet, apiPost, apiPut } from '@/lib/api-client'

// ── Types ─────────────────────────────────────────────────────────────────────

export interface PixCharge {
  uuid:            string
  sale_id:         string | null
  gateway:         string
  external_id:     string | null
  status:          'pending' | 'paid' | 'expired' | 'refunded'
  amount_cents:    number
  qr_code_image:   string | null  // base64 PNG
  pix_copy_paste:  string | null
  expires_at:      string | null  // ISO 8601
  paid_at:         string | null
}

export interface PixPublicInfo {
  pix_key:            string | null
  pix_key_type:       string | null
  has_qrcode_enabled: boolean
  environment:        'sandbox' | 'production'
}

export interface GatewayConfig {
  uuid:           string | null
  gateway:        string
  api_key_masked: string | null
  has_api_key:    boolean
  environment:    'sandbox' | 'production'
  is_active:      boolean
  pix_key:        string | null
  pix_key_type:   string | null
}

export interface CreatePixChargeParams {
  amount_cents:        number
  description?:        string
  sale_uuid?:          string
  expires_in_minutes?: number
}

export interface UpdateGatewayConfigParams {
  gateway?:      string
  api_key?:      string
  environment?:  'sandbox' | 'production'
  is_active?:    boolean
  pix_key?:      string | null
  pix_key_type?: string | null
}

// ── Service ───────────────────────────────────────────────────────────────────

export const pixService = {
  getPublicInfo(): Promise<PixPublicInfo> {
    return apiGet<PixPublicInfo>('/pos/pix/public-info')
  },

  createCharge(params: CreatePixChargeParams): Promise<PixCharge> {
    return apiPost<PixCharge, CreatePixChargeParams>('/pos/pix/charges', params)
  },

  getCharge(uuid: string): Promise<PixCharge> {
    return apiGet<PixCharge>(`/pos/pix/charges/${uuid}`)
  },

  simulatePayment(uuid: string): Promise<PixCharge> {
    return apiPost<PixCharge>(`/pos/pix/charges/${uuid}/simulate-payment`)
  },

  getGatewayConfig(): Promise<GatewayConfig | null> {
    return apiGet<GatewayConfig | null>('/pos/pix/settings')
  },

  updateGatewayConfig(params: UpdateGatewayConfigParams): Promise<GatewayConfig> {
    return apiPut<GatewayConfig, UpdateGatewayConfigParams>('/pos/pix/settings', params)
  },
}
