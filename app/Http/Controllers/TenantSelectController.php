<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSelectController extends Controller
{
    public function create(Request $request)
    {
        $tenants = $request->user()->tenants()
            ->withPivot('is_active', 'last_active_at')
            ->orderBy('name')
            ->get();

        return view('tenants.select', ['tenants' => $tenants]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = Tenant::findOrFail((int) $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ])['tenant_id']);

        abort_unless($request->user()->membershipIn($tenant) !== null, 403);

        $request->user()->forceFill(['current_tenant_id' => $tenant->id])->save();
        session(['current_tenant_id' => $tenant->id]);

        return redirect()->to('/')->with('status', 'Tenant atualizado.');
    }
}
