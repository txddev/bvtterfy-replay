<?php

namespace Bvtterfly\Replay\Policies;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReplayPolicy implements \Bvtterfly\Replay\Contracts\Policy
{
    /**
     * Create a new policy instance.
     */
    public function isIdempotentRequest(Request $request): bool
    {
        return $request->isMethod('POST')
               && ($request->hasHeader(config('replay.header_name')) || $request->has(config('replay.header_name')));
    }

    public function isRecordableResponse(Response $response): bool
    {
        return $response->isSuccessful()
               || $response->isServerError();
    }
}
