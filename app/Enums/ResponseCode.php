<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Central application response codes (API + domain).
 */
enum ResponseCode: string
{
    case OK = 'OK';
    case CREATED = 'CREATED';
    case ORDER_CREATED = 'ORDER_CREATED';
    case ORDER_PROCESSING = 'ORDER_PROCESSING';
    case ORDER_FULFILLED = 'ORDER_FULFILLED';

    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case UNAUTHENTICATED = 'UNAUTHENTICATED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case RATE_LIMITED = 'RATE_LIMITED';

    // Auth / OTP
    case INVALID_OTP = 'INVALID_OTP';
    case OTP_EXPIRED = 'OTP_EXPIRED';
    case OTP_REPLAYED = 'OTP_REPLAYED';
    case TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';

    case WALLET_FROZEN = 'WALLET_FROZEN';
    case INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    case INVALID_DENOMINATION = 'INVALID_DENOMINATION';
    case PURCHASE_LIMIT_EXCEEDED = 'PURCHASE_LIMIT_EXCEEDED';
    case PRODUCT_UNAVAILABLE = 'PRODUCT_UNAVAILABLE';
    case DUPLICATE_ORDER = 'DUPLICATE_ORDER';
    case KYC_REQUIRED = 'KYC_REQUIRED';

    case VOUCHER_FULFILLMENT_FAILED = 'VOUCHER_FULFILLMENT_FAILED';
    case PROVIDER_TIMEOUT = 'PROVIDER_TIMEOUT';
    case PROVIDER_UNAVAILABLE = 'PROVIDER_UNAVAILABLE';

    case PAYMENT_FAILED = 'PAYMENT_FAILED';
    case PAYMENT_GATEWAY_ERROR = 'PAYMENT_GATEWAY_ERROR';

    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    public function isSuccess(): bool
    {
        return match ($this) {
            self::OK, self::ORDER_CREATED, self::ORDER_PROCESSING, self::ORDER_FULFILLED => true,
            default => false,
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::OK => 200,
            self::CREATED => 201,
            self::ORDER_CREATED => 201,
            self::ORDER_PROCESSING => 202,
            self::VALIDATION_FAILED => 422,
            self::UNAUTHENTICATED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::RATE_LIMITED => 429,
            self::TOO_MANY_REQUESTS => 429,
            self::INVALID_OTP,
            self::OTP_EXPIRED,
            self::OTP_REPLAYED => 422,
            self::WALLET_FROZEN => 422,
            self::INSUFFICIENT_BALANCE,
            self::INVALID_DENOMINATION,
            self::PURCHASE_LIMIT_EXCEEDED,
            self::PRODUCT_UNAVAILABLE,
            self::DUPLICATE_ORDER,
            self::KYC_REQUIRED => 422,
            self::VOUCHER_FULFILLMENT_FAILED,
            self::PROVIDER_TIMEOUT,
            self::PROVIDER_UNAVAILABLE,
            self::PAYMENT_FAILED,
            self::PAYMENT_GATEWAY_ERROR => 502,
            self::INTERNAL_ERROR => 500,
            self::UNKNOWN_ERROR => 500,
        };
    }

    public function message(): string
    {
        $key = match ($this) {
            self::OK => 'response.ok',
            self::CREATED => 'response.created',
            self::NOT_FOUND => 'error.not_found',
            self::FORBIDDEN => 'error.forbidden',
            self::UNAUTHENTICATED => 'error.unauthenticated',
            self::VALIDATION_FAILED => 'error.validation_failed',
            self::RATE_LIMITED, self::TOO_MANY_REQUESTS => 'error.rate_limited',
            default => 'error.unknown',
        };

        $trans = __($key);

        return $trans !== $key ? $trans : 'Something went wrong. Please try again later.';
    }
}
