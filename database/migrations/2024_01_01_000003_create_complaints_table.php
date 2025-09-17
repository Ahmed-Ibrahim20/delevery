<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            
            // نص الشكوى
            $table->text('complaint_text');
            
            // المستخدم الذي أضاف الشكوى
            $table->unsignedBigInteger('user_id');
            
            // حالة الشكوى (0: جديدة, 1: قيد المراجعة, 2: مكتملة, 3: مرفوضة)
            $table->unsignedTinyInteger('status')->default(0);
            
            // ملاحظات الإدارة
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Index for better performance
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
