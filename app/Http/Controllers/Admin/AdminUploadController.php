<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Upload;
use Illuminate\Http\Request;

class AdminUploadController extends Controller
{
    public function index(Request $request)
    {
        $query = Upload::withoutTenantScope()
            ->with(['organization', 'user'])
            ->orderBy('created_at', 'desc');

        // Filtro por organización
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Filtro por shop_code
        if ($request->filled('shop_code')) {
            $query->where('shop_code', $request->shop_code);
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $uploads = $query->paginate(25)->withQueryString();

        $organizations = Organization::orderBy('name')->get();

        // Shop codes distintos para el filtro
        $shopCodes = Upload::withoutTenantScope()
            ->distinct()
            ->orderBy('shop_code')
            ->pluck('shop_code')
            ->filter();

        return view('admin.uploads.index', compact('uploads', 'organizations', 'shopCodes'));
    }
}
