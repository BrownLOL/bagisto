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
        // 订单项添加定制数据字段
        if (!Schema::hasColumn('order_items', 'customization_data')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->json('customization_data')->nullable()->after('additional');
            });
        }

        // 发票项添加定制数据字段
        if (!Schema::hasColumn('invoice_items', 'customization_data')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->json('customization_data')->nullable()->after('additional');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'customization_data')) {
                $table->dropColumn('customization_data');
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'customization_data')) {
                $table->dropColumn('customization_data');
            }
        });
    }
};
