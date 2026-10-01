<?php

namespace App\LLM;

interface ChatModelInterface
{
    /**
     * $system = instructies voor het model (hoe moet het zich gedragen)
     * $user   = het eigenlijke bericht (context + vraag)
     */
    public function complete(string $system, string $user): string;
}
