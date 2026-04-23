<?php

namespace App\Support;

class BillingRequirementResolver
{
    /** @var array<string, string> */
    private const LABELS = [
        'billing_name' => 'Full name',
        'billing_email' => 'Email',
        'billing_tel' => 'Phone',
        'billing_address' => 'Address line 1',
        'billing_address_two' => 'Address line 2',
        'billing_city' => 'City',
        'billing_state' => 'State',
        'billing_zip' => 'ZIP/Pincode',
        'billing_country' => 'Country',
        'billing_gst_number' => 'GST number',
    ];

    /**
     * Return provider/payment-aware required billing fields.
     *
     * @return list<string>
     */
    public function requiredFields(?string $paymentMethod, ?string $sourceProvider): array
    {
        $payment = strtolower((string) $paymentMethod);
        $provider = strtolower((string) $sourceProvider);

        // CCAvenue currently enforces strict full-address form, including address line 2.
        if ($payment === 'ccavenue') {
            return [
                'billing_name',
                'billing_email',
                'billing_tel',
                'billing_address',
                'billing_address_two',
                'billing_city',
                'billing_state',
                'billing_zip',
                'billing_country',
            ];
        }

        // UPI/Unlimit + Woohoo flow requires full shipping/billing details.
        if (in_array($payment, ['upi', 'unlimit'], true) && $provider === 'woohoo') {
            return [
                'billing_name',
                'billing_email',
                'billing_address',
                'billing_city',
                'billing_state',
                'billing_zip',
                'billing_country',
            ];
        }

        // Wallet/internal flows can proceed with lightweight identity data.
        return [
            'billing_name',
            'billing_email',
        ];
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, string>
     */
    public function labels(array $fields): array
    {
        $output = [];
        foreach ($fields as $field) {
            $output[$field] = self::LABELS[$field] ?? $field;
        }

        return $output;
    }

    /**
     * @param  list<string>  $fields
     * @return list<string>
     */
    public function humanize(array $fields): array
    {
        return array_values(array_map(
            fn (string $field): string => self::LABELS[$field] ?? $field,
            $fields
        ));
    }
}

