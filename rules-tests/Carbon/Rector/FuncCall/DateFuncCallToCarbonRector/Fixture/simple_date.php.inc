<?php

namespace Rector\Tests\Carbon\Rector\FuncCall\DateFuncCallToCarbonRector\Fixture;

class SimpleDateExample
{
    public function run()
    {
        $date = date('Y-m-d');
    }
}

?>
-----
<?php

namespace Rector\Tests\Carbon\Rector\FuncCall\DateFuncCallToCarbonRector\Fixture;

class SimpleDateExample
{
    public function run()
    {
        $date = \Carbon\Carbon::now()->format('Y-m-d');
    }
}

?>
