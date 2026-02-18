<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Upload;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalOrganizations = Organization::count();
        $activeOrganizations = Organization::where('is_active', true)->count();
        $totalUsers = User::where('is_super_admin', false)->count();
        $totalUploads = Upload::withoutTenantScope()->count();

        $recentOrganizations = Organization::withTrashed()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard.index', compact(
            'totalOrganizations',
            'activeOrganizations',
            'totalUsers',
            'totalUploads',
            'recentOrganizations'
        ));
    }
}
