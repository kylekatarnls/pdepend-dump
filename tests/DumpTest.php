<?php

declare(strict_types=1);

use PDepend\Source\Language\PHP\PHPBuilder;
use PDepend\Util\Cache\Driver\MemoryCacheDriver;
use PDependDump\Dump;

test('has list command', function () {
    $cache = new MemoryCacheDriver();
    $builder = new PHPBuilder();

    $dump = new Dump($cache, $builder);

    foreach ($dump->chunks(__DIR__ . '/mock/MyClass.php') as $chunk) {
        echo $chunk;
    }
    exit;
});
