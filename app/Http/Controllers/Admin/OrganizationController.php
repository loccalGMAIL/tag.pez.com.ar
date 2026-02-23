<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Organization;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::withTrashed()
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('admin.organizations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                      => 'required|string|max:255',
            'slug'                      => 'required|string|max:100|unique:organizations,slug|regex:/^[a-z0-9\-]+$/',
            'eretail_base_url'          => 'nullable|url',
            'eretail_username'          => 'nullable|string|max:255',
            'eretail_password'          => 'nullable|string|max:255',
            'eretail_default_shop_code' => 'nullable|string|max:50',
            'eretail_db_host'           => 'nullable|string|max:255',
            'eretail_db_port'           => 'nullable|integer|min:1|max:65535',
            'eretail_db_database'       => 'nullable|string|max:255',
            'eretail_db_username'       => 'nullable|string|max:255',
            'eretail_db_password'       => 'nullable|string|max:255',
        ]);

        $organization = Organization::create([
            'name'                      => $request->name,
            'slug'                      => $request->slug,
            'is_active'                 => true,
            'eretail_base_url'          => $request->eretail_base_url,
            'eretail_username'          => $request->eretail_username,
            'eretail_password'          => $request->filled('eretail_password') ? $request->eretail_password : null,
            'eretail_default_shop_code' => $request->eretail_default_shop_code,
            'eretail_db_host'           => $request->eretail_db_host,
            'eretail_db_port'           => $request->eretail_db_port ?? '3306',
            'eretail_db_database'       => $request->eretail_db_database,
            'eretail_db_username'       => $request->eretail_db_username,
            'eretail_db_password'       => $request->filled('eretail_db_password') ? $request->eretail_db_password : null,
        ]);

        $this->seedDefaultSettings($organization);

        return redirect()
            ->route('admin.organizations.show', $organization)
            ->with('success', "Organización \"{$organization->name}\" creada correctamente.");
    }

    public function show(Organization $organization)
    {
        $organization->load('users');
        $uploadsCount = \App\Models\Upload::withoutTenantScope()
            ->where('organization_id', $organization->id)
            ->count();

        return view('admin.organizations.show', compact('organization', 'uploadsCount'));
    }

    public function edit(Organization $organization)
    {
        return view('admin.organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        $request->validate([
            'name'                      => 'required|string|max:255',
            'eretail_base_url'          => 'nullable|url',
            'eretail_username'          => 'nullable|string|max:255',
            'eretail_password'          => 'nullable|string|max:255',
            'eretail_default_shop_code' => 'nullable|string|max:50',
            'eretail_db_host'           => 'nullable|string|max:255',
            'eretail_db_port'           => 'nullable|integer|min:1|max:65535',
            'eretail_db_database'       => 'nullable|string|max:255',
            'eretail_db_username'       => 'nullable|string|max:255',
            'eretail_db_password'       => 'nullable|string|max:255',
        ]);

        $data = [
            'name'                      => $request->name,
            'eretail_base_url'          => $request->eretail_base_url,
            'eretail_username'          => $request->eretail_username,
            'eretail_default_shop_code' => $request->eretail_default_shop_code,
            'eretail_db_host'           => $request->eretail_db_host,
            'eretail_db_port'           => $request->eretail_db_port ?? '3306',
            'eretail_db_database'       => $request->eretail_db_database,
            'eretail_db_username'       => $request->eretail_db_username,
        ];

        // Solo actualizar contraseñas si se enviaron nuevas
        if ($request->filled('eretail_password')) {
            $data['eretail_password'] = $request->eretail_password;
        }
        if ($request->filled('eretail_db_password')) {
            $data['eretail_db_password'] = $request->eretail_db_password;
        }

        $organization->update($data);

        return redirect()
            ->route('admin.organizations.show', $organization)
            ->with('success', 'Organización actualizada correctamente.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', "Organización \"{$organization->name}\" desactivada.");
    }

    public function activate(Organization $organization)
    {
        if ($organization->trashed()) {
            $organization->restore();
            $organization->update(['is_active' => true]);
            $message = "Organización \"{$organization->name}\" restaurada y activada.";
        } else {
            $organization->update(['is_active' => !$organization->is_active]);
            $message = $organization->is_active
                ? "Organización \"{$organization->name}\" activada."
                : "Organización \"{$organization->name}\" desactivada.";
        }

        return redirect()
            ->route('admin.organizations.show', $organization)
            ->with('success', $message);
    }

    public function impersonate(Organization $organization)
    {
        session([
            'impersonating_org'      => $organization->id,
            'impersonating_org_name' => $organization->name,
        ]);

        return redirect()
            ->route('dashboard.index')
            ->with('info', "Visualizando como organización: {$organization->name}");
    }

    public function testDbConnection(Organization $organization)
    {
        $config     = $organization->getERetailDbConfig();
        $connName   = 'eretail_test_' . $organization->id;

        try {
            config(["database.connections.{$connName}" => $config]);
            \Illuminate\Support\Facades\DB::purge($connName);

            $pdo     = \Illuminate\Support\Facades\DB::connection($connName)->getPdo();
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();

            return response()->json([
                'status'  => 'success',
                'message' => 'Conexión exitosa',
                'details' => [
                    'host'          => $config['host'],
                    'database'      => $config['database'],
                    'mysql_version' => $version,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'details' => [
                    'host'     => $config['host'] ?? '(vacío)',
                    'database' => $config['database'] ?? '(vacío)',
                ],
            ], 422);
        } finally {
            \Illuminate\Support\Facades\DB::purge($connName);
        }
    }

    private function seedDefaultSettings(Organization $organization): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($organization);

        $defaults = [
            ['key' => 'discount_percentage',   'value' => '12',          'description' => 'Porcentaje de descuento',    'type' => 'integer'],
            ['key' => 'update_mode',           'value' => 'check_date',  'description' => 'Modo de actualización',     'type' => 'string'],
            ['key' => 'default_shop_code',     'value' => $organization->eretail_default_shop_code ?? '0001', 'description' => 'Código de tienda por defecto', 'type' => 'string'],
            ['key' => 'default_template',      'value' => 'REG',         'description' => 'Plantilla por defecto',     'type' => 'string'],
            ['key' => 'create_missing_products', 'value' => 'true',      'description' => 'Crear productos faltantes', 'type' => 'boolean'],
            ['key' => 'excel_skip_rows',       'value' => '2',           'description' => 'Filas a omitir en Excel',  'type' => 'integer'],
            ['key' => 'auto_refresh_tags',     'value' => 'true',        'description' => 'Refrescar etiquetas auto', 'type' => 'boolean'],
            ['key' => 'refresh_method',        'value' => 'specific',    'description' => 'Método de refresco',       'type' => 'string'],
            ['key' => 'flash_updated_tags',    'value' => 'false',       'description' => 'Parpadeo en etiquetas',    'type' => 'boolean'],
        ];

        foreach ($defaults as $setting) {
            AppSetting::create($setting);
        }

        $tenantManager->clear();
    }
}
