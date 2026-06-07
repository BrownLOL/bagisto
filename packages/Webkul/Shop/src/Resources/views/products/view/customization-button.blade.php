<div id="customization-app" style="margin: 20px 0;">
    <button
        id="customize-btn"
        data-product-id="{{ $product->id }}"
        style="display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;"
    >
        立即定制
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Customization button script loaded');
    
    var btn = document.getElementById('customize-btn');
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var productId = this.getAttribute('data-product-id');
            console.log('Customize clicked, product ID:', productId);
            
            window.location.href = '/customization/designer/' + productId;
        });
    }
});
</script>
