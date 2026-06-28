<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Produtos</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #666; font-size: 10px; margin-bottom: 20px; }
        .product { border: 1px solid #ddd; margin-bottom: 16px; padding: 12px; page-break-inside: avoid; }
        .product-name { font-size: 14px; font-weight: bold; margin-bottom: 6px; }
        .product-brand { color: #666; font-size: 11px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th, td { padding: 4px 6px; border: 1px solid #eee; text-align: left; }
        th { background: #f8f8f8; }
        .price { color: #1a6e1a; font-weight: bold; }
        .no-price { color: #999; }
        .page-title { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="page-title">
        <h1>Catálogo de Produtos</h1>
        <div class="meta">Gerado em {{ now()->format('d/m/Y H:i') }} — {{ $products->count() }} produto(s)</div>
    </div>

    @forelse($products as $product)
    <div class="product">
        <div class="product-name">{{ $product->name }}</div>
        @if($product->brand)
        <div class="product-brand">{{ $product->brand->name }}</div>
        @endif

        <table>
            <tr>
                <th>Código</th>
                <td>{{ $product->code ?? '—' }}</td>
                <th>Tipo</th>
                <td>{{ $product->type?->value ?? '—' }}</td>
                <th>Status</th>
                <td>{{ $product->status?->value ?? '—' }}</td>
            </tr>
            @if($includePrices)
            <tr>
                <th>Preço Base</th>
                <td class="{{ $product->base_price_cents ? 'price' : 'no-price' }}">
                    {{ $product->base_price_cents
                        ? 'R$ ' . number_format($product->base_price_cents / 100, 2, ',', '.')
                        : '—' }}
                </td>
                <th>Custo</th>
                <td class="{{ $product->cost_price_cents ? 'price' : 'no-price' }}">
                    {{ $product->cost_price_cents
                        ? 'R$ ' . number_format($product->cost_price_cents / 100, 2, ',', '.')
                        : '—' }}
                </td>
                <th>Em Promoção</th>
                <td>{{ $product->is_on_sale ? 'Sim' : 'Não' }}</td>
            </tr>
            @endif
        </table>

        @if($product->variants->isNotEmpty())
        <table style="margin-top: 8px;">
            <tr>
                <th>SKU</th>
                <th>Variante</th>
                @if($includePrices)<th>Preço</th>@endif
            </tr>
            @foreach($product->variants as $variant)
            <tr>
                <td>{{ $variant->sku }}</td>
                <td>{{ $variant->name }}</td>
                @if($includePrices)
                <td class="price">R$ {{ number_format($variant->price_cents / 100, 2, ',', '.') }}</td>
                @endif
            </tr>
            @endforeach
        </table>
        @endif
    </div>
    @empty
    <p style="text-align: center; color: #999; padding: 40px;">Nenhum produto encontrado.</p>
    @endforelse
</body>
</html>
