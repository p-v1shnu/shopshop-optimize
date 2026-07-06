<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 255)->index();
            $table->string('provider_reference', 255)->nullable()->index();
            $table->string('msisdn', 11)->index();
            $table->string('otp', 255);
            $table->json('data');
            $table->dateTime('expired_at')->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('otp_logs', function (Blueprint $table) {
            $table->dropIfExists();
        });
    }
};
