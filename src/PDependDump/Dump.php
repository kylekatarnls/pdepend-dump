<?php

declare(strict_types=1);

namespace PDependDump;

use InvalidArgumentException;
use PDepend\Source\AST\AbstractASTArtifact;
use PDepend\Source\AST\ASTArtifact;
use PDepend\Source\AST\ASTClassOrInterfaceReference;
use PDepend\Source\AST\ASTNamespace;
use PDepend\Source\AST\ASTNode;
use PDepend\Source\Builder\Builder;
use PDepend\Source\Language\PHP\PHPParserGeneric;
use PDepend\Source\Language\PHP\PHPTokenizerInternal;
use PDepend\Util\Cache\CacheDriver;
use PDependDump\NodeParser\ASTNamespaceParser;
use PDependDump\NodeParser\NodeParser;

final class Dump
{
    private CacheDriver $cache;

    private Builder $builder;

    private $indent = '  ';

    private $endOfLine = PHP_EOL;

    /**
     * @var string[]
     * @psalm-var array<class-string<ASTArtifact>, class-string<NodeParser>>
     */
    private $parsers = [
        ASTNamespace::class => ASTNamespaceParser::class,
    ];

    /**
     * @var array
     * @psalm-var array<class-string<NodeParser>, NodeParser>
     */
    private $parsersCache = [];

    public function __construct(CacheDriver $cache, Builder $builder)
    {
        $this->cache = $cache;
        $this->builder = $builder;
    }

    /**
     * Set the indentation style.
     *
     * @param string|int $indent a string to use as indentation or a number of spaces.
     */
    public function setIndent($indent): void
    {
        if (is_int($indent)) {
            $indent = str_repeat(' ', $indent);
        }

        $this->indent = $indent;
    }

    /**
     * Set the end of line style.
     *
     * @param string $endOfLine such as "\r\n" or "\n"
     */
    public function setEndOfLine(string $endOfLine): void
    {
        $this->endOfLine = $endOfLine;
    }

    /**
     * Change the list of the custom parsers.
     *
     * @param string[] $parsers
     * @psalm-param array<class-string<ASTArtifact>, class-string<NodeParser>> $parsers
     */
    public function setParsers(array $parsers): void
    {
        foreach ($parsers as $node => $parser) {
            if ($parser instanceof NodeParser) {
                continue;
            }

            throw new InvalidArgumentException(
                "Parser for $node is not a " . NodeParser::class,
            );
        }

        $this->parsers = $parsers;
    }

    /**
     * @return string[]
     * @psalm-return array<class-string<ASTArtifact>, class-string<NodeParser>>
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * @param string $nodeClass
     * @psalm-param class-string<ASTArtifact> $nodeClass
     *
     * @return string|null
     * @psalm-return class-string<NodeParser>|null
     */
    public function getParserClass(string $nodeClass): ?string
    {
        return $this->parsers[$nodeClass] ?? null;
    }

    /**
     * @param string $nodeClass
     * @psalm-param class-string<ASTArtifact> $nodeClass
     *
     * @return NodeParser|null
     * @psalm-return NodeParser
     */
    public function getParser(string $nodeClass): ?NodeParser
    {
        $parserClass = $this->getParserClass($nodeClass);

        if (!$parserClass) {
            return null;
        }

        if (!isset($this->parsersCache[$parserClass])) {
            $this->parsersCache[$parserClass] = new $parserClass();
        }

        return $this->parsersCache[$parserClass];
    }

    /**
     * Iterate over the namespaces.
     *
     * @param string $file
     * @param bool   $ignoreAnnotations
     *
     * @return Builder|ASTNamespace[]
     */
    public function parse(string $file, bool $ignoreAnnotations = false): Builder
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
            foreach ($this->nodeAsLines($node) as $line) {
                yield $line;
            }
        }
    }

    public function isNode($node): bool
    {
        return $node instanceof ASTNode || $node instanceof ASTArtifact;
    }

    /**
     * Throw an exception if given node is neither ASTNode nor ASTArtifact.
     *
     * @param ASTNode|ASTArtifact $node
     */
    public function assertNode($node): void
    {
        if (!$this->isNode($node)) {
            throw new InvalidArgumentException(
                'Given ' . (is_object($node) ? get_class($node) : gettype($node)) .
                    ' is neither ' . ASTNode::class . ' nor ' . ASTArtifact::class,
            );
        }
    }

    /**
     * Get a string representation of the given node either an ASTNode or an ASTArtifact.
     *
     * @param ASTNode|ASTArtifact $node
     *
     * @return string
     */
    public function getImage($node): string
    {
        return $node instanceof ASTNode || $node instanceof AbstractASTArtifact ? $node->getImage() : $node->getName();
    }

    /**
     * Iterate over dumps line by line parsed from a given node either an ASTNode or an ASTArtifact.
     *
     * @param ASTNode|ASTArtifact $node
     * @param int                 $indent
     *
     * @return iterable|string[]
     */
    public function nodeAsLines($node, int $indent = 0): iterable
    {
        $class = get_class($node);
        $type = lcfirst(preg_replace('/^PDepend\\\\Source\\\\AST\\\\AST/', '', $class));

        yield $this->line($indent, $type . ': ' . $this->getImage($node));

        $parser = $this->getParser($class);

        if ($parser) {
            foreach ($parser->parse($node) as $subIndent => $line) {
                if ($this->isNode($line)) {
                    yield from $this->nodeAsLines($line, $subIndent + $indent);

                    continue;
                }

                yield $this->line($subIndent + $indent, (string) $line);
            }

            return;
        }

        if (!method_exists($node, 'getChildren')) {
            return;
        }

        foreach ($node->getChildren() as $child) {
            foreach ($this->nodeAsLines($child, $indent + 1) as $line) {
                yield $line;
            }
        }
    }

    public function dumpNode($node, int $indent = 0): string
    {
        $this->assertNode($node);

        return $this->iterableToString($this->nodeAsLines($node, $indent));
    }

    public function dump(string $file, bool $ignoreAnnotations = false): string
    {
        return $this->iterableToString($this->chunks($file, $ignoreAnnotations));
    }

    private function iterableToString(iterable $chunks): string
    {
        $output = '';

        foreach ($chunks as $chunk) {
            $output .= $chunk;
        }

        return $output;
    }

    private function line(int $indent, string $text): string
    {
        return str_repeat($this->indent, $indent) . $text . $this->endOfLine;
    }
}
