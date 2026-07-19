<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Monitoring\HealthCheckPayloadFactory;
use App\Support\Monitoring\VersionInfoPayloadFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthCheckController extends Controller
{
    public function __construct(
        private readonly HealthCheckPayloadFactory $healthPayloadFactory,
        private readonly VersionInfoPayloadFactory $versionInfoPayloadFactory,
    ) {
    }

    public function health(): JsonResponse
    {
        $payload = $this->healthPayloadFactory->make();

        return response()->json(
            $payload,
            $this->healthPayloadFactory->resolveHttpStatus($payload),
        );
    }

    public function status(Request $request): JsonResponse|View
    {
        $healthPayload = $this->healthPayloadFactory->make();
        $httpStatus = $this->healthPayloadFactory->resolveHttpStatus($healthPayload);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($healthPayload, $httpStatus);
        }

        return view('status', [
            'health' => $healthPayload,
            'healthHttpStatus' => $httpStatus,
            'version' => $this->versionInfoPayloadFactory->make(),
        ]);
    }
}
