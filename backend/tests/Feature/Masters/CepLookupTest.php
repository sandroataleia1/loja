<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('GET /addresses/cep/{zipcode}', function (): void {
    it('retorna endereço para CEP válido', function (): void {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'logradouro' => 'Avenida Paulista',
                'bairro'     => 'Bela Vista',
                'localidade' => 'São Paulo',
                'uf'         => 'SP',
                'ibge'       => '3550308',
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/addresses/cep/01310-100');

        $response->assertOk()
            ->assertJsonPath('data.zip_code', '01310100')
            ->assertJsonPath('data.street', 'Avenida Paulista')
            ->assertJsonPath('data.city', 'São Paulo')
            ->assertJsonPath('data.state', 'SP');
    });

    it('retorna 422 para CEP inválido', function (): void {
        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 200),
        ]);

        $response = $this->getJson('/api/v1/addresses/cep/00000000');

        $response->assertUnprocessable();
    });

    it('retorna 503 quando ViaCEP está indisponível', function (): void {
        Http::fake([
            'viacep.com.br/*' => Http::response(null, 503),
        ]);

        $response = $this->getJson('/api/v1/addresses/cep/01310100');

        $response->assertStatus(503);
    });

    it('usa cache para CEP já consultado', function (): void {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'logradouro' => 'Rua das Flores',
                'bairro'     => 'Centro',
                'localidade' => 'Curitiba',
                'uf'         => 'PR',
                'ibge'       => '4106902',
            ], 200),
        ]);

        // Garante cache limpo
        Cache::forget('viacep:04538133');

        $this->getJson('/api/v1/addresses/cep/04538-133')->assertOk();
        $this->getJson('/api/v1/addresses/cep/04538-133')->assertOk();

        // ViaCEP deve ter sido chamado apenas uma vez
        Http::assertSentCount(1);
    });

    it('rejeita CEP com formato inválido (menos de 8 dígitos)', function (): void {
        $response = $this->getJson('/api/v1/addresses/cep/123');

        $response->assertUnprocessable();
    });
});
