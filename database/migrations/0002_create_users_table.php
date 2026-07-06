<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->id();
            $table->enum('type', ['email', 'facebook', 'loca', 'phone'])->nullable()->index();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->enum('role', ['user', 'admin', 'staff'])->index()->default('user');
            $table->string('phone', 255)->nullable()->comment('20XXXXXXXX');
            $table->string('name', 255)->nullable()->index();
            $table->enum('gender', ['M', 'F', 'L'])->nullable()->comment('M: male, F: female, L: LGBTQIA+');
            $table->date('dob')->nullable();
            $table->string('province', 2)->nullable()->comment('http://www.statoids.com/ula.html');
            $table->string('district', 255)->nullable();
            $table->string('village', 255)->nullable();
            $table->dateTime('banned_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->text('remark')->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->unique(['tenant_id', 'email'], 'users_email_unique');
            $table->unique(['tenant_id', 'phone'], 'users_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIfExists();
        });
    }
};
