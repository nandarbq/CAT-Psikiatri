<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Monitoring\VersionInfoPayloadFactory;
use Illuminate\Http\JsonResponse;

class VersionInfoController extends Controller
{
    public function __construct(
        private readonly VersionInfoPayloadFactory $versionInfoPayloadFactory,
    ) {
    }

    public function version(): JsonResponse
    {
        return response()->json($this->versionInfoPayloadFactory->make());
    }
}
