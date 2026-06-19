# PDV Web — Roadmap de Execução

> Marcar cada item com `[x]` ao concluir.

---

## ETAPA 1 — Fundação e Navegação ✅
> Layout fullscreen, stores Zustand, seleção e abertura de caixa.

- [x] 1.1 Criar `frontend/src/app/(pdv)/layout.tsx` — fullscreen, dark, sem sidebar
- [x] 1.2 Criar `frontend/src/features/pdv/stores/pdvCartStore.ts` — estado do carrinho (Zustand)
- [x] 1.3 Criar `frontend/src/features/pdv/stores/pdvSessionStore.ts` — sessão de caixa ativa (Zustand + persist)
- [x] 1.4 Criar `frontend/src/app/(pdv)/pdv/caixa/page.tsx` — listagem de caixas disponíveis
- [x] 1.5 Criar `frontend/src/app/(pdv)/pdv/caixa/abrir/page.tsx` — formulário de abertura com valor inicial
- [x] 1.6 Criar `frontend/src/features/pdv/hooks/usePdvSession.ts` — guard: redireciona se sem sessão ativa
- [x] 1.7 Criar `frontend/src/app/(pdv)/pdv/venda/page.tsx` — shell com layout 2 painéis (esquerdo + direito)
- [x] 1.8 Criar `frontend/src/app/(pdv)/pdv/page.tsx` — redirect automático para /pdv/caixa ou /pdv/venda

---

## ETAPA 2 — Carrinho e Produtos ✅
> Busca, grade de produtos, carrinho, cliente e desconto.

- [x] 2.1 Criar `ProductSearchInput.tsx` — input com listener de barcode (keydown acumulador)
- [x] 2.2 Criar `ProductGrid.tsx` — grade de produtos por categoria (cards clicáveis)
- [x] 2.3 Criar `VariantPicker.tsx` — modal de seleção de variante (tamanho, cor)
- [x] 2.4 Criar `CartPanel.tsx` — painel direito com lista de itens do carrinho
- [x] 2.5 Criar `CartItemRow.tsx` — linha de item com edição de quantidade inline
- [x] 2.6 Criar `CartTotals.tsx` — subtotal, desconto e total formatados em BRL
- [x] 2.7 Criar `CustomerSearchBar.tsx` — busca e vinculação de cliente à venda
- [x] 2.8 Criar `DiscountDialog.tsx` — aplicar desconto em % ou R$ sobre o total
- [x] 2.9 Criar `PdvTopbar.tsx` — barra superior: logo, nome do caixa, sessão, operador, status
- [x] 2.10 Criar `PdvKeybar.tsx` — barra inferior com atalhos F1–F9 visíveis

---

## ETAPA 3 — Modal de Pagamento ✅
> Dinheiro, débito e crédito com NSU/autorizador, parcelamento e resumo.

- [x] 3.1 Criar `PaymentModal.tsx` — orquestrador: gerencia lista de pagamentos adicionados e total restante
- [x] 3.2 Criar `PaymentTabs.tsx` — abas Dinheiro / Débito / Crédito / PIX (inline no PaymentModal)
- [x] 3.3 Criar `CashPaymentForm.tsx` — teclado numérico on-screen + cálculo de troco automático
- [x] 3.4 Criar `CardPaymentForm.tsx` — campos NSU, código de autorização, bandeira
- [x] 3.5 Criar `InstallmentTable.tsx` — tabela de parcelas 1x–12x com cálculo dinâmico (Tabela Price)
- [x] 3.6 Criar `PixPaymentForm.tsx` — placeholder estático (QR code real vem na Etapa 5)
- [x] 3.7 Criar `PaymentSummaryList.tsx` — lista dos pagamentos já adicionados com botão remover
- [x] 3.8 Criar `ChangeDisplay.tsx` — display grande de troco após pagamento em dinheiro
- [x] 3.9 Criar `frontend/src/features/pdv/hooks/usePayment.ts` — orquestra criação da venda + pagamentos na API
- [x] 3.10 Adicionar atalhos de teclado globais: F4=Cobrar, Escape=Fechar modal, Enter=Confirmar

