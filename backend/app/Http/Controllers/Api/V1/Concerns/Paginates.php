<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\Request;

trait Paginates
{
    /** ?per_page= clamped to 1..100 so clients can't request huge pages. */
    protected function perPage(Request $request, int $default = 15): int
    {
        return max(1, min(100, $request->integer('per_page', $default)));
    }
}
