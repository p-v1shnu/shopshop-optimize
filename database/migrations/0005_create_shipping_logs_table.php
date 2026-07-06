<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 255)->nullable()->index();
            $table->string('provider_reference', 255)->nullable()->index();
            $table->string('type')->nullable()->index();
            $table->json('data');
            $table->decimal('response_time', 10, 2)->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_logs', function (Blueprint $table) {
            $table->dropIfExists();
        });
    }
};
