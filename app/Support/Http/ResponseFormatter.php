<?php

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResponseFormatter
{
    public static function json(ResponsePayload $payload, ?Request $request = null): JsonResponse
    {
        if ($payload->success) {
            $body = [
                'success' => true,
                'message' => $payload->messageKey ? __($payload->messageKey) : '',
                'data' => $payload->data,
            ];

            if (is_array($payload->meta)) {
                $body['meta'] = $payload->meta;
            }

            return response()->json($body, $payload->httpStatus ?? 200);
        }

        $error = [
            'code' => $payload->code->value,
            'message' => $payload->messageKey ? __($payload->messageKey) : $payload->code->message(),
        ];

        if (! empty($payload->details)) {
            $error['details'] = $payload->details;
        }

        return response()->json([
            'success' => false,
            'error' => $error,
        ], $payload->httpStatus ?? $payload->code->httpStatus());
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
