import type { CashMovement } from '@/types/shared-types'

interface SessionSummary {
  sale_count:   number
  total_sales:  number
  by_method: {
    cash:         number
    pix:          number
    credit_card:  number
    debit_card:   number
    store_credit: number
  }
  supply_total:     number
  withdrawal_total: number
  expected_balance: number
  opening_balance:  number
}

interface ClosingReportDocumentProps {
  registerName:        string
  operatorName:        string
  openedAt:            string
  closedAt:            string
  openingBalanceCents: number
  closingBalanceCents: number
  difference:          number
  notes?:              string
  summary?:            SessionSummary
  movements:           CashMovement[]
}

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

const fmtDateTime = (iso: string) =>
  new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })

const MOVEMENT_LABEL: Record<string, string> = {
  opening:    'Abertura',
  sale:       'Venda',
  supply:     'Suprimento',
  withdrawal: 'Sangria',
  refund:     'Reembolso',
  adjustment: 'Ajuste',
  closing:    'Fechamento',
}

const PAYMENT_LABELS: [keyof SessionSummary['by_method'], string][] = [
  ['cash',         'Dinheiro'],
  ['pix',          'PIX'],
  ['credit_card',  'Cartão Crédito'],
  ['debit_card',   'Cartão Débito'],
  ['store_credit', 'Crédito da Loja'],
]

