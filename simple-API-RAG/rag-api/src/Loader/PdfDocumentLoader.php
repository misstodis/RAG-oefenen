<?php

namespace App\Loader;

use Smalot\PdfParser\Parser;
use Symfony\AI\Store\Document\LoaderInterface;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\Component\Uid\Uuid;

class PdfDocumentLoader implements LoaderInterface
{
    public function load(?string $source = null, array $options = []): iterable
    {
        if (!$source || !file_exists($source)) {
            throw new \InvalidArgumentException('Source file does not exist.');
        }

        $parser = new Parser();
        $pdf = $parser->parseFile($source);

        yield new TextDocument(Uuid::v4(), trim($pdf->getText()), new Metadata([
            Metadata::KEY_SOURCE => basename($source),
        ]));
    }
}
