<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

interface ActionInterface
{
    public function execute(): mixed;
}
