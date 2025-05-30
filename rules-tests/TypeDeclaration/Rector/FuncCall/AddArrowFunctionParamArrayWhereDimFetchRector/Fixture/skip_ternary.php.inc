<?php

declare(strict_types=1);

namespace Rector\Tests\TypeDeclaration\Rector\FuncCall\AddArrowFunctionParamArrayWhereDimFetchRector\Fixture;

final class SkipTernary
{
    public function run()
    {
        $array = [['name' => 'John']];

        $result = array_map(fn ($item) => is_array($item) ? $item['name'] : null, $array);
    }
}
