<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            
            // المستخدم اللي هيستقبل الإشعار
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // النوع (admin, driver, shop) لو عايز تبعت Role-based
            $table->unsignedTinyInteger('target_role')->nullable()
                  ->comment('0: admin, 1: driver, 2: shop');

            // العنوان والنص
            $table->string('title', 255)->nullable();
            $table->text('message')->nullable();

         //   $table->string('notifiable_type')->nullable();
           // $table->unsignedBigInteger('notifiable_id')->nullable();
     

            // ربط الإشعار بالكيان (مثلاً أوردر أو شكوى)
            $table->morphs('notifiable'); 
            // دا بيعمل: notifiable_id + notifiable_type (مثلاً order_id / complaint_id)

            // هل الإشعار اتقري
            $table->boolean('is_read')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
