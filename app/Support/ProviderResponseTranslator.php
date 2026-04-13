<?php

namespace App\Support;

use App\Enums\ProviderErrorCode;
use App\Enums\ResponseCode;

/**
 * Resolves provider-specific error payloads to ResponseCode + localized message.
 */
final class ProviderResponseTranslator
{
    /**
     * @param  array<string, mixed>  $payload  Raw provider JSON (e.g. Woohoo error body)
     */
    public static function fromWoohooPayload(array $payload): array
    {
        $code = $payload['errorCode'] ?? $payload['code'] ?? $payload['statusCode'] ?? null;
        if (is_array($code)) {
            $code = $code[0] ?? null;
        }

        return self::fromWoohooCode($code);
    }

    public static function fromWoohooCode(string|int|null $code): array
    {
        $enum = ProviderErrorCode::tryFromWoohooCode($code);
        if ($enum !== null) {
            return [
                'response_code' => $enum->toResponseCode(),
                'message' => $enum->providerMessage(),
                'provider_error' => $enum->value,
            ];
        }

        $fallback = __('provider_errors.woohoo.'.(string) $code);
        if ($fallback !== 'provider_errors.woohoo.'.(string) $code) {
            return [
                'response_code' => ResponseCode::VOUCHER_FULFILLMENT_FAILED,
                'message' => $fallback,
                'provider_error' => 'woohoo.'.(string) $code,
            ];
        }

        return [
            'response_code' => ResponseCode::VOUCHER_FULFILLMENT_FAILED,
            'message' => __('provider_errors.woohoo.default'),
            'provider_error' => $code !== null && $code !== '' ? 'woohoo.'.(string) $code : 'woohoo.unknown',
        ];
    }

    public static function fromVouchagramCode(?string $code): array
    {
        $enum = ProviderErrorCode::tryFromVouchagramCode($code);
        if ($enum === ProviderErrorCode::VouchagramSuccess) {
            return [
                'response_code' => ResponseCode::OK,
                'message' => $enum->providerMessage(),
                'provider_error' => $enum->value,
            ];
        }

        if ($enum === ProviderErrorCode::VouchagramInvalid) {
            return [
                'response_code' => ResponseCode::VOUCHER_FULFILLMENT_FAILED,
                'message' => $enum->providerMessage(),
                'provider_error' => $enum->value,
            ];
        }

        return [
            'response_code' => ResponseCode::VOUCHER_FULFILLMENT_FAILED,
            'message' => __('provider_errors.vouchagram.default'),
            'provider_error' => 'vouchagram.'.($code ?? 'unknown'),
        ];
    }
}
