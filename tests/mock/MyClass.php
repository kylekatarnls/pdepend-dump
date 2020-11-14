<?php

declare(strict_types=1);

namespace Foo;

use Countable;

final class MyClass implements Countable
{
    private int $count;

    public function __construct(int $count)
    {
        $this->count = $count;
    }

    public function count(): int
    {
        return $this->count;
    }
}
