import { z } from 'zod'

export const registerSchema = z
  .object({
    tenant_name:           z.string().min(2, 'Nome da empresa deve ter pelo menos 2 caracteres').max(150),
    name:                  z.string().min(2, 'Nome deve ter pelo menos 2 caracteres').max(150),
    email:                 z.string().min(1, 'E-mail obrigatório').email('E-mail inválido'),
    password:              z.string().min(8, 'Senha deve ter pelo menos 8 caracteres'),
    password_confirmation: z.string().min(8, 'Confirmação obrigatória'),
    legal_name:            z.string().max(150).optional(),
    document:              z.string().max(20).optional(),
    phone:                 z.string().max(20).optional(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message:  'As senhas não coincidem',
    path:     ['password_confirmation'],
  })

export type RegisterFormValues = z.infer<typeof registerSchema>
