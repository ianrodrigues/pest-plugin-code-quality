<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Selection\EmptySelection;

it('passes when the selected classes declare no method bodies', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\AbstractOnly')->classes()->toHaveMethodComplexityAtMost(1));

    expect($chain)->not->toThrow(EmptySelection::class);
});
