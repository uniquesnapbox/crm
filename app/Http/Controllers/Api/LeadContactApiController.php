<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeadContactApiController extends Controller
{
    private static ?bool $hasNextFollowUpColumn = null;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $perPage = max(10, min((int) $request->get('per_page', 20), 50));
        $selectColumns = [
            'id',
            'client_name',
            'client_email',
            'mobile',
            'source_id',
            'status_id',
            'category_id',
            'company_name',
            'website',
            'office',
            'country',
            'address',
            'interest_level',
            'deal_size',
            'contact_status',
            'contact_status_reason',
            'products_services',
            'assigned_to',
            'added_by',
            'note',
            'created_at',
        ];

        if ($this->hasNextFollowUpColumn()) {
            $selectColumns[] = 'next_follow_up';
        }

        $query = Lead::query()
            ->with([
                'leadSource' => function ($q) {
                    $q->select('id', DB::raw('type as name'));
                },
                'leadStatus' => function ($q) {
                    $q->select('id', DB::raw('type as name'), 'label_color');
                },
                'category:id,category_name',
                'addedBy:id,name,email',
                'assignedTo:id,name,email',
            ])
            ->select($selectColumns)
            ->whereNull('archived_at');

        $viewPermission = $user->permission('view_lead');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both'], true));

        if (!$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('added_by', $user->id)
                    ->orWhere('assigned_to', $user->id);
            });
        } elseif ($viewPermission === 'added') {
            $query->where('added_by', $user->id);
        } elseif ($viewPermission === 'owned') {
            $query->where('assigned_to', $user->id);
        } elseif ($viewPermission === 'both') {
            $query->where(function ($q) use ($user) {
                $q->where('added_by', $user->id)->orWhere('assigned_to', $user->id);
            });
        }

        $type = trim((string) $request->get('type', 'all'));
        if ($type !== '' && $type !== 'all') {
            if ($type === 'lead') {
                $query->whereNull('client_id');
            } else {
                $query->whereNotNull('client_id');
            }
        }

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $status = trim((string) $request->get('status', ''));
        if ($status !== '' && $status !== 'all') {
            $query->where('status_id', $status);
        }

        return response()->json($query->orderByDesc('created_at')->paginate($perPage));
    }

    private function hasNextFollowUpColumn(): bool
    {
        if (self::$hasNextFollowUpColumn === null) {
            self::$hasNextFollowUpColumn = Schema::hasColumn('leads', 'next_follow_up');
        }

        return self::$hasNextFollowUpColumn;
    }
}
