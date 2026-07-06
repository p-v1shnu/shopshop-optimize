<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_coupons', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->increments('id');
            $table->enum('status', ['active', 'inactive', 'expired', 'sold_out'])->default('active')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('null = public coupon, user_id = private coupon for specific user');
            $table->string('code')->comment('Coupon code in plain text');
            $table->enum('type', ['fixed', 'percentage'])->default('fixed')->comment('Discount type');
            $table->decimal('amount', 10, 2)->comment('Discount amount or percentage value');
            $table->dateTime('started_at')->nullable()->index();
            $table->dateTime('ended_at')->nullable()->index();
            $table->unsignedInteger('total_quantity')->default(0)->comment('Total coupon quantity, 1000000 = unlimited');
            $table->unsignedInteger('available_quantity')->default(0)->comment('Available coupon quantity');
            $table->unsignedInteger('user_daily_limit')->default(0)->comment('Max usage per user per day, 1000000 = unlimited');
            $table->decimal('minimum_order_amount', 10, 2)->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->text('remark')->nullable();

            $table->unique(['tenant_id', 'code'], 'tenant_coupon_code_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // Add triggers to validate coupon amount based on type
        DB::unprepared("
            CREATE TRIGGER shop_coupons_validate_amount_before_insert
            BEFORE INSERT ON shop_coupons
            FOR EACH ROW
            BEGIN
                -- Check amount cannot be less than zero for any type
                IF NEW.amount < 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Coupon amount cannot be less than zero';
                END IF;

                -- For percentage type, amount must be between 0 and 100
                IF NEW.type = 'percentage' AND NEW.amount > 100 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Percentage coupon amount must be between 0 and 100';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER shop_coupons_validate_amount_before_update
            BEFORE UPDATE ON shop_coupons
            FOR EACH ROW
            BEGIN
                -- Check amount cannot be less than zero for any type
                IF NEW.amount < 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Coupon amount cannot be less than zero';
                END IF;

                -- For percentage type, amount must be between 0 and 100
                IF NEW.type = 'percentage' AND NEW.amount > 100 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Percentage coupon amount must be between 0 and 100';
                END IF;
            END
        ");

        // ================================================================================
        // Shop Order Coupons (Audit Trail)
        // ================================================================================

        Schema::create('shop_order_coupons', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->increments('id');
            $table->string('shop_order_id', 255)->index();
            $table->unsignedInteger('shop_coupon_id')->index();
            $table->unsignedBigInteger('user_id')->index();

            // Snapshot of coupon data at time of use
            $table->string('coupon_code');
            $table->enum('coupon_type', ['fixed', 'percentage']);
            $table->decimal('coupon_amount', 10, 2)->comment('Coupon value (amount or percentage)');
            $table->decimal('discount_amount', 10, 2)->comment('Actual discount applied to order');
            $table->decimal('before_discount_amount', 10, 2)->comment('Order amount before discount was applied');
            $table->dateTime('started_at')->nullable()->comment('Coupon valid start date at time of use');
            $table->dateTime('ended_at')->nullable()->comment('Coupon expiry date at time of use');
            $table->integer('user_daily_limit')->comment('Daily limit per user at time of use');
            $table->decimal('minimum_order_amount', 10, 2);

            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->text('remark')->nullable();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('shop_order_id')
                ->references('id')
                ->on('shop_orders')
                ->onDelete('restrict');

            $table->foreign('shop_coupon_id')
                ->references('id')
                ->on('shop_coupons')
                ->onDelete('restrict');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            // Ensure one coupon per order
            $table->unique(['shop_order_id', 'shop_coupon_id'], 'unique_order_coupon');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_order_coupons');

        // Drop triggers before dropping the table
        DB::unprepared('DROP TRIGGER IF EXISTS shop_coupons_validate_amount_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS shop_coupons_validate_amount_before_update');

        Schema::dropIfExists('shop_coupons');
    }
};
