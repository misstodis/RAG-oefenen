<?php

namespace App\Controller;

use App\Dto\AskRequest;
use App\Rag\RagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class AskController extends AbstractController
{
    #[Route('/api/ask', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] AskRequest $request,
        RagService $rag,
    ): JsonResponse {
        $start = microtime(true);

        $result = $rag->ask($request->question, $request->k, $request->hybrid);

        // Zet de bronnen om in simpele arrays voor de JSON
        $sources = [];
        foreach ($result->sources as $i => $chunk) {
            $sources[] = [
                'nummer'  => $i + 1,
                'bron'    => $chunk->source,
                'score'   => round($chunk->score, 4),
                'fragment' => mb_substr($chunk->content, 0, 200),
            ];
        }

        return $this->json([
            'antwoord' => $result->answer,
            'bronnen'  => $sources,
            'duur_ms'  => (int) ((microtime(true) - $start) * 1000),
        ]);
    }
}
