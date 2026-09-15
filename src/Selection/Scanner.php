<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Selection;

use IanRodrigues\CodeQuality\Analysis\AstMeasurer;
use IanRodrigues\CodeQuality\Analysis\FileMeasurements;
use IanRodrigues\CodeQuality\Analysis\MeasurementCache;
use IanRodrigues\CodeQuality\Policies\Policy;
use IanRodrigues\CodeQuality\Selection\Support\NamespaceDirectories;
use IanRodrigues\CodeQuality\Selection\Support\ResolvedDirectory;
use IanRodrigues\CodeQuality\Support\ProjectPath;
use Pest\Arch\Objects\FunctionDescription;
use Pest\Arch\Repositories\ObjectsRepository;
use PhpParser\Error as ParserError;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Architecture\Elements\ObjectDescription;
use Symfony\Component\Finder\Finder;

/**
 * Walks the same directories Pest's own `ObjectsRepository` does, rather
 * than guessing at completeness.
 *
 * Discovery ignores `classes()`/`ignoring()` filters (those are deliberate
 * choices, not gaps); eligibility, which drives `EmptySelection`, uses the
 * filtered objects a policy actually measures.
 */
final readonly class Scanner
{
    private Parser $parser;

    public function __construct(
        private AstMeasurer $measurer = new AstMeasurer(),
        private MeasurementCache $cache = new MeasurementCache(),
    ) {
        $this->parser = new ParserFactory()->createForNewestSupportedVersion();
    }

    /**
     * @param list<ObjectDescription> $filteredObjects
     */
    public function scan(string $target, array $filteredObjects, Policy $policy): TargetCoverage
    {
        $directories = NamespaceDirectories::resolve($target);

        if ($directories !== [] && array_all($directories, static fn (ResolvedDirectory $directory): bool => $directory->isVendor())) {
            throw VendorTarget::for($target, $directories);
        }

        $discovered = $this->discover($target);
        $files = $this->filesUnder($directories);

        $withAst = [];

        foreach ($discovered as $object) {
            if (! isset($object->stmts) || $object->stmts === []) {
                continue;
            }

            $withAst[ProjectPath::canonical($object->path)] = true;
        }

        $skipped = [];
        $withoutClasses = 0;

        foreach ($files as $canonical => $original) {
            if (isset($withAst[$canonical])) {
                continue;
            }

            $reason = $this->reasonFor($original, $directories);

            if (! $reason instanceof SkipReason) {
                $withoutClasses++;

                continue;
            }

            $skipped[] = new SkippedFile(ProjectPath::relative($canonical), $reason);
        }

        return new TargetCoverage(
            $target,
            array_map(static fn (ResolvedDirectory $directory): string => ProjectPath::relative($directory->path), $directories),
            count($files),
            count($discovered),
            count($withAst),
            $this->eligibleSymbols($filteredObjects, $policy),
            $skipped,
            $withoutClasses,
        );
    }

    /**
     * @return list<ObjectDescription>
     */
    private function discover(string $target): array
    {
        $objects = [];

        foreach (ObjectsRepository::getInstance()->allByNamespace($target) as $item) {
            // `FunctionDescription` also extends `ObjectDescription`, so it
            // is excluded explicitly rather than matched by type.
            if (! $item instanceof FunctionDescription) {
                $objects[] = $item;
            }
        }

        return $this->deduplicated($objects);
    }

    /**
     * A nested PSR-4 root inside a broader match makes `ObjectsRepository`
     * scan the same directory twice; dedupe by name, as `Targets::resolve()`
     * does across target strings.
     *
     * @param list<ObjectDescription> $objects
     * @return list<ObjectDescription>
     */
    private function deduplicated(array $objects): array
    {
        $seen = [];

        foreach ($objects as $object) {
            $seen[$object->name] ??= $object;
        }

        return array_values($seen);
    }

    /**
     * @param list<ObjectDescription> $filteredObjects
     */
    private function eligibleSymbols(array $filteredObjects, Policy $policy): int
    {
        $count = 0;

        foreach ($this->deduplicated($filteredObjects) as $object) {
            if (! isset($object->stmts) || $object->stmts === []) {
                continue;
            }

            $path = ProjectPath::canonical($object->path);
            $contents = @file_get_contents($path);

            if ($contents === false) {
                continue;
            }

            /** @var list<Node\Stmt> $stmts */
            $stmts = array_values($object->stmts);

            $measurements = $this->cache->remember(
                $path,
                $contents,
                fn (): FileMeasurements => $this->measurer->measureAst($path, $stmts),
            );

            foreach ($policy->symbolsIn($measurements) as $symbol) {
                if ($symbol->isAnonymous()) {
                    continue;
                }

                if ($policy->valueFor($symbol) !== null) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * @param list<ResolvedDirectory> $directories
     * @return array<string, string> canonical path => original path
     */
    private function filesUnder(array $directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            if ($directory->isFile) {
                $files[ProjectPath::canonical($directory->path)] = $directory->path;

                continue;
            }

            foreach (Finder::create()->files()->in($directory->path)->name('*.php') as $file) {
                $files[ProjectPath::canonical($file->getPathname())] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Null means the file declares no class-like symbol at all — a
     * functions file, a config file returning an array — so there is
     * nothing to measure and it is not a skip.
     *
     * @param list<ResolvedDirectory> $directories
     */
    private function reasonFor(string $file, array $directories): ?SkipReason
    {
        $owner = $this->ownerOf($file, $directories);

        if ($owner instanceof ResolvedDirectory && $owner->isVendor()) {
            return SkipReason::Vendor;
        }

        $node = $this->classLike($file);

        if ($node === false) {
            return SkipReason::NoAst;
        }

        if (! $node instanceof ClassLike) {
            return null;
        }

        $declared = $this->declaredNamespace($node);

        if ($declared === null) {
            return SkipReason::NoAst;
        }

        if (! $owner instanceof ResolvedDirectory || $declared !== $this->expectedNamespace($file, $owner)) {
            return SkipReason::NamespaceMismatch;
        }

        return SkipReason::NotLoadable;
    }

    /**
     * The most specific resolved directory a file was actually found
     * under, matched by directory containment rather than string prefix so
     * sibling namespaces sharing a prefix (`Billing` vs `BillingArchive`)
     * never cross-attribute.
     *
     * @param list<ResolvedDirectory> $directories
     */
    private function ownerOf(string $file, array $directories): ?ResolvedDirectory
    {
        $canonicalFile = ProjectPath::canonical($file);
        $best = null;
        $bestLength = -1;

        foreach ($directories as $directory) {
            if ($directory->isFile) {
                if (ProjectPath::canonical($directory->path) === $canonicalFile) {
                    return $directory;
                }

                continue;
            }

            $dir = ProjectPath::canonical($directory->path);

            if ($dir !== $canonicalFile && ! str_starts_with($canonicalFile, $dir.DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (strlen($dir) > $bestLength) {
                $best = $directory;
                $bestLength = strlen($dir);
            }
        }

        return $best;
    }

    private function expectedNamespace(string $file, ResolvedDirectory $owner): string
    {
        if ($owner->isFile) {
            return $owner->namespace;
        }

        $dir = ProjectPath::canonical($owner->path);
        $fileDir = dirname(ProjectPath::canonical($file));

        if ($fileDir === $dir) {
            return $owner->namespace;
        }

        $relative = str_replace(DIRECTORY_SEPARATOR, '\\', substr($fileDir, strlen($dir) + 1));

        return $owner->namespace.'\\'.$relative;
    }

    /**
     * `false` means the file could not be read or parsed at all; `null`
     * means it parsed fine but declares no class, interface, trait or
     * enum. Both are distinct from finding a `ClassLike` node.
     */
    private function classLike(string $file): ClassLike|false|null
    {
        $contents = @file_get_contents($file);

        if ($contents === false) {
            return false;
        }

        try {
            $ast = $this->parser->parse($contents);
        } catch (ParserError) {
            return false;
        }

        if ($ast === null) {
            return false;
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $stmts = $traverser->traverse($ast);

        $node = new NodeFinder()->findFirst($stmts, static fn (Node $node): bool => $node instanceof ClassLike);

        return $node instanceof ClassLike ? $node : null;
    }

    private function declaredNamespace(ClassLike $node): ?string
    {
        $name = $node->namespacedName;

        if (! $name instanceof Name) {
            return null;
        }

        $segments = explode('\\', $name->toString());
        array_pop($segments);

        return implode('\\', $segments);
    }
}
