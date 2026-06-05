<!-- Product Customization Button -->
<div class="customization-section" id="customization-section" style="display: none;">
    <button
        class="customize-btn"
        id="customize-btn"
        onclick="window.openCustomizationModal()"
    >
        {{ __('shop::app.products.customize') }}
    </button>
</div>

<!-- Customization Modal Container -->
<div id="customization-modal-container"></div>

<style>
    .customization-section {
        margin: 15px 0;
    }

    .customize-btn {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .customize-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }
</style>

<script>
    // 全局变量存储商品信息
    window.productCustomizationData = {
        productId: {{ $product->id }},
        printAreas: [],
        images: []
    };

    // 加载打印区域数据
    async function loadPrintAreas() {
        try {
            const response = await fetch(`/customization/api/print-areas/${window.productCustomizationData.productId}`);
            const data = await response.json();

            if (data.success && data.data && data.data.length > 0) {
                window.productCustomizationData.printAreas = data.data;
                document.getElementById('customization-section').style.display = 'block';
            }
        } catch (error) {
            console.error('Failed to load print areas:', error);
        }
    }

    // 暴露给外部的打开弹窗方法
    window.openCustomizationModal = function() {
        window.dispatchEvent(new CustomEvent('open-customization-modal'));
    };

    // 页面加载时获取数据
    document.addEventListener('DOMContentLoaded', loadPrintAreas);
</script>
