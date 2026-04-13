<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WoohooAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('providers.view');

        return Inertia::render('Admin/Woohoo/Index', [
            'host' => config('woohoo.host'),
            'configured' => $this->isConfigured(),
            'processingUrl' => route('woohoo.processing'),
        ]);
    }

    private function isConfigured(): bool
    {
        return (bool) config('woohoo.host')
            && (bool) config('woohoo.client_secret')
            && (bool) config('woohoo.bearer_token');
    }
}
