<?php

declare(strict_types=1);

use PDepend\Source\Language\PHP\PHPBuilder;
use PDepend\Util\Cache\Driver\MemoryCacheDriver;
use PDependDump\Dump;
use function Tests\clean;

test('has list command', function () {
    $cache = new MemoryCacheDriver();
    $builder = new PHPBuilder();

    $dump = new Dump($cache, $builder);

    expect(clean($dump->dump(__DIR__ . '/mock/MyClass.php')))
        ->toBe(file_get_contents(__DIR__ . '/mock/MyClass.dump'));
});
