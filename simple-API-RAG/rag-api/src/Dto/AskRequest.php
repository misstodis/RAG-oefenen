<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class AskRequest
{
    #[Assert\NotBlank(message: 'Stel een vraag.')]
    #[Assert\Length(max: 1000)]
    public string $question = '';

    #[Assert\Range(min: 1, max: 20)]
    public int $k = 5;

    public bool $hybrid = true;
}
