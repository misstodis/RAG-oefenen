<?php

namespace App\Rag;
use App\Retrieval\RetrievedChunk;

final readonly class RagAnswer
{
    /** @param RetrievedChunk[] $sources */
    public function __construct(
        public string $answer,
        public array $sources,
    ) {
    }
}
