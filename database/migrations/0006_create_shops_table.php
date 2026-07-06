<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->id();
            $table->string('name', 255);
            $table->json('images');
            $table->decimal('normal_price', 10, 2)->nullable()->comment('ລາຄາທີ່ສະແດງວ່າກ່ອນຫຼຸດແມ່ນເທົ່າໃດ');
            $table->decimal('price', 10, 2)->comment('ລາຄາຂາຍໂຕຈິງ');
            $table->string('short_description', 500)->nullable();
            $table->text('long_description')->nullable();
            $table->string('sku', 255);
            $table->integer('total_unit')->nullable();
            $table->string('unit_type', 255)->nullable();
            $table->string('storage', 255)->nullable();
            $table->integer('sort_no')->nullable();
            $table->unsignedBigInteger('total_search')->default(0)->index();
            $table->unsignedInteger('available_quantity')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->text('remark')->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['tenant_id', 'sku'], 'tenant_sku_unique');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');
        });

        Schema::create('shop_orders', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->string('id', 255)->primary();
            $table->unsignedBigInteger('user_id');
            $table->decimal('order_amount', 10, 2);
            $table->decimal('shipping_amount', 10, 2);
            $table->decimal('coupon_amount', 10, 2)->default(0)->comment('ສ່ວນຫຼຸດຈາກຄູປອງ');
            $table->decimal('payment_amount', 10, 2);
            $table->string('payment_uuid', 255)->unique();
            $table->enum('payment_status', ['pending', 'paid', 'expired', 'cancelled', 'refunded'])->index();
            $table->dateTime('payment_expired_at')->nullable();
            $table->dateTime('payment_reconciled_at')->nullable();
            $table->string('payment_channel')->nullable()->comment('bcel, jdb');
            $table->enum('shipping_fee_type', ['cod', 'free', 'prepaid'])->nullable();
            $table->string('shipping_channel')->comment('hal')->nullable()->index();
            $table->string('shipping_channel_name')->nullable();
            $table->string('shipping_name', 255);
            $table->string('shipping_phone', 255);
            $table->string('shipping_province', 255);
            $table->string('shipping_district', 255);
            $table->string('shipping_village', 255);
            $table->text('shipping_remark')->nullable();
            $table->string('shipping_branch_province', 255)->nullable()->index();
            $table->string('shipping_branch_district', 255)->nullable()->index();
            $table->string('shipping_branch_name', 255)->nullable()->index();
            $table->json('shipping_detail')->nullable();
            $table->string('shipping_tracking_number')->nullable()->unique();
            $table->enum('shipping_status', ['pending', 'shipping', 'completed'])->nullable()->index();
            $table->json('generate_qr_request')->nullable();
            $table->json('generate_qr_response')->nullable();
            $table->string('order_code', 255)->nullable()->index();
            $table->string('campaign_code', 255)->nullable()->index();
            $table->unsignedInteger('total_product_quantity')->nullable();
            $table->unsignedInteger('total_shipping_quantity')->nullable();
            $table->dateTime('notified_invoice_api_at')->nullable()->index();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->index(['payment_status', 'payment_expired_at', 'payment_reconciled_at'], 'payment_reconcile');
        });

        Schema::create('shop_order_details', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->id();
            $table->string('shop_order_id');
            $table->unsignedBigInteger('shop_product_id');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2);
            $table->string('name')->nullable();
            $table->json('images')->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('shop_order_id')
                ->references('id')
                ->on('shop_orders')
                ->onDelete('restrict');

            $table->foreign('shop_product_id')
                ->references('id')
                ->on('shop_products')
                ->onDelete('restrict');

            $table->unique(['shop_order_id', 'shop_product_id'], 'shop_order_unique');
        });

        Schema::create('shop_order_payments', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->id();
            $table->string('shop_order_id');
            $table->string('channel')->index();
            $table->string('merchant_provider')->index();
            $table->string('merchant_id')->index();
            $table->decimal('amount', 10, 2);
            $table->string('xref')->nullable()->unique();
            $table->string('ref')->unique();
            $table->dateTime('reconciled_at')->nullable();
            $table->enum('type', ['payment', 'shipping_fee'])->index();
            $table->json('response')->nullable();
            $table->string('remark', 1000)->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('shop_order_id')
                ->references('id')
                ->on('shop_orders')
                ->onDelete('restrict');
        });

        Schema::create('shop_order_logs', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->id();
            $table->string('shop_order_id', 255)->index();
            $table->string('type', 255)->index();
            $table->json('detail')->nullable();
            $table->decimal('response_time', 10, 3)->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('shop_order_id')
                ->references('id')
                ->on('shop_orders')
                ->onDelete('restrict');
        });

        Schema::create('shop_user_searches', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('search_term', 255)->index();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });

        // ================================================================================
        // Shipping Rules
        // ================================================================================

        Schema::create('shop_shipping_rules', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->id();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->decimal('minimum_amount', 10, 2);
            $table->enum('shipping_fee_type', ['cod', 'free', 'prepaid']);
            $table->string('shipping_days_text', 255);
            $table->text('remark')->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();

            $table->index(['started_at', 'ended_at'], 'shipping_rules_duration_index');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');
        });

        // In the SQL standard, a SQLSTATE code is a five-character string where:
        // The first two characters form the class
        // The last three characters form the subclass
        // – Class 45 is reserved for "unhandled user-defined exception"
        // – Subclass 000 is the generic "no further information" subclass
        // Putting that together, 45000 is the generic user-defined exception code

        DB::unprepared(<<<SQL
            CREATE TRIGGER prevent_overlap_insert
            BEFORE INSERT ON shop_shipping_rules
            FOR EACH ROW
            BEGIN
              IF NEW.status = 'active' AND EXISTS (
                SELECT 1
                  FROM shop_shipping_rules
                 WHERE tenant_id  = NEW.tenant_id
                   AND status     = 'active'
                   AND NEW.started_at < ended_at
                   AND NEW.ended_at   > started_at
              ) THEN
                SIGNAL SQLSTATE '45000'
                  SET MESSAGE_TEXT = 'Active time range overlaps an existing active record for this tenant';
              END IF;
            END;
            SQL
        );

        DB::unprepared(<<<SQL
            CREATE TRIGGER prevent_overlap_update
            BEFORE UPDATE ON shop_shipping_rules
            FOR EACH ROW
            BEGIN
              IF NEW.status = 'active' AND EXISTS (
                SELECT 1
                  FROM shop_shipping_rules
                 WHERE tenant_id  = NEW.tenant_id
                   AND status     = 'active'
                   AND id         != OLD.id
                   AND NEW.started_at < ended_at
                   AND NEW.ended_at   > started_at
              ) THEN
                SIGNAL SQLSTATE '45000'
                  SET MESSAGE_TEXT = 'Active time range overlaps an existing active record for this tenant';
              END IF;
            END;
            SQL
        );

        // ================================================================================
        // Product Stocks
        // ================================================================================

        Schema::create('shop_product_stocks', function (Blueprint $table) {
            $table->string('tenant_id', 255);
            $table->id();
            $table->string('shop_order_id')->nullable();
            $table->unsignedBigInteger('shop_product_id');
            $table->integer('quantity');
            $table->text('remark')->nullable();
            $table->string('xref', 255)->nullable()->unique();
            $table->dateTime('created_at')->useCurrent()->index();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict');

            $table->foreign('shop_order_id')
                ->references('id')
                ->on('shop_orders')
                ->onDelete('restrict');

            $table->foreign('shop_product_id')
                ->references('id')
                ->on('shop_products')
                ->onDelete('restrict');
        });

        // ================================================================================
        // Stored Procedure: Update Product Available Quantity
        // ================================================================================

        DB::unprepared('DROP PROCEDURE IF EXISTS update_product_available_quantity;');

        DB::unprepared(<<<SQL
            CREATE PROCEDURE update_product_available_quantity(
                IN p_product_id BIGINT UNSIGNED,
                IN p_quantity INT,
                IN p_type ENUM('UPDATE', 'SET'),
                IN p_remark TEXT,
                OUT p_success BOOLEAN,
                OUT p_message TEXT
            )
            BEGIN
                DECLARE v_error_code CHAR(5) DEFAULT '00000';
                DECLARE v_error_msg TEXT;
                DECLARE v_current_quantity INT DEFAULT NULL;
                DECLARE v_new_quantity INT DEFAULT NULL;
                DECLARE v_quantity_change INT DEFAULT NULL;
                DECLARE v_tenant_id VARCHAR(255);

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    GET DIAGNOSTICS CONDITION 1
                        v_error_code = RETURNED_SQLSTATE,
                        v_error_msg = MESSAGE_TEXT;

                    ROLLBACK;
                    SET p_success = FALSE;
                    SET p_message = CONCAT('Error [', v_error_code, ']: ', v_error_msg);
                END;

                START TRANSACTION;

                -- Lock the product row for update to prevent race conditions
                -- This acquires an exclusive lock on the row until the transaction completes
                SELECT available_quantity, tenant_id INTO v_current_quantity, v_tenant_id
                FROM shop_products
                WHERE id = p_product_id
                FOR UPDATE;

                -- Check if product exists
                IF v_current_quantity IS NULL THEN
                    ROLLBACK;
                    SET p_success = FALSE;
                    SET p_message = 'Error: Product not found';
                ELSE
                    -- Calculate new quantity based on type
                    IF p_type = 'UPDATE' THEN
                        -- UPDATE: Add/subtract p_quantity from current quantity
                        SET v_new_quantity = v_current_quantity + p_quantity;
                        SET v_quantity_change = p_quantity;
                    ELSE
                        -- SET: Set quantity to p_quantity
                        SET v_new_quantity = p_quantity;
                        SET v_quantity_change = p_quantity - v_current_quantity;
                    END IF;

                    -- Check if new quantity would be negative
                    IF v_new_quantity < 0 THEN
                        ROLLBACK;
                        SET p_success = FALSE;
                        SET p_message = CONCAT('Error: Invalid quantity. Current quantity: ', v_current_quantity, ', requested value: ', p_quantity, ', type: ', p_type);
                    ELSE
                        -- Update the available_quantity
                        UPDATE shop_products
                        SET available_quantity = v_new_quantity,
                            updated_at = NOW()
                        WHERE id = p_product_id;

                        -- Insert stock movement record with the actual change amount
                        INSERT INTO shop_product_stocks (
                            tenant_id,
                            shop_order_id,
                            shop_product_id,
                            quantity,
                            remark,
                            created_at,
                            updated_at
                        ) VALUES (
                            v_tenant_id,
                            NULL,
                            p_product_id,
                            v_quantity_change,
                            p_remark,
                            NOW(),
                            NOW()
                        );

                        COMMIT;
                        SET p_success = TRUE;

                        IF p_type = 'UPDATE' THEN
                            IF p_quantity > 0 THEN
                                SET p_message = CONCAT('Success: Stock increased by ', p_quantity, '. New quantity: ', v_new_quantity);
                            ELSE
                                SET p_message = CONCAT('Success: Stock decreased by ', ABS(p_quantity), '. New quantity: ', v_new_quantity);
                            END IF;
                        ELSE
                            SET p_message = CONCAT('Success: Stock set to ', v_new_quantity, '. Previous quantity: ', v_current_quantity);
                        END IF;
                    END IF;
                END IF;
            END;
            SQL
        );
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIfExists();
        });

        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropForeign(['tenant_id', 'user_id']);
            $table->dropIndex(['payment_reconcile']);
            $table->dropIfExists();
        });

        Schema::table('shop_order_details', function (Blueprint $table) {
            $table->dropForeign(['tenant_id', 'shop_order_id', 'shop_product_id']);
            $table->dropUnique(['shop_order_unique']);
            $table->dropIfExists();
        });

        Schema::table('shop_order_payments', function (Blueprint $table) {
            $table->dropForeign(['tenant_id', 'shop_order_id']);
            $table->dropIfExists();
        });

        Schema::table('shop_order_logs', function (Blueprint $table) {
            $table->dropForeign(['tenant_id', 'order_id']);
            $table->dropIfExists();
        });

        Schema::table('shop_user_searches', function (Blueprint $table) {
            $table->dropForeign(['tenant_id', 'user_id']);
            $table->dropIfExists();
        });

        // ================================================================================
        // Shipping Rules
        // ================================================================================

        DB::unprepared('DROP TRIGGER IF EXISTS prevent_overlap_insert;');
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_overlap_update;');

        Schema::table('shop_shipping_rules', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIfExists();
        });

        // ================================================================================
        // Product Stocks
        // ================================================================================

        DB::unprepared('DROP PROCEDURE IF EXISTS update_product_available_quantity;');

        Schema::table('shop_product_stocks', function (Blueprint $table) {
            $table->dropForeign(['tenant_id', 'shop_order_id', 'shop_product_id']);
            $table->dropIfExists();
        });
    }
};
