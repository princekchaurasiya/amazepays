<?php

namespace App\Events;

use App\Models\SecurityEventLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecurityEventCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly SecurityEventLog $securityEventLog) {}
}
