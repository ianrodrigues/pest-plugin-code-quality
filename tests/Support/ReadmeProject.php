<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Support;

use RuntimeException;

/**
 * Runs the exact code published in README.md against a throwaway
 * project, so a doc sample can never silently drift. Wraps
 * `FixtureProject` instead of changing it: parsing fenced blocks is this class's job alone.
 */
final class ReadmeProject
{
    private const string PROJECT = 'Readme';

    private function __construct()
    {
    }

    public static function path(string $relative = ''): string
    {
        $path = FixtureProject::path(self::PROJECT);

        return $relative === '' ? $path : $path.'/'.$relative;
    }

    /**
     * @param list<string> $arguments arguments after `vendor/bin/pest`
     * @return array{exitCode: int, output: string}
     */
    public static function runPest(array $arguments = []): array
    {
        FixtureProject::ensureInstalled(self::PROJECT);

        return FixtureProject::runPest(['--colors=never', ...$arguments], self::PROJECT);
    }

    /**
     * Runs a documented `vendor/bin/pest ...` command line verbatim, as
     * copied from a README shell block.
     *
     * @return array{exitCode: int, output: string}
     */
    public static function runCommand(string $command): array
    {
        return self::runPest(self::commandArguments($command));
    }

    public static function write(string $relative, string $contents): void
    {
        $path = self::path($relative);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);
    }

    /**
     * Writes a README PHP sample as a runnable test file, wrapped with the
     * declaration header every fixture test carries.
     */
    public static function writeTest(string $body, string $file = 'tests/ArchTest.php'): void
    {
        self::write($file, "<?php\n\ndeclare(strict_types=1);\n\n{$body}\n");
    }

    /**
     * Clears everything the documentation tests write between scenarios,
     * so one tagged block never leaks into the next.
     */
    public static function reset(): void
    {
        foreach (glob(self::path('tests/*.php')) ?: [] as $file) {
            unlink($file);
        }

        foreach ([self::path('quality-*.json*'), self::path('tests/quality-*.json*')] as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                unlink($file);
            }
        }

        foreach (['app/Billing', 'app/Support'] as $generated) {
            if (! is_dir(self::path($generated))) {
                continue;
            }

            foreach (glob(self::path($generated.'/*.php')) ?: [] as $file) {
                unlink($file);
            }

            @rmdir(self::path($generated));
        }
    }

    /**
     * Pulls the fenced code block that immediately follows
     * `<!-- readme-test: $marker -->` out of README.md, so a test runs the
     * words a reader actually sees rather than a hand-kept copy of them.
     */
    public static function block(string $marker): string
    {
        $pattern = '/<!--\s*readme-test:\s*'.preg_quote($marker, '/')
            .'\s*-->\n?[ \t]*```[a-zA-Z]*\n(.*?)\n[ \t]*```/s';

        if (preg_match($pattern, self::readme(), $matches) !== 1) {
            throw new RuntimeException("No README block tagged \"readme-test: {$marker}\" was found.");
        }

        return self::dedent($matches[1]);
    }

    /**
     * README blocks nested under a numbered list step carry that list's
     * indentation; strip the common leading whitespace so the extracted
     * PHP or shell text reads the same as it would unindented.
     */
    private static function dedent(string $block): string
    {
        $lines = explode("\n", $block);
        $indent = null;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $leading = strlen($line) - strlen(ltrim($line, ' '));
            $indent = $indent === null ? $leading : min($indent, $leading);
        }

        if ($indent === null || $indent === 0) {
            return $block;
        }

        return implode("\n", array_map(
            static fn (string $line): string => $line === '' ? $line : substr($line, $indent),
            $lines,
        ));
    }

    private static function readme(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
    }

    /**
     * @return list<string>
     */
    private static function commandArguments(string $command): array
    {
        $command = trim($command);

        if (! str_starts_with($command, 'vendor/bin/pest')) {
            throw new RuntimeException("Expected a \"vendor/bin/pest\" command line, got: {$command}");
        }

        $rest = trim(substr($command, strlen('vendor/bin/pest')));

        if ($rest === '') {
            return [];
        }

        return preg_split('/\s+/', $rest) ?: [];
    }
}
