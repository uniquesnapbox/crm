<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadCategory;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\User;
use App\Scopes\ActiveScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadOptionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $sources = LeadSource::query()
            ->select('id', 'type')
            ->orderBy('type')
            ->get()
            ->map(fn ($item) => [
                'value' => (string) $item->id,
                'label' => $item->type,
            ])
            ->values();

        $categories = LeadCategory::query()
            ->select('id', 'category_name')
            ->orderBy('category_name')
            ->get()
            ->map(fn ($item) => [
                'value' => (string) $item->id,
                'label' => $item->category_name,
            ])
            ->values();

        $statuses = LeadStatus::query()
            ->select('id', 'type', 'label_color', 'priority', 'default')
            ->orderBy('priority')
            ->get()
            ->map(fn ($item) => [
                'value' => (string) $item->id,
                'label' => $item->type,
                'color' => $item->label_color,
                'is_default' => (bool) $item->default,
            ])
            ->values();

        $employees = User::withoutGlobalScope(ActiveScope::class)
            ->onlyEmployee()
            ->where('status', 'active')
            ->with('roles:id,name,display_name')
            ->select('id', 'name', 'email', 'status', 'image')
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'value' => (string) $item->id,
                'label' => $item->name,
                'email' => $item->email,
            ])
            ->values();

        $countries = collect(countries())
            ->map(fn ($item) => [
                'value' => (string) $item->nicename,
                'label' => (string) $item->nicename,
                'phone_code' => preg_replace('/\D+/', '', (string) $item->phonecode),
            ])
            ->filter(fn ($item) => $item['value'] !== '')
            ->values();

        $products = Product::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'value' => (string) $item->name,
                'label' => (string) $item->name,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Lead options fetched successfully.',
            'data' => [
                'sources' => $sources,
                'categories' => $categories,
                'statuses' => $statuses,
                'employees' => $employees,
                'countries' => $countries,
                'products' => $products,
                'interest_levels' => [
                    ['value' => 'low', 'label' => 'Low'],
                    ['value' => 'medium', 'label' => 'Medium'],
                    ['value' => 'high', 'label' => 'High'],
                    ['value' => 'very_high', 'label' => 'Very High'],
                ],
                'contact_statuses' => [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'connected', 'label' => 'Connected'],
                    ['value' => 'not_connected', 'label' => 'Not Connected'],
                ],
            ],
        ]);
    }
}
