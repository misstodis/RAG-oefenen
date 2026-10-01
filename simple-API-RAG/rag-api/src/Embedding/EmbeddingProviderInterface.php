<?php

namespace App\Embedding;

interface EmbeddingProviderInterface
{
    public function embed(array $texts): array;
}
