<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 255)->index();
            $table->string('message', 500)->nullable();
            $table->json('detail')->nullable();
            $table->decimal('response_time', 10, 3)->nullable();
            $table->text('remark')->nullable();
            $table->string('model', 255)->nullable()->index();
            $table->string('model_id', 255)->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropIfExists();
        });
    }
};
