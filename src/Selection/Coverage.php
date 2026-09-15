<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Selection;

/**
 * The completeness accounting for one policy run, across every target it
 * touched.
 */
final readonly class Coverage
{
    /**
     * @param list<TargetCoverage> $targets
     */
    public function __construct(
        public array $targets,
    ) {
    }

    public function filesFound(): int
    {
        return $this->sum(static fn (TargetCoverage $target): int => $target->filesFound);
    }

    public function objectsProduced(): int
    {
        return $this->sum(static fn (TargetCoverage $target): int => $target->objectsProduced);
    }

    public function objectsWithAst(): int
    {
        return $this->sum(static fn (TargetCoverage $target): int => $target->objectsWithAst);
    }

    public function eligibleMethods(): int
    {
        return $this->sum(static fn (TargetCoverage $target): int => $target->eligibleMethods);
    }

    public function withoutClasses(): int
    {
        return $this->sum(static fn (TargetCoverage $target): int => $target->withoutClasses);
    }

    /**
     * Every skipped file across every target, deduplicated by path since
     * overlapping targets can find the same file twice.
     *
     * @return list<SkippedFile>
     */
    public function skippedFiles(): array
    {
        $files = [];

        foreach ($this->targets as $target) {
            foreach ($target->skipped as $skipped) {
                $files[$skipped->path] ??= $skipped;
            }
        }

        return array_values($files);
    }

    /**
     * @param callable(TargetCoverage): int $value
     */
    private function sum(callable $value): int
    {
        return array_sum(array_map($value, $this->targets));
    }
}
