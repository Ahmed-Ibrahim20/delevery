<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintRequest;
use App\Http\Services\ComplaintService;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    /**
     * قائمة الشكاوى (مع بحث وتصفية)
     */
    public function index(Request $request)
    {
        $searchComplaint = $request->query('search');
        $perPageComplaint = $request->query('perPage', 10);
        $userId = $request->query('user_id');

        $complaints = $this->complaintService->indexComplaint($searchComplaint, $perPageComplaint, $userId);

        return response()->json([
            'status' => true,
            'message' => $userId ? 'قائمة شكاوى المستخدم' : 'قائمة الشكاوى',
            'data' => $complaints
        ]);
    }

    /**
     * إنشاء شكوى جديدة
     */
    public function store(ComplaintRequest $request)
    {
        $result = $this->complaintService->storeComplaint($request->validated());

        return response()->json($result, $result['status'] ? 201 : 500);
    }

    /**
     * استرجاع تفاصيل شكوى
     */
    public function show($id)
    {
        $complaint = $this->complaintService->editComplaint($id);

        if (!$complaint) {
            return response()->json([
                'status' => false,
                'message' => 'الشكوى غير موجودة'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تفاصيل الشكوى',
            'data' => $complaint
        ]);
    }

    /**
     * تحديث بيانات شكوى
     */
    public function update(ComplaintRequest $request, $id)
    {
        $result = $this->complaintService->updateComplaint($request->validated(), $id);

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * حذف شكوى
     */
    public function destroy($id)
    {
        $result = $this->complaintService->destroyComplaint($id);

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * تغيير حالة الشكوى
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|integer|in:0,1,2,3',
            'admin_notes' => 'sometimes|string|max:1000'
        ]);

        $result = $this->complaintService->changeComplaintStatus($id, $request->status, $request->admin_notes);

        return response()->json($result, $result['status'] ? 200 : 404);
    }
}
