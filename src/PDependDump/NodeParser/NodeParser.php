<?php

declare(strict_types=1);

namespace PDependDump\NodeParser;

interface NodeParser
{
    public function parse($node): iterable;
}
