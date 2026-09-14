<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\Boundaries;

final class Fixture
{
    // ccn2: 1 + (8 x &&) = 9
    public function ccn2Of9(bool $p1, bool $p2, bool $p3, bool $p4, bool $p5, bool $p6, bool $p7, bool $p8, bool $p9): bool
    {
        return $p1 && $p2 && $p3 && $p4 && $p5 && $p6 && $p7 && $p8 && $p9;
    }

    // ccn2: 1 + (9 x &&) = 10
    public function ccn2Of10(bool $p1, bool $p2, bool $p3, bool $p4, bool $p5, bool $p6, bool $p7, bool $p8, bool $p9, bool $p10): bool
    {
        return $p1 && $p2 && $p3 && $p4 && $p5 && $p6 && $p7 && $p8 && $p9 && $p10;
    }

    // ccn2: 1 + (10 x &&) = 11
    public function ccn2Of11(bool $p1, bool $p2, bool $p3, bool $p4, bool $p5, bool $p6, bool $p7, bool $p8, bool $p9, bool $p10, bool $p11): bool
    {
        return $p1 && $p2 && $p3 && $p4 && $p5 && $p6 && $p7 && $p8 && $p9 && $p10 && $p11;
    }

    // lines: 38 assignment statements + 1 return statement = 39
    public function lines39(): int
    {
        $v1 = 1;
        $v2 = 2;
        $v3 = 3;
        $v4 = 4;
        $v5 = 5;
        $v6 = 6;
        $v7 = 7;
        $v8 = 8;
        $v9 = 9;
        $v10 = 10;
        $v11 = 11;
        $v12 = 12;
        $v13 = 13;
        $v14 = 14;
        $v15 = 15;
        $v16 = 16;
        $v17 = 17;
        $v18 = 18;
        $v19 = 19;
        $v20 = 20;
        $v21 = 21;
        $v22 = 22;
        $v23 = 23;
        $v24 = 24;
        $v25 = 25;
        $v26 = 26;
        $v27 = 27;
        $v28 = 28;
        $v29 = 29;
        $v30 = 30;
        $v31 = 31;
        $v32 = 32;
        $v33 = 33;
        $v34 = 34;
        $v35 = 35;
        $v36 = 36;
        $v37 = 37;
        $v38 = 38;
        return $v1;
    }

    // lines: 39 assignment statements + 1 return statement = 40
    public function lines40(): int
    {
        $v1 = 1;
        $v2 = 2;
        $v3 = 3;
        $v4 = 4;
        $v5 = 5;
        $v6 = 6;
        $v7 = 7;
        $v8 = 8;
        $v9 = 9;
        $v10 = 10;
        $v11 = 11;
        $v12 = 12;
        $v13 = 13;
        $v14 = 14;
        $v15 = 15;
        $v16 = 16;
        $v17 = 17;
        $v18 = 18;
        $v19 = 19;
        $v20 = 20;
        $v21 = 21;
        $v22 = 22;
        $v23 = 23;
        $v24 = 24;
        $v25 = 25;
        $v26 = 26;
        $v27 = 27;
        $v28 = 28;
        $v29 = 29;
        $v30 = 30;
        $v31 = 31;
        $v32 = 32;
        $v33 = 33;
        $v34 = 34;
        $v35 = 35;
        $v36 = 36;
        $v37 = 37;
        $v38 = 38;
        $v39 = 39;
        return $v1;
    }

    // lines: 40 assignment statements + 1 return statement = 41
    public function lines41(): int
    {
        $v1 = 1;
        $v2 = 2;
        $v3 = 3;
        $v4 = 4;
        $v5 = 5;
        $v6 = 6;
        $v7 = 7;
        $v8 = 8;
        $v9 = 9;
        $v10 = 10;
        $v11 = 11;
        $v12 = 12;
        $v13 = 13;
        $v14 = 14;
        $v15 = 15;
        $v16 = 16;
        $v17 = 17;
        $v18 = 18;
        $v19 = 19;
        $v20 = 20;
        $v21 = 21;
        $v22 = 22;
        $v23 = 23;
        $v24 = 24;
        $v25 = 25;
        $v26 = 26;
        $v27 = 27;
        $v28 = 28;
        $v29 = 29;
        $v30 = 30;
        $v31 = 31;
        $v32 = 32;
        $v33 = 33;
        $v34 = 34;
        $v35 = 35;
        $v36 = 36;
        $v37 = 37;
        $v38 = 38;
        $v39 = 39;
        $v40 = 40;
        return $v1;
    }

    // params: 3
    public function params3(int $a1, int $a2, int $a3): int
    {
        return $a1 + $a2 + $a3;
    }

    // params: 4
    public function params4(int $a1, int $a2, int $a3, int $a4): int
    {
        return $a1 + $a2 + $a3 + $a4;
    }

    // params: 5
    public function params5(int $a1, int $a2, int $a3, int $a4, int $a5): int
    {
        return $a1 + $a2 + $a3 + $a4 + $a5;
    }
}
