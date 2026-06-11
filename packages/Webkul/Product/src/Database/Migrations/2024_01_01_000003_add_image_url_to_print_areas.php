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
        // 添加 image_url 字段，存储图片完整URL
        Schema::table('product_image_print_areas', function (Blueprint $table) {
            $table->string('image_url', 500)->nullable()->after('product_image_id');
        });

        // 填充现有数据：将关联的图片URL填充到 image_url 字段
        DB::statement(<<<SQL
            UPDATE product_image_print_areas pa
            INNER JOIN product_images pi ON pa.product_image_id = pi.id
            SET pa.image_url = CONCAT('/storage/', pi.path)
            WHERE pa.image_url IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_image_print_areas', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
    }
};
