<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function summary()
    {
        return response()->json([
            'success' => true,
            'data' => $this->reportService->summary(),
        ]);
    }
}