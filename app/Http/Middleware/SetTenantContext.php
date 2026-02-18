<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetTenantContext
{
    public function __construct(private TenantManager $tenantManager) {}

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->is_super_admin) {
            // Usuario regular: tenant obligatorio
            if (!$user->organization_id || !$user->organization) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Tu cuenta no está asociada a ninguna organización. Contactá al administrador.');
            }

            if (!$user->organization->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Tu organización está desactivada. Contactá al administrador.');
            }

            $this->tenantManager->setTenant($user->organization);

        } elseif ($user && $user->is_super_admin && $request->session()->has('impersonating_org')) {
            // Super-admin impersonando una organización
            $orgId = $request->session()->get('impersonating_org');
            $org   = \App\Models\Organization::find($orgId);

            if ($org && $org->is_active) {
                $this->tenantManager->setTenant($org);
            }
        }

        return $next($request);
    }
}
