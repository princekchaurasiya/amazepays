<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ValueDesignAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('providers.view');

        return Inertia::render('Admin/ValueDesign/Index', [
            'legacyBrandsUrl' => route('admin.vd.brands'),
            'legacyDashboardUrl' => url('/admin/vd/dashboard'),
            'testConnectionUrl' => route('admin.vd.brands.test-connection'),
        ]);
    }
}
