<?php $productId = $product->id; ?>
<script>
    window.productId = <?php echo $productId; ?>;
</script>

<div id="customization-app">
    <button
        id="customize-btn"
        class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-darker transition-colors"
    >
        立即定制
    </button>
</div>
