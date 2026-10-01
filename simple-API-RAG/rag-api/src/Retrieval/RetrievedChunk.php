<?php

namespace App\Retrieval;

final readonly class RetrievedChunk
{
    public function __construct(
        public string $content,  // de tekst
        public string $source,   // uit welk bestand
        public int $chunkIndex,  // welk stuk van dat bestand
        public float $score,     // hoe goed het past (hoger = beter)
    ) {
    }
}
