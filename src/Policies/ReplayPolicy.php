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
        return in_array($request->method(),config('replay.methods'))
               && ($request->hasHeader(config('replay.header_name')) || $request->has(config('replay.header_name')));
    }

    public function isRecordableResponse(Response $response): bool
    {
        return $response->isSuccessful() || $response->isRedirection() || $response->isServerError();
    }
}
