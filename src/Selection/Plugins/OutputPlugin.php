<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Selection\Plugins;

use Pest\Contracts\Plugins\AddsOutput;
use Rdgs\PestCodeQuality\Selection\SkippedFile;
use Rdgs\PestCodeQuality\Selection\WarningsCollector;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints every skipped file gathered across the whole process, once, after
 * the run finishes. Registered in `composer.json`'s `extra.pest.plugins`.
 */
final class OutputPlugin implements AddsOutput
{
    private const int LINE_CAP = 10;

    public function __construct(
        private readonly OutputInterface $output,
    ) {
    }

    public function addOutput(int $exitCode): int
    {
        $files = WarningsCollector::all();

        if ($files === []) {
            return $exitCode;
        }

        $this->output->writeln('');
        $this->output->writeln(sprintf(
            'Quality: %d files were found but not analysed (run with --quality-inspect for details)',
            count($files),
        ));

        foreach (array_slice($files, 0, self::LINE_CAP) as $file) {
            $this->output->writeln($this->line($file));
        }

        return $exitCode;
    }

    private function line(SkippedFile $file): string
    {
        return sprintf('  %s (%s)', $file->path, $file->reason->label());
    }
}
