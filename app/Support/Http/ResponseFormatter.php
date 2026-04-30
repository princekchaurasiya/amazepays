<?php

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class ResponseFormatter
{
    private const DEFAULT_SUCCESS_MESSAGE_KEY = 'response.ok';
    private const DEFAULT_ERROR_MESSAGE_KEY = 'error.unknown';

    private static function resolveMessage(string $messageKey): string
    {
        $translated = __($messageKey);

        if ($translated === $messageKey) {
            Log::warning('Missing translation key', ['message_key' => $messageKey]);

            $fallback = __(self::DEFAULT_ERROR_MESSAGE_KEY);
            if ($fallback === self::DEFAULT_ERROR_MESSAGE_KEY) {
                return 'Something went wrong. Please try again later.';
            }

            return $fallback;
        }

        $translated = trim((string) $translated);

        if ($translated === '') {
            $fallback = __(self::DEFAULT_ERROR_MESSAGE_KEY);
            if ($fallback === self::DEFAULT_ERROR_MESSAGE_KEY) {
                return 'Something went wrong. Please try again later.';
            }

            return (string) $fallback;
        }

        return $translated;
    }

    /**
     * Normalize payload->data into an object (associative array).
     * Lists become: { items: [...] }
     */
    private static function normalizeData(mixed $data): array
    {
        if ($data === null) {
            return [];
        }

        if (is_array($data)) {
            $keys = array_keys($data);
            $isList = $keys === range(0, count($keys) - 1);

            return $isList ? ['items' => $data] : $data;
        }

        return ['value' => $data];
    }

    public static function json(ResponsePayload $payload, ?Request $request = null): JsonResponse
    {
        $messageKey = $payload->messageKey
            ?: ($payload->success ? self::DEFAULT_SUCCESS_MESSAGE_KEY : self::DEFAULT_ERROR_MESSAGE_KEY);

        $httpStatus = $payload->httpStatus ?? $payload->code->httpStatus();

        $body = [
            'success' => (bool) $payload->success,
            'code' => $payload->code->value,
            'message_key' => $messageKey,
            'message' => self::resolveMessage($messageKey),
            'data' => self::normalizeData($payload->data),
            'details' => is_array($payload->details) ? $payload->details : [],
            'meta' => is_array($payload->meta) ? $payload->meta : [],
        ];

        // Enforce details-only-on-errors (but always present as object).
        if ($payload->success) {
            $body['details'] = [];
        }

        return response()->json($body, $httpStatus);
    }

    /**
     * Webhook acks are intentionally minimal and gateway-specific.
     * Internally we still log and translate into ResponsePayload for uniformity.
     */
    public static function webhookAck(ResponsePayload $payload, Request $request): Response
    {
        $gateway = (string) $request->attributes->get('webhook_gateway', '');

        // Default: empty 200 OK on success, 400 otherwise.
        if ($gateway === '') {
            return response('', $payload->success ? 200 : 400);
        }

        return match ($gateway) {
            'ccavenue' => response('OK', $payload->success ? 200 : 400),
            'razorpay' => response()->json(['status' => $payload->success ? 'ok' : 'error'], $payload->success ? 200 : 400),
            'unlimit' => response('', $payload->success ? 200 : 400),
            default => response('', $payload->success ? 200 : 400),
        };
    }
}
