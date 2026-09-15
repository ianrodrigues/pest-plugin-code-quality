<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

use LogicException;
use PhpToken;

/**
 * Counts a method's body lines per the "Metric definitions" README
 * section: the physical lines between the opening and closing brace
 * that hold at least one token besides whitespace, a comment, or `{`/`}`.
 */
final readonly class SourceTokens
{
    private const int OPEN_BRACE = 123;

    private const int CLOSE_BRACE = 125;

    /** @var list<PhpToken> */
    private array $tokens;

    /** @var array<int, int> byte offset => token index */
    private array $indexByPos;

    public function __construct(string $contents)
    {
        $tokens = array_values(PhpToken::tokenize($contents));

        $this->tokens = $tokens;
        $this->indexByPos = array_flip(array_map(
            static fn (PhpToken $token): int => $token->pos,
            $tokens,
        ));
    }

    /**
     * Counts the body lines of the method whose closing brace sits at the
     * byte offset `$bodyEndFilePos` (a `ClassMethod` or `PropertyHook`
     * node's `endFilePos` attribute, which is exactly its closing `}`).
     */
    public function countBodyLines(int $bodyEndFilePos): int
    {
        $closeIndex = $this->indexByPos[$bodyEndFilePos]
            ?? throw new LogicException("No token found at byte offset {$bodyEndFilePos}.");

        if ($this->tokens[$closeIndex]->id !== self::CLOSE_BRACE) {
            throw new LogicException("Token at byte offset {$bodyEndFilePos} is not a closing brace.");
        }

        $openIndex = $this->matchingOpenBrace($closeIndex);

        $hasContent = [];

        for ($i = $openIndex; $i <= $closeIndex; $i++) {
            $token = $this->tokens[$i];

            if ($this->isSkippable($token)) {
                continue;
            }

            $span = substr_count($token->text, "\n");

            for ($line = $token->line; $line <= $token->line + $span; $line++) {
                $hasContent[$line] = true;
            }
        }

        $count = 0;

        for ($line = $this->tokens[$openIndex]->line; $line <= $this->tokens[$closeIndex]->line; $line++) {
            if ($hasContent[$line] ?? false) {
                $count++;
            }
        }

        return $count;
    }

    private function matchingOpenBrace(int $closeIndex): int
    {
        $depth = 1;

        for ($i = $closeIndex - 1; $i >= 0; $i--) {
            $token = $this->tokens[$i];

            if ($token->id === self::CLOSE_BRACE) {
                $depth++;

                continue;
            }

            if ($this->isOpenBrace($token)) {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        throw new LogicException('No matching opening brace found.');
    }

    /**
     * Interpolated strings open their brace with `{$` or `${`, tokenizing
     * as `T_CURLY_OPEN` or `T_DOLLAR_OPEN_CURLY_BRACES` rather than a
     * literal `{`, while still closing with a plain `}`.
     */
    private function isOpenBrace(PhpToken $token): bool
    {
        return $token->id === self::OPEN_BRACE
            || $token->is([\T_CURLY_OPEN, \T_DOLLAR_OPEN_CURLY_BRACES]);
    }

    private function isSkippable(PhpToken $token): bool
    {
        if ($token->id === self::OPEN_BRACE || $token->id === self::CLOSE_BRACE) {
            return true;
        }

        return $token->is([\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT]);
    }
}
