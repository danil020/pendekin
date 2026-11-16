<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Resources\LinkResource;
use App\Models\Link;
use App\Services\LinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class LinkController extends Controller
{
    public function __construct(private LinkService $linkService) {}

    public function store(StoreLinkRequest $request): JsonResponse
    {
        $link = $this->linkService->create($request->user(), $request->validated());

        return LinkResource::make($link)->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return LinkResource::collection(
            $request->user()->links()->latest()->paginate(),
        );
    }

    public function deactivate(Link $link): LinkResource
    {
        Gate::authorize('deactivate', $link);

        $link->update(['is_active' => false]);

        return LinkResource::make($link->refresh());
    }
}
