// Product Customization Button
document.addEventListener('DOMContentLoaded', function() {
    console.log('Customization button script loaded');
    
    const btn = document.getElementById('customize-btn');
    if (!btn) {
        console.log('Customize button not found');
        return;
    }
    
    const productId = btn.dataset.productId;
    console.log('Product ID:', productId);
    
    btn.addEventListener('click', function() {
        console.log('Button clicked');
        
        // Check if product has print areas
        fetch('/api/product/' + productId + '/print-areas')
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                console.log('Print areas:', data);
                
                if (data.areas && data.areas.length > 0) {
                    window.location.href = '/customization/designer/' + productId;
                } else {
                    alert('此商品暂不支持定制服务');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('加载失败，请稍后重试');
            });
    });
});
