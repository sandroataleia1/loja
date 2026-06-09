import { db } from './database'
import type { CreateCustomerInput, Customer } from '@/types/customer'

export const customerService = {
  list: (params?: {
    search?: string
    limit?:  number
    offset?: number
  }): Promise<Customer[]> =>
    db('customer_list', params ?? {}),

  get: (uuid: string): Promise<Customer | null> =>
    db('customer_get', { uuid }),

  create: (input: CreateCustomerInput): Promise<Customer> =>
    db('customer_create', { input }),
}
