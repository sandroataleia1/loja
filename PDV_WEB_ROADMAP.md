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

## ETAPA 4 — Impressão de Cupom
> Cupom 80mm via CSS @media print e window.print().

- [ ] 4.1 Criar `frontend/src/features/pdv/components/receipt/receipt.print.css` — CSS @media print 80mm
- [ ] 4.2 Criar `ReceiptDocument.tsx` — HTML do cupom: cabeçalho, itens, pagamentos, NFC-e, rodapé
- [ ] 4.3 Criar `ReceiptModal.tsx` — modal pós-venda: botões Imprimir / Nova Venda / Ver Detalhes
- [ ] 4.4 Criar `frontend/src/features/pdv/hooks/usePrintReceipt.ts` — monta portal no body + dispara window.print()
- [ ] 4.5 Testar impressão em impressora térmica 80mm real (Epson, Bematech ou Elgin)

---

## ETAPA 5 — Integração PIX Real
> Gateway Asaas/Efí no backend + QR code dinâmico + polling de status.

- [ ] 5.1 Backend: criar `PixGatewayContract.php` — interface do gateway PIX
- [ ] 5.2 Backend: criar `MockPixGateway.php` — simulação para dev/sandbox
- [ ] 5.3 Backend: criar `AsaasPixGateway.php` (ou `EfiPixGateway.php`) — implementação real
- [ ] 5.4 Backend: criar `PixController.php` — endpoints POST /charge, GET /charge/{id}, DELETE /charge/{id}
- [ ] 5.5 Backend: registrar rotas em `routes/api/v1/pdv.php`
- [ ] 5.6 Backend: criar webhook receiver + `UpdatePixPaymentStatusAction`
- [ ] 5.7 Backend: adicionar credenciais do gateway em `.env` e `config/services.php`
- [ ] 5.8 Frontend: criar `frontend/src/features/pdv/hooks/usePixCharge.ts` — polling 2.5s com auto-stop ao pagar/expirar
- [ ] 5.9 Frontend: atualizar `PixPaymentForm.tsx` — QR code real + timer regressivo + botão copia-e-cola
- [ ] 5.10 Testar fluxo completo PIX: criação → QR → pagamento → confirmação → concluir venda

---

## ETAPA 6 — Fechamento de Caixa e Movimentos
> Suprimento, sangria, fechamento com conferência e relatório imprimível.

- [ ] 6.1 Criar `frontend/src/app/(pdv)/caixa/fechar/page.tsx` — formulário de fechamento
- [ ] 6.2 Exibir breakdown por método de pagamento (dinheiro, PIX, cartão) no fechamento
- [ ] 6.3 Calcular e exibir diferença: saldo esperado vs. valor informado pelo operador
- [ ] 6.4 Criar componente de relatório de fechamento imprimível (window.print())
- [ ] 6.5 Criar `SuprimentoDialog.tsx` — modal para registrar suprimento de caixa
- [ ] 6.6 Criar `SangriaDialog.tsx` — modal para registrar sangria de caixa
- [ ] 6.7 Backend: criar `GET /api/v1/pdv/session/summary` — resumo da sessão para fechamento
- [ ] 6.8 Integrar suprimento/sangria na tela de venda (menu ou botão de caixa)

---

## ETAPA 7 — Polimento e Produção
> Atalhos, UX, performance, permissões e testes finais.

- [ ] 7.1 Implementar atalhos de teclado completos: F1–F12, Escape, Enter, Backspace no carrinho
- [ ] 7.2 Adicionar loading states em todas as ações (busca, adicionar item, confirmar venda)
- [ ] 7.3 Adicionar toast de erro com botão "Tentar novamente" nas falhas de API
- [ ] 7.4 Ativar modo tela cheia automático ao entrar no PDV (Fullscreen API)
- [ ] 7.5 Prefetch do catálogo ao abrir sessão (React Query com staleTime 5min)
- [ ] 7.6 Configurar permissão `pdv.access` no RBAC do backend
- [ ] 7.7 Adicionar link "Acessar PDV" no admin (menu lateral ou card no dashboard)
- [ ] 7.8 Testar fluxo completo: abertura → venda → PIX → cartão parcelado → impressão → fechamento
- [ ] 7.9 Testar em resolução 1366×768 (monitor de caixa padrão)
- [ ] 7.10 Deploy: verificar variáveis de ambiente, CORS e HTTPS em produção

---

## Progresso Geral

| Etapa | Descrição | Status |
|-------|-----------|--------|
| 1 | Fundação e Navegação | ✅ Concluída |
| 2 | Carrinho e Produtos | ✅ Concluída |
| 3 | Modal de Pagamento | ✅ Concluída |
| 4 | Impressão de Cupom | ⬜ Pendente |
| 5 | Integração PIX Real | ⬜ Pendente |
| 6 | Fechamento de Caixa | ⬜ Pendente |
| 7 | Polimento e Produção | ⬜ Pendente |

**Total: 28 / 50 itens concluídos**
