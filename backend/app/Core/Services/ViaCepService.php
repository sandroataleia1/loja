<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Exceptions\ZipCodeNotFoundException;
use App\Core\Exceptions\ZipCodeServiceUnavailableException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ViaCepService
{
    private const TTL = 86400; // 24h — CEP raramente muda

    public function lookup(string $zipcode): array
    {
        $digits = preg_replace('/\D/', '', $zipcode);

        if (strlen($digits) !== 8) {
            throw new ZipCodeNotFoundException($zipcode);
        }

        return Cache::remember("viacep:{$digits}", self::TTL, function () use ($digits): array {
            return $this->fetchFromApi($digits);
        });
    }

    private function fetchFromApi(string $digits): array
    {
        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$digits}/json/");
        } catch (Throwable $e) {
            Log::warning('ViaCEP unavailable', ['cep' => $digits, 'error' => $e->getMessage()]);
            throw new ZipCodeServiceUnavailableException();
        }

        if ($response->failed()) {
            throw new ZipCodeServiceUnavailableException();
        }

        $data = $response->json();

        if (isset($data['erro']) && $data['erro'] === true) {
            throw new ZipCodeNotFoundException($digits);
        }

        return [
            'zip_code'     => $digits,
            'street'       => $data['logradouro'] ?? null,
            'neighborhood' => $data['bairro']     ?? null,
            'city'         => $data['localidade'] ?? null,
            'state'        => $data['uf']          ?? null,
            'ibge_code'    => $data['ibge']        ?? null,
        ];
    }
}
