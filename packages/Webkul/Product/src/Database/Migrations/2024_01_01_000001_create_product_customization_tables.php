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
        // 商品图片可打印区域表
        Schema::create('product_image_print_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_image_id');
            $table->foreign('product_image_id')->references('id')->on('product_images')->onDelete('cascade');
            $table->string('name')->nullable()->comment('区域名称');
            $table->decimal('x', 8, 4)->default(0)->comment('X坐标百分比');
            $table->decimal('y', 8, 4)->default(0)->comment('Y坐标百分比');
            $table->decimal('width', 8, 4)->default(20)->comment('宽度百分比');
            $table->decimal('height', 8, 4)->default(20)->comment('高度百分比');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();
            
            $table->index(['product_image_id', 'is_active']);
        });

        // 购物车项定制数据表
        Schema::create('cart_item_customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_item_id')->constrained()->onDelete('cascade');
            $table->json('design_data')->comment('设计数据JSON');
            $table->string('preview_image')->nullable()->comment('预览图路径');
            $table->timestamps();
            
            $table->unique(['cart_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_image_print_areas');
        Schema::dropIfExists('cart_item_customizations');
    }
};
