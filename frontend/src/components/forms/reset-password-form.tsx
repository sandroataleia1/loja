'use client'

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import Link from 'next/link'
import { Loader2 } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import { useRouter } from 'next/navigation'
import { resetPasswordSchema, type ResetPasswordFormValues } from '@/features/auth/schemas/reset-password.schema'
import { authService } from '@/services/auth.service'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { ROUTES } from '@/constants'

interface ResetPasswordFormProps {
  token: string
  email?: string
}

export function ResetPasswordForm({ token, email = '' }: ResetPasswordFormProps) {
  const router = useRouter()

  const { mutate, isPending } = useMutation({
    mutationFn: authService.resetPassword,
    onSuccess:  () => {
      toast.success('Senha redefinida com sucesso!')
      router.push(ROUTES.LOGIN)
    },
    onError: () => toast.error('Não foi possível redefinir a senha. O link pode ter expirado.'),
  })

  const form = useForm<ResetPasswordFormValues>({
    resolver:      zodResolver(resetPasswordSchema),
    defaultValues: { token, email, password: '', password_confirmation: '' },
  })

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-xl">Nova senha</CardTitle>
        <CardDescription>Escolha uma senha forte para sua conta.</CardDescription>
      </CardHeader>

      <Form {...form}>
        <form onSubmit={form.handleSubmit((v) => mutate(v))}>
          <CardContent className="space-y-4">
            <FormField
              control={form.control}
              name="email"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>E-mail</FormLabel>
                  <FormControl>
                    <Input placeholder="voce@empresa.com" type="email" autoComplete="email" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="password"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Nova senha</FormLabel>
                  <FormControl>
                    <Input placeholder="••••••••" type="password" autoComplete="new-password" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="password_confirmation"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Confirmar nova senha</FormLabel>
                  <FormControl>
                    <Input placeholder="••••••••" type="password" autoComplete="new-password" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </CardContent>

          <CardFooter className="flex flex-col gap-3">
            <Button type="submit" className="w-full" disabled={isPending}>
              {isPending && <Loader2 className="h-4 w-4 animate-spin" />}
              {isPending ? 'Redefinindo...' : 'Redefinir senha'}
            </Button>

            <Link
              href={ROUTES.LOGIN}
              className="text-center text-sm text-muted-foreground hover:text-primary transition-colors"
            >
              Voltar ao login
            </Link>
          </CardFooter>
        </form>
      </Form>
    </Card>
  )
}
