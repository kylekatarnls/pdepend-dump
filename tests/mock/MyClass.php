<?php

declare(strict_types=1);

namespace Foo;

use Countable;

final class MyClass implements Countable
{
    private int $count;

    /**
     * @var string
     */
    private $text;

    public function __construct(int $count)
    {
        $this->count = $count;
    }

    public function count(): int
    {
        return $this->count;
    }
}
