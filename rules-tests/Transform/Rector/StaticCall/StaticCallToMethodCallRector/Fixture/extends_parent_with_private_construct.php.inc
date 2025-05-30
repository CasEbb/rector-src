<?php

namespace Rector\Tests\Transform\Rector\StaticCall\StaticCallToMethodCallRector\Fixture;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use Rector\Tests\Transform\Rector\StaticCall\StaticCallToMethodCallRector\Source\SomeResource;

class ExtendsParentWithPrivateConstruct extends SomeResource
{
    public function toArray(
        Request $request,
    ): array {
        return [
            'user_id' => $this->user_id ?? App::get(MissingValue::class),
        ];
    }
}

?>
-----
<?php

namespace Rector\Tests\Transform\Rector\StaticCall\StaticCallToMethodCallRector\Fixture;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use Rector\Tests\Transform\Rector\StaticCall\StaticCallToMethodCallRector\Source\SomeResource;

class ExtendsParentWithPrivateConstruct extends SomeResource
{
    public function __construct(private \Illuminate\Foundation\Application $application)
    {
    }
    public function toArray(
        Request $request,
    ): array {
        return [
            'user_id' => $this->user_id ?? $this->application->get(MissingValue::class),
        ];
    }
}

?>
