<?php

declare(strict_types=1);

use PDepend\Source\AST\ASTNamespace;
use PDepend\Source\AST\ASTStatement;
use PDepend\Source\AST\ASTVariable;
use PDepend\Source\Language\PHP\PHPBuilder;
use PDependDump\Dump;
use PDependDump\Exception\InvalidArgumentException;
use PDependDump\NodeParser\ASTNamespaceParser;
use Tests\PDependDump\TestCacheDriver;
use function Tests\expectToMatchFile;

it('can dump class', function () {
    $cache = new TestCacheDriver();
    $builder = new PHPBuilder();

    $dump = new Dump($cache, $builder);

    expectToMatchFile(
        $dump->dump(__DIR__ . '/mock/MyClass.php'),
        __DIR__ . '/mock/MyClass.dump',
    );

    $cache->clear();

    expectToMatchFile(
        $dump->dump(__DIR__ . '/mock/MyClass.php', true),
        __DIR__ . '/mock/MyClass-no-annotations.dump',
    );

    $cache->clear();

    $dump->setIndent(4);
    $dump->setEndOfLine("↵\n");

    expectToMatchFile(
        $dump->dump(__DIR__ . '/mock/MyClass.php'),
        __DIR__ . '/mock/MyClass-indent.dump',
    );
});

it('can dumps a given node', function () {
    $cache = new TestCacheDriver();
    $builder = new PHPBuilder();

    $dump = new Dump($cache, $builder);

    expect(trim($dump->dumpNode(new ASTVariable('$fooBar'))))->toBe('variable: $fooBar');
});

it('forbid to dump an non-PDepend node', function () {
    $cache = new TestCacheDriver();
    $builder = new PHPBuilder();

    $dump = new Dump($cache, $builder);

    $dump->dumpNode(new stdClass());
})->throws(
    InvalidArgumentException::class,
    'Given stdClass is neither PDepend\Source\AST\ASTNode nor PDepend\Source\AST\ASTArtifact',
);

it('can change of parsers', function () {
    $cache = new TestCacheDriver();
    $builder = new PHPBuilder();

    $dump = new Dump($cache, $builder);

    $dump->setParsers([]);

    expect($dump->getParsers())->toBe([]);

    $parsers = [
        ASTNamespace::class => ASTNamespaceParser::class,
    ];
    $dump->setParsers($parsers);

    expect($dump->getParsers())->toBe($parsers);

    $parsers = [
        ASTNamespace::class => new ASTNamespaceParser(),
    ];
    $dump->setParsers($parsers);

    expect($dump->getParser(ASTNamespace::class))->toBe($parsers[ASTNamespace::class]);
});

it('forbids invalid parsers', function () {
    $cache = new TestCacheDriver();
    $builder = new PHPBuilder();

    $dump = new Dump($cache, $builder);

    $dump->setParsers([
        ASTNamespace::class => ASTNamespace::class,
    ]);
})->throws(
    InvalidArgumentException::class,
    'Parser for PDepend\Source\AST\ASTNamespace is not a PDependDump\NodeParser\NodeParser',
);

it('can iterate over node children', function () {
    $cache = new TestCacheDriver();
    $builder = new PHPBuilder();

    $dump = new Dump($cache, $builder);

    $child = new ASTVariable('$fooBar');
    $statement = new ASTStatement('if ($fooBar)');
    $statement->addChild($child);

    expect(iterator_to_array($dump->getChildren($statement)))->toBe([$child]);
    expect(iterator_to_array($dump->getChildren(new stdClass())))->toBe([]);
});
