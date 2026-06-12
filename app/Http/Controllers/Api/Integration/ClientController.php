<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Resources\Integration\ClientResource;
use App\Models\User;
use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    private function clientsQuery(Request $request): Builder
    {
        $query = User::query()
            ->withoutGlobalScope(ActiveScope::class)
            ->with(['roles:id,name,display_name', 'clientDetails:user_id,company_name,website,city,state'])
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->leftJoin('client_details', 'client_details.user_id', '=', 'users.id')
            ->where('roles.name', 'client')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.mobile',
                'users.status',
                'users.image',
            ]);

        if ($search = trim((string) $request->query('search', ''))) {
            $like = '%' . $search . '%';

            $query->where(function (Builder $builder) use ($like) {
                $builder->where('users.name', 'like', $like)
                    ->orWhere('users.email', 'like', $like)
                    ->orWhere('client_details.company_name', 'like', $like);
            });
        }

        if ($status = trim((string) $request->query('status', ''))) {
            $query->where('users.status', $status);
        }

        return $query->orderBy('users.name')->distinct('users.id');
    }

    public function index(Request $request)
    {
        $defaultPerPage = max((int) config('integration_api.default_per_page', 12), 1);
        $maxPerPage = max((int) config('integration_api.max_per_page', 50), $defaultPerPage);
        $perPage = min(max((int) $request->integer('per_page', $defaultPerPage), 1), $maxPerPage);

        $clients = $this->clientsQuery($request)
            ->paginate($perPage)
            ->appends($request->query());

        return ClientResource::collection($clients)->additional([
            'success' => true,
            'message' => 'Clients fetched successfully.',
        ]);
    }

    public function show(Request $request, int $clientId)
    {
        $client = $this->clientsQuery($request)->where('users.id', $clientId)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        return (new ClientResource($client))->additional([
            'success' => true,
            'message' => 'Client fetched successfully.',
        ]);
    }
}
