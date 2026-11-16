<?php

namespace App\Http\Controllers;

use App\Services\LinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LinkRedirectController extends Controller
{
    public function __invoke(string $code, LinkService $linkService): RedirectResponse|JsonResponse
    {
        return $linkService->resolve($code);
    }
}
