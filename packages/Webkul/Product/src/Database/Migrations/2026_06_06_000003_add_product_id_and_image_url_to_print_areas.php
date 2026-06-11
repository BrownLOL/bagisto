<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 添加 product_id 和 image_url 字段
        Schema::table('product_image_print_areas', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable()->after('product_image_id');
            $table->string('image_url', 500)->nullable()->after('product_id');
        });

        // 回填 product_id：根据 product_image_id 关联 product_images 表获取 product_id
        DB::statement(<<<SQL
            UPDATE product_image_print_areas pa
            INNER JOIN product_images pi ON pa.product_image_id = pi.id
            SET pa.product_id = pi.product_id
            WHERE pa.product_id IS NULL
        SQL);

        // 回填 image_url：将关联的图片URL填充到 image_url 字段
        DB::statement(<<<SQL
            UPDATE product_image_print_areas pa
            INNER JOIN product_images pi ON pa.product_image_id = pi.id
            SET pa.image_url = CONCAT('/storage/', pi.path)
            WHERE pa.image_url IS NULL
        SQL);

        // 删除 product_image_id 列和外键约束
        Schema::table('product_image_print_areas', function (Blueprint $table) {
            $table->dropForeign(['product_image_id']);
            $table->dropColumn('product_image_id');
        });

        // 将 product_id 改为非空（回填后应该都有值）
        Schema::table('product_image_print_areas', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable(false)->change();
            $table->index('product_id', 'idx_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_image_print_areas', function (Blueprint $table) {
            $table->dropColumn(['product_id', 'image_url']);
        });
    }
};
