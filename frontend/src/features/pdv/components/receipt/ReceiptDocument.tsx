import { useAuth } from '@/hooks/use-auth'
import { usePdvSessionStore } from '../../stores/pdvSessionStore'
import type { ReceiptData } from '../../types'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

const fmtDateTime = (iso: string) =>
  new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })

const PAYMENT_LABELS: Record<string, string> = {
  cash:         'Dinheiro',
  debit_card:   'Débito',
  credit_card:  'Crédito',
  pix:          'PIX',
  store_credit: 'Crédito Loja',
  voucher:      'Voucher',
}

// All styling is inline so the receipt renders identically in preview and on paper
const S = {
  root: {
    fontFamily: "'Courier New', Courier, monospace",
    fontSize: '9pt',
    color: '#000',
    background: '#fff',
    width: '280px',
    padding: '6px 4px',
    lineHeight: '1.45',
  } as React.CSSProperties,
  center:  { textAlign: 'center' } as React.CSSProperties,
  bold:    { fontWeight: 'bold' } as React.CSSProperties,
  small:   { fontSize: '8pt' } as React.CSSProperties,
  large:   { fontSize: '11pt', fontWeight: 'bold' } as React.CSSProperties,
  row:     { display: 'flex', justifyContent: 'space-between' } as React.CSSProperties,
  sep:     { borderBottom: '1px dashed #555', margin: '4px 0' } as React.CSSProperties,
  total:   { display: 'flex', justifyContent: 'space-between', fontWeight: 'bold', fontSize: '12pt', marginTop: '2px' } as React.CSSProperties,
}

interface Props {
  data: ReceiptData
}

export function ReceiptDocument({ data }: Props) {
  const { user, tenant }  = useAuth()
  const session           = usePdvSessionStore((s) => s.session)
  const { sale, items, payments } = data

  const isFiscal   = sale.fiscal_status === 'issued'
  const totalPaid  = payments.reduce((s, p) => s + p.amountCents, 0)
  const change     = Math.max(0, totalPaid - sale.total_cents)
  const saleDate   = sale.completed_at ?? sale.created_at

  return (
    <div style={S.root}>

      {/* ── Cabeçalho ──────────────────────────────────────────────── */}
      <div style={S.center}>
        <p style={S.large}>{tenant?.trade_name ?? 'Loja'}</p>
        {tenant?.document && (
          <p style={S.small}>CNPJ: {tenant.document}</p>
        )}
        {tenant?.phone && <p style={S.small}>{tenant.phone}</p>}
        {tenant?.email && <p style={S.small}>{tenant.email}</p>}
      </div>

      <div style={S.sep} />

      {/* ── Identificação do documento ─────────────────────────────── */}
      <p style={{ ...S.center, ...S.bold }}>
        {isFiscal ? 'NFC-e AUTORIZADA' : 'COMPROVANTE DE VENDA'}
      </p>
      {sale.code && (
        <p style={{ ...S.center, ...S.small }}>Venda #{sale.code}</p>
      )}

      <div style={{ ...S.small, marginTop: '4px' }}>
        <p>Data: {fmtDateTime(saleDate)}</p>
        {session && (
          <div style={S.row}>
            <span>Caixa: {session.registerName}</span>
            {user && <span>Op: {user.name.split(' ')[0]}</span>}
          </div>
        )}
        {sale.customer && (
          <p>Cliente: {sale.customer.name}</p>
        )}
      </div>

      <div style={S.sep} />

      {/* ── Itens ─────────────────────────────────────────────────── */}
      {items.map((item) => {
        const lineTotal = item.unitPriceCents * item.quantity - item.discountCents
        return (
          <div key={item.id} style={{ marginBottom: '4px' }}>
            <p style={{ wordBreak: 'break-word' }}>{item.name}</p>
            <div style={{ ...S.row, ...S.small }}>
              <span>
                {item.quantity}x {fmtBRL(item.unitPriceCents)}
                {item.sku ? <span style={{ color: '#666' }}> ({item.sku})</span> : null}
              </span>
              <span style={S.bold}>{fmtBRL(lineTotal)}</span>
            </div>
            {item.discountCents > 0 && (
              <div style={{ ...S.row, ...S.small }}>
                <span style={{ color: '#555' }}>Desconto item</span>
                <span style={{ color: '#555' }}>-{fmtBRL(item.discountCents)}</span>
              </div>
            )}
          </div>
        )
      })}

      <div style={S.sep} />

      {/* ── Totais ────────────────────────────────────────────────── */}
      <div style={S.small}>
        <div style={S.row}>
          <span>Subtotal</span>
          <span>{fmtBRL(sale.subtotal_cents)}</span>
        </div>
        {sale.discount_total_cents > 0 && (
          <div style={S.row}>
            <span>Desconto</span>
            <span>-{fmtBRL(sale.discount_total_cents)}</span>
          </div>
        )}
      </div>
      <div style={S.total}>
        <span>TOTAL</span>
        <span>{fmtBRL(sale.total_cents)}</span>
      </div>

      <div style={S.sep} />

      {/* ── Pagamentos ────────────────────────────────────────────── */}
      <p style={{ ...S.bold, ...S.small }}>PAGAMENTO</p>
      {payments.map((p) => (
        <div key={p.id} style={{ ...S.row, ...S.small }}>
          <span>
            {PAYMENT_LABELS[p.method] ?? p.method}
            {p.installments && p.installments > 1 ? ` (${p.installments}x)` : ''}
            {p.nsu ? <span style={{ color: '#555' }}> NSU {p.nsu}</span> : null}
          </span>
          <span>{fmtBRL(p.amountCents)}</span>
        </div>
      ))}
      {change > 0 && (
        <div style={{ ...S.row, ...S.bold }}>
          <span>Troco</span>
          <span>{fmtBRL(change)}</span>
        </div>
      )}

      {/* ── Bloco fiscal (NFC-e) ──────────────────────────────────── */}
      {isFiscal ? (
        <>
          <div style={S.sep} />
          <div style={{ ...S.center, ...S.small }}>
            <p style={S.bold}>DOCUMENTO FISCAL ELETRÔNICO</p>
            <p>NFC-e • Série/Nº: {sale.code ?? '---'}</p>
            {/* Chave de acesso completa disponível na Etapa 7 */}
            <p style={{ fontSize: '7pt', color: '#555', marginTop: '2px' }}>
              Consulte em nfce.fazenda.gov.br
            </p>
          </div>
        </>
      ) : (
        <>
          <div style={S.sep} />
          <p style={{ ...S.center, ...S.small, color: '#666' }}>
            Documento sem valor fiscal
          </p>
        </>
      )}

      <div style={S.sep} />

      {/* ── Rodapé ───────────────────────────────────────────────── */}
      <p style={{ ...S.center, ...S.bold }}>Obrigado pela preferência!</p>
      <p style={{ ...S.center, ...S.small, color: '#666', marginTop: '2px' }}>
        {fmtDateTime(saleDate)}
      </p>
    </div>
  )
}
