const KEYS = [
  ['F1', 'Produto'],
  ['F2', 'Cliente'],
  ['F4', 'Cobrar'],
  ['F9', 'Cancelar'],
  ['F10', 'Desconto'],
  ['Esc', 'Fechar'],
]

export function PdvKeybar() {
  return (
    <footer className="flex items-center gap-1 px-3 py-1.5 border-t bg-muted/30 text-xs text-muted-foreground shrink-0 select-none">
      {KEYS.map(([key, label]) => (
        <span key={key} className="flex items-center gap-1 mr-3">
          <kbd className="px-1.5 py-0.5 rounded border border-border bg-background font-mono text-[10px] leading-none">
            {key}
          </kbd>
          {label}
        </span>
      ))}
    </footer>
  )
}
