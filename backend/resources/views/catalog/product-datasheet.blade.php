<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Técnica — {{ $product->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .meta { color: #666; font-size: 11px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { text-align: left; padding: 6px 8px; border: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: bold; width: 35%; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-draft  { background: #fff3cd; color: #856404; }
        .section { margin-bottom: 20px; }
        .description { line-height: 1.6; }
    </style>
</head>
<body>
    <h1>{{ $product->name }}</h1>
    <div class="meta">
        SKU / Código: <strong>{{ $product->code ?? '—' }}</strong>
        &nbsp;|&nbsp;
        Status:
        <span class="badge {{ $product->status?->value === 'active' ? 'badge-active' : 'badge-draft' }}">
            {{ $product->status?->value ?? '—' }}
        </span>
        &nbsp;|&nbsp;
        Gerado em: {{ now()->format('d/m/Y H:i') }}
    </div>

    <div class="section">
        <h2>Informações Gerais</h2>
        <table>
            <tr><th>Marca</th><td>{{ $product->brand?->name ?? '—' }}</td></tr>
            <tr><th>Tipo</th><td>{{ $product->type?->value ?? '—' }}</td></tr>
            <tr><th>NCM</th><td>{{ $product->ncm ?? '—' }}</td></tr>
            <tr><th>CEST</th><td>{{ $product->cest ?? '—' }}</td></tr>
            <tr><th>Unidade</th><td>{{ $product->unit?->name ?? $product->unit_of_measure?->value ?? '—' }}</td></tr>
            <tr><th>Preço Base</th><td>R$ {{ $product->base_price_formatted ?? '—' }}</td></tr>
            <tr><th>Custo</th><td>R$ {{ $product->cost_price_formatted ?? '—' }}</td></tr>
            <tr><th>Peso Bruto (g)</th><td>{{ $product->weight_gross_g ?? '—' }}</td></tr>
            <tr><th>Peso Líquido (g)</th><td>{{ $product->weight_net_g ?? '—' }}</td></tr>
        </table>
    </div>

    @if($product->description)
    <div class="section">
        <h2>Descrição</h2>
        <p class="description">{{ $product->description }}</p>
    </div>
    @endif

    @if($product->categories->isNotEmpty())
    <div class="section">
        <h2>Categorias</h2>
        <p>{{ $product->categories->pluck('name')->implode(', ') }}</p>
    </div>
    @endif

    @if($product->variants->isNotEmpty())
    <div class="section">
        <h2>Variantes ({{ $product->variants->count() }})</h2>
        <table>
            <tr>
                <th>SKU</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Ativo</th>
            </tr>
            @foreach($product->variants as $variant)
            <tr>
                <td>{{ $variant->sku }}</td>
                <td>{{ $variant->name }}</td>
                <td>R$ {{ number_format($variant->price_cents / 100, 2, ',', '.') }}</td>
                <td>{{ $variant->is_active ? 'Sim' : 'Não' }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    @if($product->relationLoaded('technicalAttributes') && $product->technicalAttributes->isNotEmpty())
    <div class="section">
        <h2>Atributos Técnicos</h2>
        <table>
            @foreach($product->technicalAttributes as $attr)
            <tr>
                <th>{{ $attr->attribute?->name ?? $attr->attribute_id }}</th>
                <td>{{ $attr->value_text ?? $attr->value_number }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    @if($product->barcodes->isNotEmpty())
    <div class="section">
        <h2>Códigos de Barra</h2>
        <table>
            <tr><th>Tipo</th><th>Código</th><th>Principal</th></tr>
            @foreach($product->barcodes as $barcode)
            <tr>
                <td>{{ $barcode->barcode_type }}</td>
                <td>{{ $barcode->value }}</td>
                <td>{{ $barcode->is_primary ? 'Sim' : 'Não' }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif
</body>
</html>
