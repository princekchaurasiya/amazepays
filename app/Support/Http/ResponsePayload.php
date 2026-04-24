<?php

namespace App\Support\Http;

use App\Enums\ResponseCode;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class ResponsePayload implements Responsable
{
    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public readonly bool $success,
        public readonly ResponseCode $code,
        public readonly ?string $messageKey,
        public readonly mixed $data,
        public readonly array $details = [],
        public readonly ?array $meta = null,
        public readonly ?int $httpStatus = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function ok(?string $messageKey = null, array $data = []): self
    {
        return new self(true, ResponseCode::OK, $messageKey, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function created(?string $messageKey = null, array $data = []): self
    {
        return new self(true, ResponseCode::CREATED, $messageKey, $data, httpStatus: 201);
    }

    public static function paginated(LengthAwarePaginator $paginator): self
    {
        return new self(
            success: true,
            code: ResponseCode::OK,
            messageKey: null,
            data: $paginator->items(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function fail(ResponseCode $code, ?string $messageKey = null, array $details = [], ?int $httpStatus = null): self
    {
        return new self(false, $code, $messageKey, null, $details, null, $httpStatus);
    }

    public function toResponse($request)
    {
        if ($request instanceof Request && $request->attributes->get('is_webhook_ack') === true) {
            return ResponseFormatter::webhookAck($this, $request);
        }

        // Phase 0: keep transport simple and stable for mobile clients (JSON only).
        // Inertia/redirect transport will be centralized in the same formatter when Phase 5 removes Blade.
        return ResponseFormatter::json($this, $request);
    }
}
