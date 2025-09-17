<?php
// database/migrations/2025_08_20_000000_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // بيانات العميل الأساسية
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->string('customer_address');

            // بيانات الطلب البسيطة
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->unsignedBigInteger('user_add_id')->nullable();

             // نسبة التطبيق ومبلغها
            $table->decimal('application_percentage', 5, 2)->default(0)->comment('النسبة المئوية للتطبيق');
            $table->decimal('application_fee', 10, 2)->default(0)->comment('المبلغ الناتج عن النسبة');

            // حالة الطلب
            $table->unsignedTinyInteger('status')
                ->default(0) // 0 = pending
                ->comment('0: pending, 1: delivered, 2: cancelled, 3:complete');
            $table->unsignedBigInteger('delivery_id')->nullable();

            // ملاحظات اختيارية
            $table->string('notes', 500)->nullable();

            $table->timestamps();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_add_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('delivery_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
