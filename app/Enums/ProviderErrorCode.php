<?php

namespace App\Enums;

use App\Support\ProviderResponseTranslator;

/**
 * Provider-scoped error identifiers. Maps to ResponseCode and provider_errors lang keys (value = lang key suffix).
 *
 * @see ProviderResponseTranslator For numeric Woohoo-style codes without an explicit enum case.
 */
enum ProviderErrorCode: string
{
    // Woohoo / legacy order API numeric codes (lang: provider_errors.woohoo.{code})
    case Woohoo200 = 'woohoo.200';
    case Woohoo201 = 'woohoo.201';
    case Woohoo202 = 'woohoo.202';
    case Woohoo400 = 'woohoo.400';
    case Woohoo401 = 'woohoo.401';
    case Woohoo403 = 'woohoo.403';
    case Woohoo500 = 'woohoo.500';
    case Woohoo5035 = 'woohoo.5035';
    case Woohoo5036 = 'woohoo.5036';
    case Woohoo5037 = 'woohoo.5037';
    case Woohoo5038 = 'woohoo.5038';
    case Woohoo5046 = 'woohoo.5046';
    case Woohoo5080 = 'woohoo.5080';
    case Woohoo5103 = 'woohoo.5103';
    case Woohoo5305 = 'woohoo.5305';
    case Woohoo5307 = 'woohoo.5307';
    case Woohoo5308 = 'woohoo.5308';
    case Woohoo5310 = 'woohoo.5310';
    case Woohoo5311 = 'woohoo.5311';
    case Woohoo5312 = 'woohoo.5312';
    case Woohoo5313 = 'woohoo.5313';
    case Woohoo5315 = 'woohoo.5315';
    case Woohoo5318 = 'woohoo.5318';
    case Woohoo5321 = 'woohoo.5321';
    case Woohoo5326 = 'woohoo.5326';
    case Woohoo5327 = 'woohoo.5327';
    case Woohoo5333 = 'woohoo.5333';
    case Woohoo5334 = 'woohoo.5334';
    case Woohoo5335 = 'woohoo.5335';
    case Woohoo5338 = 'woohoo.5338';
    case Woohoo5342 = 'woohoo.5342';
    case Woohoo5343 = 'woohoo.5343';
    case Woohoo5344 = 'woohoo.5344';
    case Woohoo5348 = 'woohoo.5348';
    case Woohoo5349 = 'woohoo.5349';
    case Woohoo6000 = 'woohoo.6000';
    case Woohoo6050 = 'woohoo.6050';
    case Woohoo6051 = 'woohoo.6051';
    case Woohoo6052 = 'woohoo.6052';
    case Woohoo6053 = 'woohoo.6053';
    case Woohoo6054 = 'woohoo.6054';
    case Woohoo6057 = 'woohoo.6057';
    case Woohoo6058 = 'woohoo.6058';
    case Woohoo6059 = 'woohoo.6059';
    case Woohoo6063 = 'woohoo.6063';
    case Woohoo6015 = 'woohoo.6015';
    case Woohoo11429 = 'woohoo.11429';
    case Woohoo11447 = 'woohoo.11447';
    case Woohoo7001 = 'woohoo.7001';
    case Woohoo7002 = 'woohoo.7002';

    // Vouchagram (application envelope)
    case VouchagramSuccess = 'vouchagram.0000';
    case VouchagramInvalid = 'vouchagram.invalid';

