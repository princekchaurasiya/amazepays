<?php

use App\Enums\ResponseCode;

/**
 * Central messages for {@see ResponseCode}.
 */
return [
    'OK' => 'Request completed successfully.',
    'ORDER_CREATED' => 'Your order was created successfully.',
    'ORDER_PROCESSING' => 'Your order is being processed.',
    'ORDER_FULFILLED' => 'Your voucher is ready.',

    'VALIDATION_FAILED' => 'Please check the information you entered and try again.',
    'UNAUTHENTICATED' => 'Please sign in to continue.',
    'FORBIDDEN' => 'You do not have permission to perform this action.',
    'INSUFFICIENT_BALANCE' => 'Your wallet balance is too low for this purchase.',
    'INVALID_DENOMINATION' => 'The selected amount is not valid for this product.',
    'PURCHASE_LIMIT_EXCEEDED' => 'You have reached the purchase limit for this product.',
    'PRODUCT_UNAVAILABLE' => 'This product is currently unavailable.',
    'DUPLICATE_ORDER' => 'This order was already submitted. Please check your order history.',

    'VOUCHER_FULFILLMENT_FAILED' => 'We could not deliver your voucher. Please try again or contact support.',
    'PROVIDER_TIMEOUT' => 'The voucher provider did not respond in time. Please try again.',
    'PROVIDER_UNAVAILABLE' => 'The voucher service is temporarily unavailable. Please try again later.',

    'PAYMENT_FAILED' => 'Payment could not be completed.',
    'PAYMENT_GATEWAY_ERROR' => 'A payment service error occurred. Please try again or use another method.',

    'UNKNOWN_ERROR' => 'Something went wrong. Please try again later.',
];
