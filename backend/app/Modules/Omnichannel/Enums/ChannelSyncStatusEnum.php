<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Enums;

enum ChannelSyncStatusEnum: string
{
    case Pending  = 'pending';   // awaiting publication job
    case Synced   = 'synced';    // confirmed published on the channel
    case Failed   = 'failed';    // publication job failed
    case Outdated = 'outdated';  // product changed locally, channel not yet updated

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Aguardando publicação',
            self::Synced   => 'Publicado',
            self::Failed   => 'Falha na publicação',
            self::Outdated => 'Desatualizado',
        };
    }

    public function needsSync(): bool
    {
        return in_array($this, [self::Pending, self::Failed, self::Outdated], true);
    }
}
