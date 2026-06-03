<?php

/*
 * This file is part of the Fidry CPUCounter Config package.
 *
 * (c) Théo FIDRY <theo.fidry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fidry\CpuCoreCounter\Finder;

use const PHP_EOL;

use function count;
use function explode;
use function is_array;
use function preg_grep;

/**
 * The number of logical cores.
 *
 * @see https://stackoverflow.com/a/23378780/5846754
 */
final class LscpuLogicalFinder extends ProcOpenBasedFinder
{
    public function getCommand(): string
    {
        return 'lscpu -p';
    }

    protected function countCpuCores(string $process): ?int
    {
        $lines = explode(PHP_EOL, $process);
        $actualLines = preg_grep('/^\d+,/', $lines);

        if (! is_array($actualLines)) {
            return null;
        }

        $count = count($actualLines);

        return $count === 0 ? null : $count;
    }

    public function toString(): string
    {
        return 'LscpuLogicalFinder';
    }
}
