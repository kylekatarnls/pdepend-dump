<?php

declare(strict_types=1);

namespace Tests\PDependDump;

use PDepend\Util\Cache\Driver\MemoryCacheDriver;

class TestCacheDriver extends MemoryCacheDriver
{
    public function clear(): void
    {
        $this->cache = [];
    }
}