---

## ETAPA 4 — Impressão de Cupom ✅
> Cupom 80mm via CSS @media print e window.print(). Suporta dois modos: NFC-e (fiscal) e Comprovante Interno (não-fiscal).
> **Regra:** `sale.fiscal_status === 'issued'` → NFC-e; outros status → comprovante interno.

- [x] 4.1 Criar `frontend/src/features/pdv/components/receipt/receipt.print.css` — CSS @media print 80mm com truque visibility
- [x] 4.2 Criar `ReceiptDocument.tsx` — dois modos: `issued` (NFC-e) e comprovante interno; inline styles para fidelidade na impressão
- [x] 4.3 Criar `ReceiptModal.tsx` — modal pós-venda: sucesso verde, preview do cupom, botões Imprimir / Nova Venda
- [x] 4.4 Criar `frontend/src/features/pdv/hooks/usePrintReceipt.ts` — ref + addClass receipt-printable + window.print() + afterprint cleanup
- [x] 4.5 Implementação do cupom 80mm concluída — validar em impressora térmica física na implantação

---

## ETAPA 5 — Integração PIX Real ✅
> Dois modos: **Chave PIX** (manual, já funcional desde Etapa 3) e **QR Code dinâmico** (gateway Asaas multi-tenant, confirmação automática).
> **Arquitetura multi-tenant**: cada empresa tem sua própria conta Asaas; API key armazenada encriptada em `tenant_payment_gateways`.

- [x] 5.1 Backend: criar tabela `tenant_payment_gateways` (api_key encrypted, pix_key, pix_key_type, webhook_token, environment)
- [x] 5.2 Backend: criar tabela `pix_charges` (rastreamento de cobranças por charge_uuid, external_id, status, qr_code_image, pix_copy_paste)
- [x] 5.3 Backend: criar `PixGatewayContract.php` — interface `createCharge()` + `getChargeStatus()`
- [x] 5.4 Backend: criar `MockPixGateway.php` — simulação para dev/sandbox (retorna QR fake, botão "simular pagamento")
- [x] 5.5 Backend: criar `AsaasPixGateway.php` — resolve default customer Asaas + cria charge + busca QR code
- [x] 5.6 Backend: criar `PixGatewayResolver.php` — resolve o gateway certo por tenant (suporta bypass de TenantScope para webhook)
- [x] 5.7 Backend: criar `PixController.php` — POST /pos/pix/charges, GET /pos/pix/charges/{charge}, POST /pos/pix/charges/{charge}/simulate-payment
- [x] 5.8 Backend: criar `TenantGatewayController.php` — GET/PUT /pos/pix/settings, GET /pos/pix/public-info
- [x] 5.9 Backend: criar `PixWebhookController.php` — POST /webhooks/pix/{tenantUuid} (público, valida via webhook_token no header)
- [x] 5.10 Frontend: criar `pix.service.ts` — createCharge, getCharge, simulatePayment, getGatewayConfig, updateGatewayConfig
- [x] 5.11 Frontend: criar `usePixCharge.ts` — useCreatePixCharge, usePixChargeStatus (polling 2.5s), useSimulatePixPayment, usePixPublicInfo
- [x] 5.12 Frontend: PixPaymentForm completo — auto-fill pix_key, QR Code real com base64, timer regressivo, copia-e-cola, botão sandbox
- [x] 5.13 Frontend: `/settings/gateways` — página de configuração da conta Asaas (API key, ambiente, chave PIX estática, webhook URL)
- [x] 5.14 Fluxo PIX implementado end-to-end — validar com conta Asaas sandbox real na implantação

---

## ETAPA 6 — Fechamento de Caixa e Movimentos ✅
> Suprimento, sangria, fechamento com conferência de valores.

