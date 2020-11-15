<?php

declare(strict_types=1);

namespace PDependDump\NodeParser;

use PDepend\Source\AST\ASTNamespace;
use SplObjectStorage;

class ASTNamespaceParser implements NodeParser
{
    public function parse($node): iterable
    {
        return $this->parseNamespace($node);
    }

    protected function parseNamespace(ASTNamespace $namespace): iterable
    {
        $unique = new SplObjectStorage();
        yield from $this->getChildren('interfaces', $namespace->getInterfaces(), $unique);
        yield from $this->getChildren('functions', $namespace->getFunctions(), $unique);
        yield from $this->getChildren('classes', $namespace->getClasses(), $unique);
        yield from $this->getChildren('traits', $namespace->getTraits(), $unique);
        yield from $this->getChildren('types', $namespace->getTypes(), $unique);
    }

    protected function getChildren(string $name, iterable $children, SplObjectStorage $unique): iterable
    {
        $first = true;

        foreach ($children as $child) {
            if (isset($unique[$child])) {
                continue;
            }

            if ($first) {
                yield 1 => $name . ':';

                $first = false;
            }

            $unique[$child] = true;

            yield 2 => $child;
        }
    }
}
