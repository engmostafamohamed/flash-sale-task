<?php

namespace App\Repositories\Contracts;

use App\Models\Hold;
use Illuminate\Support\Collection;

interface HoldRepositoryInterface
{
    public function create(int $productId, int $quantity, int $expiryMinutes): Hold;

    public function find(int $id): ?Hold;

    public function findWithLock(int $id): ?Hold;

    public function markAsUsed(int $holdId): bool;

    public function markAsReleased(int $holdId): void;

    public function getExpiredHolds(): Collection;
}
