<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * تقرير الأدمن العام
     */
    public function adminReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $result = $this->reportService->getAdminReport(
            $request->start_date,
            $request->end_date
        );

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * تقرير دليفري محدد
     */
    public function deliveryReport(Request $request, $deliveryId)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $result = $this->reportService->getDeliveryReport(
            $deliveryId,
            $request->start_date,
            $request->end_date
        );

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * تقرير محل محدد
     */
    public function shopReport(Request $request, $shopId)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $result = $this->reportService->getShopReport(
            $shopId,
            $request->start_date,
            $request->end_date
        );

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * التقرير الشامل للأدمن (كل شيء)
     */
    public function comprehensiveReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $result = $this->reportService->getComprehensiveReport(
            $request->start_date,
            $request->end_date
        );

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * تقرير الدليفري الحالي (للدليفري نفسه)
     */
    public function myDeliveryReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $deliveryId = Auth::user()->id;

        $result = $this->reportService->getDeliveryReport(
            $deliveryId,
            $request->start_date,
            $request->end_date
        );

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * تقرير المحل الحالي (للمحل نفسه)
     */
    public function myShopReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $shopId = Auth::user()->id;

        $result = $this->reportService->getShopReport(
            $shopId,
            $request->start_date,
            $request->end_date
        );

        return response()->json($result, $result['status'] ? 200 : 404);
    }
}
