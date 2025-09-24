<?php

namespace App\Http\Services;

use App\Models\Complaint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    protected Complaint $model;

    public function __construct(Complaint $model)
    {
        $this->model = $model;
    }

    /**
     * قائمة الشكاوى مع إمكانية البحث والتصفية
     */
    public function indexComplaint($searchComplaint = null, $perPageComplaint = 10, $userId = null)
    {
        return $this->model
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($searchComplaint, function ($query) use ($searchComplaint) {
                $query->where('complaint_text', 'like', "%{$searchComplaint}%")
                    ->orWhere('admin_notes', 'like', "%{$searchComplaint}%");
            })
            ->with('user:id,name,phone')
            ->orderBy('id', 'desc')
            ->paginate($perPageComplaint);
    }

    /**
     * إنشاء شكوى جديدة
     */
    public function storeComplaint(array $requestData)
    {
        try {
            return DB::transaction(function () use ($requestData) {
                $data = Arr::only($requestData, [
                    'complaint_text',
                ]);

                // إضافة معرف المستخدم من Auth
                $data['user_id'] = Auth::id();

                $complaint = $this->model->create($data);

                return [
                    'status' => true,
                    'message' => 'تم إنشاء الشكوى بنجاح',
                    'data' => $complaint->load('user:id,name,phone')
                ];
            });
        } catch (\Exception $e) {
            Log::error('Complaint creation failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء الشكوى'
            ];
        }
    }

    /**
     * استرجاع بيانات شكوى للتعديل
     */
    public function editComplaint($complaintId)
    {
        return $this->model->with('user:id,name,phone')->find($complaintId);
    }

    /**
     * تحديث بيانات شكوى
     */
    public function updateComplaint(array $requestData, $complaintId)
    {
        try {
            $complaint = $this->model->find($complaintId);

            if (!$complaint) {
                return [
                    'status' => false,
                    'message' => 'الشكوى غير موجودة'
                ];
            }

            $data = Arr::only($requestData, [
                'complaint_text',
                'status',
                'admin_notes'
            ]);

            $complaint->update($data);

            return [
                'status' => true,
                'message' => 'تم تحديث بيانات الشكوى بنجاح',
                'data' => $complaint->load('user:id,name,phone')
            ];
        } catch (\Exception $e) {
            Log::error('Complaint update failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الشكوى'
            ];
        }
    }

    /**
     * حذف شكوى
     */
    public function destroyComplaint($complaintId)
    {
        try {
            $complaint = $this->model->find($complaintId);

            if (!$complaint) {
                return [
                    'status' => false,
                    'message' => 'الشكوى غير موجودة'
                ];
            }

            $complaint->delete();

            return [
                'status' => true,
                'message' => 'تم حذف الشكوى بنجاح'
            ];
        } catch (\Exception $e) {
            Log::error('Complaint deletion failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الشكوى'
            ];
        }
    }

    /**
     * تغيير حالة الشكوى
     */
    public function changeComplaintStatus($complaintId, $status, $adminNotes = null)
    {
        try {
            $complaint = $this->model->find($complaintId);

            if (!$complaint) {
                return [
                    'status' => false,
                    'message' => 'الشكوى غير موجودة'
                ];
            }

            $updateData = ['status' => $status];
            if ($adminNotes !== null) {
                $updateData['admin_notes'] = $adminNotes;
            }

            $complaint->update($updateData);

            $statusText = match ($status) {
                0 => 'جديدة',
                1 => 'قيد المراجعة',
                2 => 'مكتملة',
                3 => 'مرفوضة',
                default => 'غير معروف'
            };

            return [
                'status' => true,
                'message' => "تم تغيير حالة الشكوى إلى: {$statusText}",
                'data' => $complaint->load('user:id,name,phone')
            ];
        } catch (\Exception $e) {
            Log::error('Complaint status change failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة الشكوى'
            ];
        }
    }
}
