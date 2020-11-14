<?php

declare(strict_types=1);

namespace PDependDump;

use PDepend\Source\Builder\Builder;
use PDepend\Source\Language\PHP\PHPParserGeneric;
use PDepend\Source\Language\PHP\PHPTokenizerInternal;
use PDepend\Util\Cache\CacheDriver;

final class Dump
{
    private CacheDriver $cache;

    private Builder $builder;

    public function __construct(CacheDriver $cache, Builder $builder)
    {
        $this->cache = $cache;
        $this->builder = $builder;
    }

    public function parse(string $file, bool $ignoreAnnotations = false): iterable
    {
        $tokenizer = new PHPTokenizerInternal();
        $tokenizer->setSourceFile($file);

        $parser = new PHPParserGeneric(
            $tokenizer,
            $this->builder,
            $this->cache,
        );

        if ($ignoreAnnotations === true)
        {
            $parser->setIgnoreAnnotations();
        }

        $parser->parse();

        return $this->builder;
    }

    public function chunks(string $file, bool $ignoreAnnotations = false): iterable
    {
        foreach ($this->parse($file, $ignoreAnnotations) as $node) {
            yield $this->dumpNode($node);
        }
    }

    public function dumpNode($node): string
    {
        return get_class($node) . "\n";
    }

    public function dump(string $file, bool $ignoreAnnotations = false): string
    {
        $output = '';

        foreach ($this->chunks($file, $ignoreAnnotations) as $chunk) {
            $output .= $chunk;
        }

        return $output;
    }
}
