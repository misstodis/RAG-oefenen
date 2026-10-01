<?php

namespace App\VectorStore;

use Symfony\AI\Store\InMemory\Store;

final class InMemoryVector
{
    public static function create(): Store
    {
        return new Store();
    }
}
