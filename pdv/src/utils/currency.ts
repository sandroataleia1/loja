/** Formata centavos como moeda BRL: 1990 → "R$ 19,90" */
export function formatCurrency(cents: number): string {
  return new Intl.NumberFormat('pt-BR', {
    style:    'currency',
    currency: 'BRL',
  }).format(cents / 100)
}

/** Converte string monetária em centavos: "19,90" → 1990 */
export function parseCurrency(value: string): number {
  const cleaned = value.replace(/[^\d,]/g, '').replace(',', '.')
  return Math.round(parseFloat(cleaned || '0') * 100)
}
