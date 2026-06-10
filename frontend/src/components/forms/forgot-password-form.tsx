'use client'

import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import Link from 'next/link'
import { Loader2, MailCheck } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import { forgotPasswordSchema, type ForgotPasswordFormValues } from '@/features/auth/schemas/forgot-password.schema'
import { authService } from '@/services/auth.service'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { ROUTES } from '@/constants'

export function ForgotPasswordForm() {
  const [sent, setSent] = useState(false)

  const { mutate, isPending } = useMutation({
    mutationFn: authService.forgotPassword,
    onSuccess:  () => setSent(true),
    onError:    () => toast.error('Não foi possível enviar o e-mail. Tente novamente.'),
  })

  const form = useForm<ForgotPasswordFormValues>({
    resolver:      zodResolver(forgotPasswordSchema),
    defaultValues: { email: '' },
  })

  if (sent) {
    return (
      <Card>
        <CardContent className="flex flex-col items-center gap-4 py-10 text-center">
          <MailCheck className="h-12 w-12 text-primary" />
          <div>
            <p className="font-semibold">E-mail enviado!</p>
            <p className="text-sm text-muted-foreground mt-1">
              Verifique sua caixa de entrada e siga as instruções para redefinir sua senha.
            </p>
          </div>
          <Button variant="outline" size="sm" asChild>
            <Link href={ROUTES.LOGIN}>Voltar ao login</Link>
          </Button>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-xl">Recuperar senha</CardTitle>
        <CardDescription>
          Informe seu e-mail e enviaremos um link para redefinir sua senha.
        </CardDescription>
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
          </CardContent>

          <CardFooter className="flex flex-col gap-3">
            <Button type="submit" className="w-full" disabled={isPending}>
              {isPending && <Loader2 className="h-4 w-4 animate-spin" />}
              {isPending ? 'Enviando...' : 'Enviar link de recuperação'}
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
