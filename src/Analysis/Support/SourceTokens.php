<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Analysis\Support;

use LogicException;
use PhpToken;

/**
 * Counts a method's body lines from the token stream of its source file,
 * per the "Metric definitions" README section: the physical lines between
 * a method's opening and closing brace that contain at least one token
 * other than whitespace, a comment, or a standalone `{`/`}` character.
 */
final class SourceTokens
{
    private const int OPEN_BRACE = 123;

    private const int CLOSE_BRACE = 125;

    /** @var list<PhpToken> */
    private readonly array $tokens;

    /** @var array<int, int> byte offset => token index */
    private readonly array $indexByPos;

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
            $id = $this->tokens[$i]->id;

            if ($id === self::CLOSE_BRACE) {
                $depth++;

                continue;
            }

            if ($id === self::OPEN_BRACE) {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        throw new LogicException('No matching opening brace found.');
    }

    private function isSkippable(PhpToken $token): bool
    {
        if ($token->id === self::OPEN_BRACE || $token->id === self::CLOSE_BRACE) {
            return true;
        }

        return $token->is([\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT]);
    }
}
