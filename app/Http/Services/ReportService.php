<?php

namespace App\Http\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportService
{
    protected Order $orderModel;
    protected User $userModel;

    public function __construct(Order $orderModel, User $userModel)
    {
        $this->orderModel = $orderModel;
        $this->userModel = $userModel;
    }

    /**
     * تقرير الأدمن الشامل المحدث
     */
    public function getAdminReport($startDate = null, $endDate = null)
    {
        try {
            $query = $this->orderModel->where('status', 3); // complete orders only

            // تطبيق فلتر التاريخ إذا تم تمريره
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            // جلب كل الأوردرات مع العلاقات
            $orders = $query->with(['addedBy:id,name,phone,role,commission_percentage', 'delivery:id,name,phone,role,commission_percentage'])
                ->orderBy('created_at', 'desc')
                ->get();

            // حساب الإحصائيات الأساسية
            $completedOrdersCount = $orders->count();
            $totalDeliveryFees = $orders->sum('delivery_fee');
            $totalOrdersValue = $orders->sum('total');

            // حساب عمولة التطبيق من المتاجر والسائقين
            $shopCommissionTotal = 0;
            $driverCommissionTotal = 0;

            // إحصائيات الأداء
            $shopPerformance = [];
            $driverPerformance = [];

            foreach ($orders as $order) {
                // حساب عمولة التطبيق من المحل (على إجمالي الأوردر)
                if ($order->addedBy && $order->addedBy->role == User::ROLE_SHOP) {
                    $shopCommission = ($order->total * ($order->addedBy->commission_percentage ?? 0)) / 100;
                    $shopCommissionTotal += $shopCommission;

                    // إحصائيات أداء المتاجر
                    $shopId = $order->addedBy->id;
                    if (!isset($shopPerformance[$shopId])) {
                        $shopPerformance[$shopId] = [
                            'id' => $shopId,
                            'name' => $order->addedBy->name,
                            'phone' => $order->addedBy->phone,
                            'commission_percentage' => $order->addedBy->commission_percentage ?? 0,
                            'orders_count' => 0,
                            'total_orders_value' => 0,
                            'commission_paid_to_platform' => 0
                        ];
                    }
                    $shopPerformance[$shopId]['orders_count']++;
                    $shopPerformance[$shopId]['total_orders_value'] += $order->total;
                    $shopPerformance[$shopId]['commission_paid_to_platform'] += $shopCommission;
                }

                // حساب عمولة التطبيق من السائق (على رسوم التوصيل)
                if ($order->delivery && $order->delivery->role == User::ROLE_DRIVER) {
                    $driverCommission = ($order->delivery_fee * ($order->delivery->commission_percentage ?? 0)) / 100;
                    $driverCommissionTotal += $driverCommission;

                    // إحصائيات أداء السائقين
                    $driverId = $order->delivery->id;
                    if (!isset($driverPerformance[$driverId])) {
                        $driverPerformance[$driverId] = [
                            'id' => $driverId,
                            'name' => $order->delivery->name,
                            'phone' => $order->delivery->phone,
                            'commission_percentage' => $order->delivery->commission_percentage ?? 0,
                            'orders_count' => 0,
                            'total_delivery_fees' => 0,
                            'commission_paid_to_platform' => 0
                        ];
                    }
                    $driverPerformance[$driverId]['orders_count']++;
                    $driverPerformance[$driverId]['total_delivery_fees'] += $order->delivery_fee;
                    $driverPerformance[$driverId]['commission_paid_to_platform'] += $driverCommission;
                }
            }

            // إجمالي إيرادات المنصة
            $totalPlatformRevenue = $shopCommissionTotal + $driverCommissionTotal;

            // ترتيب الأداء (أكثر نشاطاً حسب عدد الأوردرات)
            $topShops = collect($shopPerformance)->sortByDesc('orders_count')->take(10)->values();
            $topDrivers = collect($driverPerformance)->sortByDesc('orders_count')->take(10)->values();

            // إحصائيات عامة - فقط المعتمدين
            $totalShopsCount = $this->userModel->where('role', User::ROLE_SHOP)
                ->where('is_approved', true)
                ->count();
            $totalDriversCount = $this->userModel->where('role', User::ROLE_DRIVER)
                ->where('is_approved', true)
                ->count();

            return [
                'status' => true,
                'message' => 'تقرير الأدمن الشامل',
                'data' => [
                    'summary' => [
                        'completed_orders_count' => $completedOrdersCount,
                        'total_orders_value' => round($totalOrdersValue, 2),
                        'total_delivery_fees' => round($totalDeliveryFees, 2),
                        'shop_commission_total' => round($shopCommissionTotal, 2),
                        'driver_commission_total' => round($driverCommissionTotal, 2),
                        'total_platform_revenue' => round($totalPlatformRevenue, 2),
                        'period' => [
                            'start_date' => $startDate,
                            'end_date' => $endDate
                        ]
                    ],
                    'general_statistics' => [
                        'total_shops_count' => $totalShopsCount,
                        'total_drivers_count' => $totalDriversCount,
                        'active_shops_count' => count($shopPerformance),
                        'active_drivers_count' => count($driverPerformance)
                    ],
                    'top_performance' => [
                        'top_shops' => $topShops,
                        'top_drivers' => $topDrivers
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Admin report failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب تقرير الأدمن'
            ];
        }
    }

    /**
     * تقرير السائق المحدث
     */
    public function getDeliveryReport($deliveryId, $startDate = null, $endDate = null)
    {
        try {
            // التحقق من وجود السائق
            $delivery = $this->userModel->where('id', $deliveryId)
                ->where('role', User::ROLE_DRIVER)
                ->first();

            if (!$delivery) {
                return [
                    'status' => false,
                    'message' => 'السائق غير موجود'
                ];
            }

            $query = $this->orderModel->where('delivery_id', $deliveryId)
                ->where('status', 3); // complete orders only

            // تطبيق فلتر التاريخ
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            // حساب الإحصائيات
            $completedOrders = $query->count();
            $totalDeliveryFees = $query->sum('delivery_fee');

            // حساب عمولة التطبيق من السائق
            $applicationPercentage = $delivery->commission_percentage ?? 0;
            $applicationCommission = ($totalDeliveryFees * $applicationPercentage) / 100;

            // صافي الأرباح للسائق
            $netProfit = $totalDeliveryFees - $applicationCommission;

            return [
                'status' => true,
                'message' => 'تقرير السائق',
                'data' => [
                    'driver_info' => [
                        'id' => $delivery->id,
                        'name' => $delivery->name,
                        'phone' => $delivery->phone,
                        'commission_percentage' => $applicationPercentage
                    ],
                    'completed_orders_count' => $completedOrders,
                    'total_delivery_fees' => round($totalDeliveryFees, 2),
                    'application_percentage' => $applicationPercentage,
                    'application_commission' => round($applicationCommission, 2),
                    'net_profit' => round($netProfit, 2),
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Delivery report failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب تقرير السائق'
            ];
        }
    }

    /**
     * تقرير المحل المحدث
     */
    public function getShopReport($shopId, $startDate = null, $endDate = null)
    {
        try {
            // التحقق من وجود المحل
            $shop = $this->userModel->where('id', $shopId)
                ->where('role', User::ROLE_SHOP)
                ->first();

            if (!$shop) {
                return [
                    'status' => false,
                    'message' => 'المحل غير موجود'
                ];
            }

            $query = $this->orderModel->where('user_add_id', $shopId)
                ->where('status', 3); // complete orders only

            // تطبيق فلتر التاريخ
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            // حساب الإحصائيات
            $orders = $query->get();
            $completedOrders = $orders->count();
            $totalOrdersValue = $orders->sum('total');
            $totalDeliveryFees = $orders->sum('delivery_fee');

            // حساب عمولة التطبيق من المحل
            $applicationPercentage = $shop->commission_percentage ?? 0;
            $applicationCommission = ($totalOrdersValue * $applicationPercentage) / 100;

            // صافي الربح للمحل
            $netProfit = $totalOrdersValue - $applicationCommission;

            return [
                'status' => true,
                'message' => 'تقرير المحل',
                'data' => [
                    'shop_info' => [
                        'id' => $shop->id,
                        'name' => $shop->name,
                        'phone' => $shop->phone,
                        'commission_percentage' => $applicationPercentage
                    ],
                    'completed_orders_count' => $completedOrders,
                    'total_orders_value' => round($totalOrdersValue, 2),
                    'total_delivery_fees' => round($totalDeliveryFees, 2),
                    'application_percentage' => $applicationPercentage,
                    'application_commission' => round($applicationCommission, 2),
                    'net_profit' => round($netProfit, 2),
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Shop report failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب تقرير المحل'
            ];
        }
    }

    /**
     * تقرير شامل للأدمن - كل الدليفريز والمحلات
     */
    public function getComprehensiveReport($startDate = null, $endDate = null)
    {
        try {
            // تقرير الأدمن العام
            $adminReport = $this->getAdminReport($startDate, $endDate);

            // تقارير كل الدليفريز - فقط المعتمدين
            $deliveries = $this->userModel->where('role', User::ROLE_DRIVER)
                ->where('is_active', true)
                ->where('is_approved', true)
                ->get();

            $deliveryReports = [];
            foreach ($deliveries as $delivery) {
                $report = $this->getDeliveryReport($delivery->id, $startDate, $endDate);
                if ($report['status']) {
                    $deliveryReports[] = $report['data'];
                }
            }

            // تقارير كل المحلات - فقط المعتمدين
            $shops = $this->userModel->where('role', User::ROLE_SHOP)
                ->where('is_active', true)
                ->where('is_approved', true)
                ->get();

            $shopReports = [];
            foreach ($shops as $shop) {
                $report = $this->getShopReport($shop->id, $startDate, $endDate);
                if ($report['status']) {
                    $shopReports[] = $report['data'];
                }
            }

            return [
                'status' => true,
                'message' => 'التقرير الشامل',
                'data' => [
                    'admin_report' => $adminReport['data'] ?? null,
                    'deliveries_reports' => $deliveryReports,
                    'shops_reports' => $shopReports,
                    'summary' => [
                        'total_deliveries' => count($deliveryReports),
                        'total_shops' => count($shopReports),
                        'period' => [
                            'start_date' => $startDate,
                            'end_date' => $endDate
                        ]
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Comprehensive report failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب التقرير الشامل'
            ];
        }
    }
}
