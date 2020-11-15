<?php

declare(strict_types=1);

namespace Tests;

/**
 * Clean dump imprecision that can be safely ignored.
 *
 * @param string $dump
 *
 * @return string
 */
function clean(string $dump): string
{
    $dump = preg_replace('/(fieldDeclaration:)\s+$/m', '$1', $dump);
    $dump = str_replace("\r", '', $dump);

    return $dump;
}
