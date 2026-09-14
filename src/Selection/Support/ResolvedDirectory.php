<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Selection\Support;

/**
 * One directory or single file a target's namespace resolved to, mirroring
 * a single entry of `ObjectsRepository::directoriesByNamespace()`.
 */
final readonly class ResolvedDirectory
{
    private function __construct(
        public string $path,
        public string $namespace,
        public bool $isFile,
    ) {
    }

    public static function directory(string $path, string $namespace): self
    {
        return new self($path, $namespace, false);
    }

    public static function file(string $path, string $namespace): self
    {
        return new self($path, $namespace, true);
    }

    public function isVendor(): bool
    {
        $real = realpath($this->path);
        $real = $real === false ? $this->path : $real;

        return str_contains($real, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR);
    }
}
