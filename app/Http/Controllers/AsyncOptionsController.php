<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsyncOptionsController extends AccountBaseController
{
    public function index(Request $request, string $resource): JsonResponse
    {
        abort_403(!auth()->check());

        $search = trim((string) $request->get('search', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(50, max(20, (int) $request->get('per_page', 20)));

        $payload = match ($resource) {
            'clients' => $this->clients($search, $page, $perPage),
            'employees' => $this->employees($search, $page, $perPage),
            'projects' => $this->projects($search, $page, $perPage),
            'products' => $this->products($search, $page, $perPage),
            'leads' => $this->leads($search, $page, $perPage),
            default => abort(404),
        };

        return response()->json($payload);
    }

    private function clients(string $search, int $page, int $perPage): array
    {
        $query = User::withoutGlobalScopes()
            ->whereHas('roles', fn($q) => $q->where('name', 'client'))
            ->select('users.id', 'users.name');

        if ($search !== '') {
            $query->where('users.name', 'like', '%' . $search . '%');
        }

        $result = $query->orderBy('users.name')->paginate($perPage, ['*'], 'page', $page);

        return $this->formatPaginated($result->items(), $result->hasMorePages());
    }

    private function employees(string $search, int $page, int $perPage): array
    {
        $query = User::withoutGlobalScopes()
            ->whereHas('roles', fn($q) => $q->where('name', 'employee'))
            ->where('users.status', 'active')
            ->select('users.id', 'users.name');

        if ($search !== '') {
            $query->where('users.name', 'like', '%' . $search . '%');
        }

        $result = $query->orderBy('users.name')->paginate($perPage, ['*'], 'page', $page);

        return $this->formatPaginated($result->items(), $result->hasMorePages());
    }

    private function projects(string $search, int $page, int $perPage): array
    {
        $query = Project::query()->select('id', 'project_name');

        if ($search !== '') {
            $query->where('project_name', 'like', '%' . $search . '%');
        }

        $result = $query->orderBy('project_name')->paginate($perPage, ['*'], 'page', $page);

        $items = array_map(function ($row) {
            return [
                'id' => $row->id,
                'text' => $row->project_name,
            ];
        }, $result->items());

        return [
            'results' => $items,
            'pagination' => ['more' => $result->hasMorePages()],
        ];
    }

    private function products(string $search, int $page, int $perPage): array
    {
        $query = Product::query()->select('id', 'name');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $result = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        $items = array_map(function ($row) {
            return [
                'id' => $row->id,
                'text' => $row->name,
            ];
        }, $result->items());

        return [
            'results' => $items,
            'pagination' => ['more' => $result->hasMorePages()],
        ];
    }

    private function leads(string $search, int $page, int $perPage): array
    {
        $query = Lead::query()->select('id', 'client_name');

        if ($search !== '') {
            $query->where('client_name', 'like', '%' . $search . '%');
        }

        $result = $query->orderBy('client_name')->paginate($perPage, ['*'], 'page', $page);

        $items = array_map(function ($row) {
            return [
                'id' => $row->id,
                'text' => $row->client_name,
            ];
        }, $result->items());

        return [
            'results' => $items,
            'pagination' => ['more' => $result->hasMorePages()],
        ];
    }

    private function formatPaginated(array $items, bool $hasMore): array
    {
        return [
            'results' => array_map(function ($row) {
                return [
                    'id' => $row->id,
                    'text' => $row->name,
                ];
            }, $items),
            'pagination' => ['more' => $hasMore],
        ];
    }
}
