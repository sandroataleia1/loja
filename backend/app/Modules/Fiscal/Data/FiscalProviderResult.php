<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Data;

use App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum;

/**
 * Resultado normalizado de uma operação no provedor fiscal.
 *
 * Independente do provedor (Stub, Focus NFe, etc.), o resultado é sempre
 * este DTO. O caller decide o que fazer com base nos campos.
 */
final readonly class FiscalProviderResult
{
    public function __construct(
        /** Status resultante a ser aplicado no FiscalDocument. */
        public FiscalDocumentStatusEnum $status,
        /** Chave de acesso 44 dígitos (null se ainda não autorizado). */
        public ?string                  $accessKey,
        /** Protocolo de autorização SEFAZ. */
        public ?string                  $protocol,
        /** Número da nota atribuído pelo provedor ou SEFAZ. */
        public ?int                     $number,
        /** Caminho do XML gerado (file path ou URL). */
        public ?string                  $xmlPath,
        /** Caminho do PDF DANFE gerado. */
        public ?string                  $pdfPath,
        /** Código de status HTTP ou código interno do provedor. */
        public ?int                     $statusCode,
        /** Mensagem legível retornada pelo provedor/SEFAZ. */
        public ?string                  $message,
        /** Payload raw completo da resposta (para FiscalResponse). */
        public array                    $rawResponse = [],
    ) {}

    public static function authorized(
        string  $accessKey,
        string  $protocol,
        int     $number,
        ?string $xmlPath     = null,
        ?string $pdfPath     = null,
        array   $rawResponse = [],
    ): self {
        return new self(
            status:      FiscalDocumentStatusEnum::Authorized,
            accessKey:   $accessKey,
            protocol:    $protocol,
            number:      $number,
            xmlPath:     $xmlPath,
            pdfPath:     $pdfPath,
            statusCode:  100,
            message:     'Documento autorizado.',
            rawResponse: $rawResponse,
        );
    }

    public static function rejected(string $message, int $statusCode, array $rawResponse = []): self
    {
        return new self(
            status:      FiscalDocumentStatusEnum::Rejected,
            accessKey:   null,
            protocol:    null,
            number:      null,
            xmlPath:     null,
            pdfPath:     null,
            statusCode:  $statusCode,
            message:     $message,
            rawResponse: $rawResponse,
        );
    }

    public static function contingency(string $message = 'SEFAZ indisponível.', array $rawResponse = []): self
    {
        return new self(
            status:      FiscalDocumentStatusEnum::Contingency,
            accessKey:   null,
            protocol:    null,
            number:      null,
            xmlPath:     null,
            pdfPath:     null,
            statusCode:  503,
            message:     $message,
            rawResponse: $rawResponse,
        );
    }

    public static function cancelled(string $protocol, array $rawResponse = []): self
    {
        return new self(
            status:      FiscalDocumentStatusEnum::Cancelled,
            accessKey:   null,
            protocol:    $protocol,
            number:      null,
            xmlPath:     null,
            pdfPath:     null,
            statusCode:  135,
            message:     'Cancelamento homologado.',
            rawResponse: $rawResponse,
        );
    }

    public function isAuthorized(): bool
    {
        return $this->status === FiscalDocumentStatusEnum::Authorized;
    }

    public function isRejected(): bool
    {
        return $this->status === FiscalDocumentStatusEnum::Rejected;
    }

    public function isContingency(): bool
    {
        return $this->status === FiscalDocumentStatusEnum::Contingency;
    }
}
