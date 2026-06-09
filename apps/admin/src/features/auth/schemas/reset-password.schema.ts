import { z } from 'zod'

export const resetPasswordSchema = z
  .object({
    token:                 z.string().min(1, 'Token obrigatório'),
    email:                 z.string().min(1, 'E-mail obrigatório').email('E-mail inválido'),
    password:              z.string().min(8, 'Senha deve ter pelo menos 8 caracteres'),
    password_confirmation: z.string().min(8, 'Confirmação obrigatória'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'As senhas não coincidem',
    path:    ['password_confirmation'],
  })

export type ResetPasswordFormValues = z.infer<typeof resetPasswordSchema>
