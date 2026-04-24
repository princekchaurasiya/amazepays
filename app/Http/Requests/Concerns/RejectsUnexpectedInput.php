<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

trait RejectsUnexpectedInput
{
    /**
     * Reject payloads that include keys outside the explicit validation rules.
     * This blocks hidden-field tampering and mass-assignment style probing.
     */
    protected function passedValidation(): void
    {
        $allowed = array_keys($this->rules());
        $incoming = array_keys($this->all());

        $unknown = array_values(array_diff($incoming, $allowed));
        if ($unknown === []) {
            return;
        }

        if ($this->expectsJson()) {
            throw new HttpResponseException(
                new JsonResponse([
                    'message' => 'Unexpected input fields detected.',
                    'errors' => [
                        'unexpected_fields' => $unknown,
                    ],
                ], 422)
            );
        }

        throw ValidationException::withMessages([
            'unexpected_fields' => ['Unexpected input fields detected: '.implode(', ', $unknown)],
        ]);
    }
}
