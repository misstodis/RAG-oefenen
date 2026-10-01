<?php

namespace App\Rag;

use App\LLM\ChatModelInterface;
use App\Retrieval\Retriever;

final class RagService
{
    // De instructies voor het model. Dit is je belangrijkste knop om aan te draaien!
    private const SYSTEM_PROMPT = <<<TXT
        Je bent een behulpzame assistent die vragen beantwoordt over documenten.
        Regels:
        - Gebruik ALLEEN de informatie uit de context hieronder.
        - Staat het antwoord niet in de context? Zeg dan: "Dat kan ik niet vinden in de documenten."
        - Verwijs naar je bronnen met [1], [2], enzovoort.
        - Antwoord in dezelfde taal als de vraag.
        TXT;

    public function __construct(
        private Retriever $retriever,
        private ChatModelInterface $llm,
    ) {
    }

    public function ask(string $question, int $k = 5, bool $hybrid = true): RagAnswer
    {
        // 1. Zoek de beste chunks
        $chunks = $this->retriever->search($question, $k, $hybrid);

        // 2. Maak er genummerde context van
        $context = '';
        foreach ($chunks as $i => $chunk) {
            $number = $i + 1;
            $context .= "[{$number}] (bron: {$chunk->source})\n{$chunk->content}\n\n";
        }

        // 3. Zet context en vraag in één bericht
        $userMessage = "Context:\n\n{$context}Vraag: {$question}";

        // 4. Laat het model antwoorden
        $answer = $this->llm->complete(self::SYSTEM_PROMPT, $userMessage);

        return new RagAnswer($answer, $chunks);
    }
}