    public function toResponseCode(): ResponseCode
    {
        return match ($this) {
            self::Woohoo200 => ResponseCode::OK,
            self::Woohoo201 => ResponseCode::ORDER_CREATED,
            self::Woohoo202 => ResponseCode::ORDER_PROCESSING,
            self::Woohoo400 => ResponseCode::VALIDATION_FAILED,
            self::Woohoo401 => ResponseCode::UNAUTHENTICATED,
            self::Woohoo403 => ResponseCode::FORBIDDEN,
            self::Woohoo500,
            self::Woohoo7001,
            self::Woohoo7002 => ResponseCode::PROVIDER_UNAVAILABLE,
            self::Woohoo5035,
            self::Woohoo5037,
            self::Woohoo5305 => ResponseCode::PROVIDER_UNAVAILABLE,
            self::Woohoo5036,
            self::Woohoo5038,
            self::Woohoo5046,
            self::Woohoo5080,
            self::Woohoo5103,
            self::Woohoo5311,
            self::Woohoo5312,
            self::Woohoo5342,
            self::Woohoo5343,
            self::Woohoo5344,
            self::Woohoo5348,
            self::Woohoo5349,
            self::Woohoo6000,
            self::Woohoo6050,
            self::Woohoo6051,
            self::Woohoo6052,
            self::Woohoo6053,
            self::Woohoo6054,
            self::Woohoo6057,
            self::Woohoo6058,
            self::Woohoo6059 => ResponseCode::PAYMENT_GATEWAY_ERROR,
            self::Woohoo5307,
            self::Woohoo5318 => ResponseCode::INVALID_DENOMINATION,
            self::Woohoo5308,
            self::Woohoo5310 => ResponseCode::PRODUCT_UNAVAILABLE,
            self::Woohoo5313 => ResponseCode::DUPLICATE_ORDER,
            self::Woohoo5315,
            self::Woohoo5321,
            self::Woohoo5326,
            self::Woohoo5327,
            self::Woohoo5333,
            self::Woohoo5334,
            self::Woohoo5335,
            self::Woohoo5338,
            self::Woohoo11429,
            self::Woohoo11447 => ResponseCode::VOUCHER_FULFILLMENT_FAILED,
            self::Woohoo6063 => ResponseCode::INSUFFICIENT_BALANCE,
            self::Woohoo6015 => ResponseCode::PURCHASE_LIMIT_EXCEEDED,
            self::VouchagramSuccess => ResponseCode::OK,
            self::VouchagramInvalid => ResponseCode::VOUCHER_FULFILLMENT_FAILED,
        };
    }

    /**
     * User-facing message from provider_errors.{value}
     */
    public function providerMessage(): string
    {
        $parts = explode('.', $this->value, 2);
        $provider = $parts[0] ?? 'woohoo';
        $code = $parts[1] ?? 'default';
        $key = 'provider_errors.'.$provider.'.'.$code;
        $trans = __($key);

        return $trans !== $key ? $trans : __("provider_errors.{$provider}.default");
    }

    public static function tryFromWoohooCode(string|int|null $code): ?self
    {
        if ($code === null || $code === '') {
            return null;
        }

        $normalized = is_numeric($code) ? (string) (int) $code : (string) $code;

        return match ($normalized) {
            '200' => self::Woohoo200,
            '201' => self::Woohoo201,
            '202' => self::Woohoo202,
            '400' => self::Woohoo400,
            '401' => self::Woohoo401,
            '403' => self::Woohoo403,
            '500' => self::Woohoo500,
            '5035' => self::Woohoo5035,
            '5036' => self::Woohoo5036,
            '5037' => self::Woohoo5037,
            '5038' => self::Woohoo5038,
            '5046' => self::Woohoo5046,
            '5080' => self::Woohoo5080,
            '5103' => self::Woohoo5103,
            '5305' => self::Woohoo5305,
            '5307' => self::Woohoo5307,
            '5308' => self::Woohoo5308,
            '5310' => self::Woohoo5310,
            '5311' => self::Woohoo5311,
            '5312' => self::Woohoo5312,
            '5313' => self::Woohoo5313,
            '5315' => self::Woohoo5315,
            '5318' => self::Woohoo5318,
            '5321' => self::Woohoo5321,
            '5326' => self::Woohoo5326,
            '5327' => self::Woohoo5327,
            '5333' => self::Woohoo5333,
            '5334' => self::Woohoo5334,
            '5335' => self::Woohoo5335,
            '5338' => self::Woohoo5338,
            '5342' => self::Woohoo5342,
            '5343' => self::Woohoo5343,
            '5344' => self::Woohoo5344,
            '5348' => self::Woohoo5348,
            '5349' => self::Woohoo5349,
            '6000' => self::Woohoo6000,
            '6050' => self::Woohoo6050,
            '6051' => self::Woohoo6051,
            '6052' => self::Woohoo6052,
            '6053' => self::Woohoo6053,
            '6054' => self::Woohoo6054,
            '6057' => self::Woohoo6057,
            '6058' => self::Woohoo6058,
            '6059' => self::Woohoo6059,
            '6063' => self::Woohoo6063,
            '6015' => self::Woohoo6015,
            '11429' => self::Woohoo11429,
            '11447' => self::Woohoo11447,
            '7001' => self::Woohoo7001,
            '7002' => self::Woohoo7002,
            default => null,
        };
    }

    public static function tryFromVouchagramCode(?string $code): ?self
    {
        if ($code === null || $code === '') {
            return null;
        }

        return match ($code) {
            '0000' => self::VouchagramSuccess,
            default => self::VouchagramInvalid,
        };
    }
}
