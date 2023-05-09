<?php

declare(strict_types=1);

namespace Bvtterfly\Replay;

use Bvtterfly\Replay\Contracts\Policy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReplayMiddleware
{
    public function __construct(
        private Policy $policy,
        private Storage $storage,
    ) {
    }

    public function handle(Request $request, Closure $next, ?string $cachePrefix = null): Response
    {
        if (!config('replay.enabled')) {
            return $next($request);
        }

        if (!$this->policy->isIdempotentRequest($request)) {
            return $next($request);
        }

        $key = $this->getCacheKey($request, $cachePrefix);

        if ($recordedResponse = ReplayResponse::find($key)) {
            return $recordedResponse->toResponse(RequestHelper::signature($request));
        }
        $lock = $this->storage->lock($key);

        $start = microtime(true);
        $current = $start;

        while (!$lock->get()) {
            if ($current - $start > config('replay.wait_for_response_in_process_timeout')) {
                abort(Response::HTTP_CONFLICT, __('replay::responses.error_messages.already_in_progress'));
            }
            sleep(1);
            if ($recordedResponse = ReplayResponse::find($key)) {
                return $recordedResponse->toResponse(RequestHelper::signature($request));
            }
            $lock = $this->storage->lock($key);
            $current = microtime(true);
        }

        try {
            $response = $next($request);
            if ($this->policy->isRecordableResponse($response)) {
                ReplayResponse::save($key, RequestHelper::signature($request), $response);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    protected function getCacheKey(Request $request, ?string $prefix = null): string
    {
        $idempotencyKey = $this->getIdempotencyKey($request);

        return $prefix ? "$prefix:$idempotencyKey" : $idempotencyKey;
    }

    protected function getIdempotencyKey(Request $request): string
    {
        return $request->header(config('replay.header_name')) ?? $request->input(config('replay.header_name'));
    }
}
