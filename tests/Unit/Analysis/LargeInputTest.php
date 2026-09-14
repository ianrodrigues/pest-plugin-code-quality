<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Analysis\AstMeasurer;

/**
 * Writes `$classCount` synthetic classes, each with `$methodsPerClass`
 * methods, into `$directory`. Every method has a small amount of branching
 * so the measurer does real work rather than trivially fast-pathing empty
 * bodies.
 */
function generateSyntheticClasses(string $directory, int $classCount, int $methodsPerClass): void
{
    for ($classIndex = 0; $classIndex < $classCount; $classIndex++) {
        $className = "GeneratedClass{$classIndex}";
        $methods = '';

        for ($methodIndex = 0; $methodIndex < $methodsPerClass; $methodIndex++) {
            $methods .= <<<PHP

                    public function method{$methodIndex}(int \$value, ?string \$label = null): string
                    {
                        if (\$value > 0) {
                            \$value++;
                        } elseif (\$value < 0) {
                            \$value--;
                        }

                        return (\$label ?? 'default') . ':' . \$value;
                    }

                PHP;
        }

        $source = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Rdgs\PestCodeQuality\Tests\Generated;

            final class {$className}
            {
            {$methods}}

            PHP;

        file_put_contents("{$directory}/{$className}.php", $source);
    }
}

it('measures a laravel/framework-sized input without errors', function (): void {
    $classCount = 200;
    $methodsPerClass = 10;

    $directory = sys_get_temp_dir() . '/pest-quality-large-input-' . uniqid();
    mkdir($directory);

    try {
        generateSyntheticClasses($directory, $classCount, $methodsPerClass);

        $files = glob("{$directory}/*.php") ?: [];
        expect($files)->toHaveCount($classCount);

        $measurer = new AstMeasurer();

        $start = hrtime(true);
        $totalMethods = 0;

        foreach ($files as $file) {
            $totalMethods += count($measurer->measure($file));
        }

        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        fwrite(STDERR, sprintf(
            "\nLargeInputTest: measured %d methods across %d files in %.2f ms\n",
            $totalMethods,
            count($files),
            $elapsedMs,
        ));

        expect($totalMethods)->toBe($classCount * $methodsPerClass);
    } finally {
        foreach (glob("{$directory}/*.php") ?: [] as $file) {
            unlink($file);
        }

        rmdir($directory);
    }
});
