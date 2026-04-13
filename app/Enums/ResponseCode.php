<?php

namespace App\Enums;

/**
 * Central application response codes (API + domain). Messages live in resources/lang/{locale}/responses.php.
 */
enum ResponseCode: string
{
    case OK = 'OK';
    case ORDER_CREATED = 'ORDER_CREATED';
    case ORDER_PROCESSING = 'ORDER_PROCESSING';
    case ORDER_FULFILLED = 'ORDER_FULFILLED';

    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case UNAUTHENTICATED = 'UNAUTHENTICATED';
    case FORBIDDEN = 'FORBIDDEN';
    case INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    case INVALID_DENOMINATION = 'INVALID_DENOMINATION';
    case PURCHASE_LIMIT_EXCEEDED = 'PURCHASE_LIMIT_EXCEEDED';
    case PRODUCT_UNAVAILABLE = 'PRODUCT_UNAVAILABLE';
    case DUPLICATE_ORDER = 'DUPLICATE_ORDER';

    case VOUCHER_FULFILLMENT_FAILED = 'VOUCHER_FULFILLMENT_FAILED';
    case PROVIDER_TIMEOUT = 'PROVIDER_TIMEOUT';
    case PROVIDER_UNAVAILABLE = 'PROVIDER_UNAVAILABLE';

    case PAYMENT_FAILED = 'PAYMENT_FAILED';
    case PAYMENT_GATEWAY_ERROR = 'PAYMENT_GATEWAY_ERROR';

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
            self::ORDER_CREATED => 201,
            self::ORDER_PROCESSING => 202,
            self::VALIDATION_FAILED => 422,
            self::UNAUTHENTICATED => 401,
            self::FORBIDDEN => 403,
            self::INSUFFICIENT_BALANCE,
            self::INVALID_DENOMINATION,
            self::PURCHASE_LIMIT_EXCEEDED,
            self::PRODUCT_UNAVAILABLE,
            self::DUPLICATE_ORDER => 422,
            self::VOUCHER_FULFILLMENT_FAILED,
            self::PROVIDER_TIMEOUT,
            self::PROVIDER_UNAVAILABLE,
            self::PAYMENT_FAILED,
            self::PAYMENT_GATEWAY_ERROR => 502,
            self::UNKNOWN_ERROR => 500,
        };
    }

    public function message(): string
    {
        $key = 'responses.'.$this->value;
        $trans = __($key);

        return $trans !== $key ? $trans : __('responses.UNKNOWN_ERROR');
    }
}