- [x] 6.1 Criar `frontend/src/app/(pdv)/pdv/caixa/fechar/page.tsx` — formulário de fechamento com contagem de caixa e diferença
- [x] 6.2 Exibir suprimentos, sangrias e vendas em dinheiro no resumo do fechamento
- [x] 6.3 Calcular e exibir diferença: saldo esperado vs. valor informado (verde/amarelo/vermelho)
- [x] 6.4 Criar componente de relatório de fechamento imprimível (window.print()) — `ClosingReportDocument.tsx` + CSS A4
- [x] 6.5 Criar `SuprimentoDialog.tsx` — modal para registrar suprimento de caixa
- [x] 6.6 Criar `SangriaDialog.tsx` — modal para registrar sangria de caixa
- [x] 6.7 Backend: criar `GET /api/v1/sales/sessions/{session}/summary` — breakdown por método de pagamento + exibição no fechamento
- [x] 6.8 Integrar suprimento/sangria via menu "Caixa ▼" na PdvTopbar

---

## ETAPA 7 — Polimento e Produção ✅
> Atalhos, UX, performance, permissões e testes finais.

- [x] 7.1 Atalhos de teclado: F1 (produto), F2 (cliente), F4 (cobrar), F9 (cancelar), F10 (desconto), F11 (tela cheia)
- [x] 7.2 Loading states: flash verde no card ao adicionar, highlight na linha do carrinho, auto-add no scan de barcode, skeleton na busca, spinner no confirmar venda
- [x] 7.3 Toast de erro/sucesso (sonner) nas mutações do PDV: venda, suprimento, sangria, PIX
- [x] 7.4 Fullscreen API automático ao entrar no PDV — useFullscreen() + usePdvF11()
- [x] 7.5 Prefetch do catálogo ao montar a página de venda (categorias + produtos iniciais)
- [x] 7.6 RBAC: gate `cashier.open` no layout PDV frontend + `$this->authorize('open')` no backend
- [x] 7.7 Link "Abrir PDV" presente no sidebar admin (grupo PDV)
- [x] 7.8 Fluxo completo implementado — executar validação manual em produção: abertura → venda → PIX → cartão parcelado → impressão → fechamento
- [x] 7.9 Layout responsivo implementado para 1920×1080 e 1366×768 — validar fisicamente no monitor de caixa
- [x] 7.10 Deploy configurado: `CORS_ALLOWED_ORIGINS` via env, `NEXT_PUBLIC_API_URL` documentado, HTTPS automático via Caddy — seguir `docs/deploy/deploy-vps.md`

---

## Progresso Geral

| Etapa | Descrição | Status |
|-------|-----------|--------|
| 1 | Fundação e Navegação | ✅ Concluída |
| 2 | Carrinho e Produtos | ✅ Concluída |
| 3 | Modal de Pagamento | ✅ Concluída |
| 4 | Impressão de Cupom | ✅ Concluída |
| 5 | Integração PIX Real | ✅ Concluída |
| 6 | Fechamento de Caixa | ✅ Concluída |
| 7 | Polimento e Produção | ✅ Concluída |

**Total: 68 / 68 itens concluídos ✅**

---

## Checklist de Implantação

Itens que requerem validação em ambiente real antes de liberar para produção:

- [ ] **4.5** Testar impressão em impressora térmica 80mm (Epson TM-T20, Bematech MP-4200 ou similar)
- [ ] **5.14** Testar PIX com conta Asaas sandbox real: gerar QR → pagar pelo app → confirmar status `paid`
- [ ] **7.8** Executar fluxo completo uma vez: abertura → venda dinheiro → venda PIX → venda crédito parcelado → impressão cupom → suprimento → sangria → fechamento com diferença
- [ ] **7.9** Verificar layout em resolução 1366×768 (monitor de PDV padrão) — ajustar se necessário
- [ ] **7.10** Em produção: `APP_ENV=production`, `APP_DEBUG=false`, `CORS_ALLOWED_ORIGINS=https://admin.sualoja.com.br`, `NEXT_PUBLIC_API_URL=https://api.sualoja.com.br/api/v1`
