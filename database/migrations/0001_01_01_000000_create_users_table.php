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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // بيانات أساسية
            $table->string('name', 100);
            $table->string('phone', 20)->unique();
            $table->string('email', 150)->unique()->nullable();
            $table->string('image')->nullable(); // مسار أو اسم الصورة

            // الصلاحية: admin – driver – shop
            $table->unsignedTinyInteger('role')
                ->default(2) // 2 = shop
                ->comment('0: admin, 1: driver, 2: shop, 3: other');

            $table->string('catogrey')->nullable();
            // كلمة المرور
            $table->string('password')->nullable();

            // عنوان المستخدم
            $table->string('address')->nullable();

            // موافقة / رفض
            $table->boolean('is_approved')->default(false);

            // حالة النشاط
            $table->boolean('is_active')->default(true);

            //لي الدليفري 
            $table->boolean('is_available')->default(true);

            // النسبة (للمحصل أو المحل)
            $table->decimal('commission_percentage', 5, 2)->nullable();

            $table->unsignedBigInteger('user_add_id')->nullable();

            // صورة
            $table->string('avatar')->nullable();

            // ملاحظات
            $table->text('notes')->nullable();

            // لتتبع من أضاف المستخدم
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('user_add_id')->references('id')->on('users')->onDelete('set null');
        });


        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
