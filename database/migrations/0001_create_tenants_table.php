<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->boolean('enable_shop')->default(true)->nullable();
            $table->boolean('enable_coupon')->default(false)->nullable();
            $table->string('order_invoice_webhook_url', 255)->nullable();
            $table->string('site_logo_url', 255)->nullable();
            $table->string('facebook_name', 255)->nullable();
            $table->string('facebook_url', 255)->nullable();
            $table->string('facebook_cover_url', 255)->nullable();
            $table->json('delivery_contact_phone')->nullable();
            $table->string('support_contact_phone', 255)->nullable();
            $table->string('otp_site_name', 255)->nullable();
            $table->string('contact_url', 255)->nullable();
            $table->string('footer_more_info_text', 255)->nullable();
            $table->string('footer_more_info_link', 255)->nullable();
            $table->json('homepage_banners')->nullable();
            $table->json('popup_banners')->nullable();
            $table->text('head_html')->nullable();
            $table->string('google_tag_manager_id', 255)->nullable();
            $table->string('google_analytics_id', 255)->nullable();
            $table->string('maintenance_mode', 255)->nullable();
            $table->json('allow_province_ids')->nullable();
            $table->json('shipping_channels')->nullable();
            $table->text('no_shipping_instruction_text')->nullable();
            $table->text('no_shipping_order_paid_text')->nullable();
            $table->string('latitude', 255)->nullable();
            $table->string('longitude', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->json('data')->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('domains', function (Blueprint $table) {
            $table->string('tenant_id');
            $table->increments('id');
            $table->string('domain', 255)->unique();
            $table->dateTime('created_at')->index();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIfExists();
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIfExists();
        });
    }
};
