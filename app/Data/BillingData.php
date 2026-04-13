<?php

namespace App\Data;

/**
 * DTO for billing/shipping details attached to an order.
 */
readonly class BillingData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $address,
        public ?string $addressTwo = null,
        public string $city = '',
        public string $state = '',
        public string $zip = '',
        public string $country = 'IN',
        public ?string $gstNumber = null,
    ) {}

    public static function fromValidated(array $validated): self
    {
        return new self(
            name: $validated['billing_name'],
            email: $validated['billing_email'],
            phone: $validated['billing_tel'],
            address: $validated['billing_address'],
            addressTwo: $validated['billing_address_two'] ?? null,
            city: $validated['billing_city'] ?? '',
            state: $validated['billing_state'] ?? '',
            zip: $validated['billing_zip'] ?? '',
            country: $validated['billing_country'] ?? 'IN',
            gstNumber: $validated['billing_gst_number'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'billing_name' => $this->name,
            'billing_email' => $this->email,
            'billing_tel' => $this->phone,
            'billing_address' => $this->address,
            'billing_address_two' => $this->addressTwo,
            'billing_city' => $this->city,
            'billing_state' => $this->state,
            'billing_zip' => $this->zip,
            'billing_country' => $this->country,
            'billing_gst_number' => $this->gstNumber,
        ];
    }
}
