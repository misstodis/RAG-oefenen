<?php

namespace App\Ingest;

final class TextChunker
{
    public function __construct(
        private int $chunkWords = 200,   // woorden per chunk
        private int $overlapWords = 40,  // woorden overlap met de vorige chunk
    ) {
    }

    /** @return string[] */
    public function chunk(string $text): array
    {
        // Splits de tekst op spaties, enters en tabs in losse woorden
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $total = count($words);

        // Elke nieuwe chunk begint 160 woorden verder (200 - 40)
        $step = $this->chunkWords - $this->overlapWords;

        $chunks = [];
        for ($start = 0; $start < $total; $start += $step) {
            $piece = array_slice($words, $start, $this->chunkWords);
            $chunks[] = implode(' ', $piece);

            // Laatste stuk bereikt? Dan stoppen
            if ($start + $this->chunkWords >= $total) {
                break;
            }
        }

        return $chunks;
    }
}