export function ClosingReportDocument({
  registerName,
  operatorName,
  openedAt,
  closedAt,
  openingBalanceCents,
  closingBalanceCents,
  difference,
  notes,
  summary,
  movements,
}: ClosingReportDocumentProps) {
  const diffClass =
    difference === 0 ? { color: '#16a34a' }
    : Math.abs(difference) <= 100 ? { color: '#d97706' }
    : { color: '#dc2626' }

  const nonOpeningMovements = movements.filter(
    (m) => m.type !== 'opening' && m.type !== 'closing',
  )

  return (
    <div
      id="closing-report-printable"
      style={{
        fontFamily:  'Arial, sans-serif',
        fontSize:    '11px',
        color:       '#111',
        background:  '#fff',
        padding:     '8mm',
        width:       '100%',
        maxWidth:    '190mm',
        margin:      '0 auto',
        lineHeight:  '1.4',
      }}
    >
      {/* Header */}
      <div style={{ textAlign: 'center', marginBottom: '8px', borderBottom: '2px solid #111', paddingBottom: '6px' }}>
        <div style={{ fontSize: '14px', fontWeight: 'bold', marginBottom: '2px' }}>
          RELATÓRIO DE FECHAMENTO DE CAIXA
        </div>
        <div style={{ fontSize: '10px', color: '#555' }}>Documento interno — não fiscal</div>
      </div>

      {/* Session info */}
      <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: '8px' }}>
        <tbody>
          <Row label="Caixa"       value={registerName} />
          <Row label="Operador"    value={operatorName} />
          <Row label="Abertura"    value={fmtDateTime(openedAt)} />
          <Row label="Fechamento"  value={fmtDateTime(closedAt)} />
        </tbody>
      </table>

      <hr style={{ margin: '6px 0', borderTop: '1px dashed #ccc' }} />

      {/* Sales breakdown */}
      {summary && summary.sale_count > 0 && (
        <>
          <div style={{ fontWeight: 'bold', marginBottom: '4px', fontSize: '11px' }}>
            VENDAS — {summary.sale_count} {summary.sale_count === 1 ? 'transação' : 'transações'}
          </div>
          <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: '4px' }}>
            <tbody>
              {PAYMENT_LABELS.filter(([k]) => summary.by_method[k] > 0).map(([key, label]) => (
                <Row key={key} label={label} value={fmtBRL(summary.by_method[key])} />
              ))}
            </tbody>
          </table>
          <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: '8px' }}>
            <tbody>
              <Row label="TOTAL VENDAS" value={fmtBRL(summary.total_sales)} bold />
            </tbody>
          </table>
          <hr style={{ margin: '6px 0', borderTop: '1px dashed #ccc' }} />
        </>
      )}

      {/* Balance reconciliation */}
      <div style={{ fontWeight: 'bold', marginBottom: '4px', fontSize: '11px' }}>
        CONFERÊNCIA DE CAIXA
      </div>
      <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: '8px' }}>
        <tbody>
          <Row label="Saldo inicial"          value={fmtBRL(openingBalanceCents)} />
          {summary && summary.supply_total > 0 && (
            <Row label="Suprimentos"          value={`+ ${fmtBRL(summary.supply_total)}`} />
          )}
          {summary && summary.withdrawal_total > 0 && (
            <Row label="Sangrias"             value={`- ${fmtBRL(summary.withdrawal_total)}`} />
          )}
          {summary && summary.by_method.cash > 0 && (
            <Row label="Vendas em dinheiro"   value={fmtBRL(summary.by_method.cash)} />
          )}
          <Row label="Saldo esperado"         value={fmtBRL(summary?.expected_balance ?? openingBalanceCents)} bold />
          <Row label="Saldo informado"        value={fmtBRL(closingBalanceCents)} />
          <RowStyled
            label="Diferença"
            value={`${difference > 0 ? '+' : ''}${fmtBRL(difference)}`}
            style={diffClass}
            bold
          />
        </tbody>
      </table>

      {/* Movements */}
      {nonOpeningMovements.length > 0 && (
        <>
          <hr style={{ margin: '6px 0', borderTop: '1px dashed #ccc' }} />
          <div style={{ fontWeight: 'bold', marginBottom: '4px', fontSize: '11px' }}>MOVIMENTAÇÕES</div>
          <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: '8px' }}>
            <thead>
              <tr style={{ borderBottom: '1px solid #ccc', fontSize: '10px', color: '#555' }}>
                <th style={{ textAlign: 'left', padding: '2px 0', fontWeight: 'normal' }}>Tipo</th>
                <th style={{ textAlign: 'left', padding: '2px 4px', fontWeight: 'normal' }}>Descrição</th>
                <th style={{ textAlign: 'right', padding: '2px 0', fontWeight: 'normal' }}>Valor</th>
              </tr>
            </thead>
            <tbody>
              {nonOpeningMovements.map((m) => (
                <tr key={m.uuid} style={{ borderBottom: '1px solid #f0f0f0' }}>
                  <td style={{ padding: '2px 0' }}>{MOVEMENT_LABEL[m.type] ?? m.type}</td>
                  <td style={{ padding: '2px 4px', color: '#555', fontSize: '10px' }}>{m.description ?? '—'}</td>
                  <td style={{ textAlign: 'right', padding: '2px 0' }}>
                    {['withdrawal', 'refund'].includes(m.type) ? '-' : '+'}
                    {fmtBRL(m.amount_cents)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </>
      )}

      {/* Notes */}
      {notes && (
        <>
          <hr style={{ margin: '6px 0', borderTop: '1px dashed #ccc' }} />
          <div style={{ fontWeight: 'bold', marginBottom: '2px', fontSize: '11px' }}>OBSERVAÇÕES</div>
          <div style={{ fontSize: '10px', color: '#333' }}>{notes}</div>
        </>
      )}

      {/* Footer */}
      <hr style={{ margin: '8px 0', borderTop: '2px solid #111' }} />
      <div style={{ textAlign: 'center', fontSize: '9px', color: '#777' }}>
        Impresso em {new Date().toLocaleString('pt-BR')}
      </div>
    </div>
  )
}

function Row({ label, value, bold }: { label: string; value: string; bold?: boolean }) {
  return (
    <tr>
      <td style={{ padding: '1.5px 0', color: bold ? '#111' : '#555', fontWeight: bold ? 'bold' : 'normal' }}>
        {label}
      </td>
      <td style={{ textAlign: 'right', padding: '1.5px 0', fontWeight: bold ? 'bold' : 'normal' }}>
        {value}
      </td>
    </tr>
  )
}

function RowStyled({
  label, value, style, bold,
}: { label: string; value: string; style?: React.CSSProperties; bold?: boolean }) {
  return (
    <tr>
      <td style={{ padding: '1.5px 0', ...style, fontWeight: bold ? 'bold' : 'normal' }}>{label}</td>
      <td style={{ textAlign: 'right', padding: '1.5px 0', ...style, fontWeight: bold ? 'bold' : 'normal' }}>
        {value}
      </td>
    </tr>
  )
}
