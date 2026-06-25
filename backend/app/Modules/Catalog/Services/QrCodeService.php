<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final readonly class QrCodeService
{
    /**
     * Gera um QR Code e salva em storage.
     * Usa simplesoftwareio/simple-qrcode se disponível; GD nativo como fallback;
     * Google Charts API como fallback final.
     *
     * @return string URL pública do arquivo gerado
     */
    public function generate(string $content, string $storagePath, int $size = 200): string
    {
        // Tenta simplesoftwareio/simple-qrcode
        if (class_exists(\SimpleSoftwareIO\QrCode\Generator::class)) {
            return $this->generateWithLibrary($content, $storagePath, $size);
        }

        // Fallback: Google Charts QR Code API
        return $this->generateViaGoogleCharts($content, $storagePath, $size);
    }

    public function generateForProduct(Product $product): string
    {
        $content = json_encode([
            'sku'    => $product->code,
            'id'     => $product->uuid,
            'tenant' => $product->tenant_id,
        ], JSON_THROW_ON_ERROR);

        $path = "qrcodes/{$product->tenant_id}/{$product->uuid}.png";
        $url  = $this->generate($content, $path);

        $product->updateQuietly(['qr_code_url' => $url]);

        return $url;
    }

    public function generateForVariant(Variant $variant): string
    {
        $content = json_encode([
            'sku'     => $variant->sku,
            'barcode' => $variant->barcode,
            'id'      => $variant->uuid,
        ], JSON_THROW_ON_ERROR);

        $path = "qrcodes/{$variant->tenant_id}/variant-{$variant->uuid}.png";
        $url  = $this->generate($content, $path);

        $variant->updateQuietly(['qr_code_url' => $url]);

        return $url;
    }

    private function generateWithLibrary(string $content, string $storagePath, int $size): string
    {
        /** @var \SimpleSoftwareIO\QrCode\Generator $generator */
        $generator = app(\SimpleSoftwareIO\QrCode\Generator::class);
        $png = $generator->format('png')->size($size)->generate($content);

        Storage::disk('public')->put($storagePath, $png);

        return Storage::disk('public')->url($storagePath);
    }

    private function generateViaGoogleCharts(string $content, string $storagePath, int $size): string
    {
        $response = Http::timeout(10)->get('https://chart.googleapis.com/chart', [
            'chs'  => "{$size}x{$size}",
            'cht'  => 'qr',
            'chl'  => $content,
            'choe' => 'UTF-8',
        ]);

        if ($response->successful()) {
            Storage::disk('public')->put($storagePath, $response->body());
            return Storage::disk('public')->url($storagePath);
        }

        // Se tudo falhar retorna URL direta do Google Charts sem armazenar
        return 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size
            . '&cht=qr&chl=' . urlencode($content) . '&choe=UTF-8';
    }
}
