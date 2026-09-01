<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::whereIn('name', ['admin', 'manager', 'user'])
            ->with('permissions:id,name')
            ->orderBy('id')
            ->get(['id', 'name']);

        return $this->successResponse($roles);
    }
}
