import { z } from 'zod'

export const loginSchema = z.object({
  email:       z.string().min(1, 'E-mail obrigatório').email('E-mail inválido'),
  password:    z.string().min(8, 'Senha deve ter pelo menos 8 caracteres'),
  device_name: z.string().optional(),
})

export type LoginFormValues = z.infer<typeof loginSchema>
