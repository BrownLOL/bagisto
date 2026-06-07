<div id="customization-app" style="margin: 20px 0;">
    <button
        id="customize-btn"
        data-product-id="{{ $product->id }}"
        style="display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;"
    >
        立即定制
    </button>
</div>

<!-- Customization Dialog -->
<div id="customization-dialog" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow: auto; padding: 24px; position: relative;">
        <button id="close-dialog" style="position: absolute; top: 12px; right: 12px; background: none; border: none; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        <h2 style="margin: 0 0 20px 0; font-size: 20px; font-weight: bold;">商品定制</h2>
        <div id="dialog-content">
            <p style="text-align: center; padding: 40px;">加载中...</p>
        </div>
    </div>
</div>

<script>
(function() {
    console.log('Customization script loaded');
    
    var btn = document.getElementById('customize-btn');
    var dialog = document.getElementById('customization-dialog');
    var closeBtn = document.getElementById('close-dialog');
    var dialogContent = document.getElementById('dialog-content');
    
    console.log('Elements found:', {btn: !!btn, dialog: !!dialog, closeBtn: !!closeBtn, content: !!dialogContent});
    
    if (!btn || !dialog) {
        console.error('Customization elements not found');
        return;
    }
    
    // Open dialog on button click
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var productId = this.getAttribute('data-product-id');
        console.log('Button clicked, product ID:', productId);
        
        dialog.style.display = 'flex';
        
        // Load print areas
        dialogContent.innerHTML = '<p style="text-align: center; padding: 40px;">加载中...</p>';
        
        fetch('/customization/print-areas/' + productId)
            .then(function(response) { 
                console.log('API status:', response.status);
                return response.json(); 
            })
            .then(function(data) {
                console.log('API response:', data);
                
                if (data.data && data.data.length > 0) {
                    var html = '<p style="margin-bottom: 16px;">该商品支持定制区域 ' + data.data.length + ' 个</p>';
                    html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px;">';
                    
                    data.data.forEach(function(item) {
                        console.log('Item:', item);
                        var imgUrl = item.image_url || item.url || item.src || item.product_image_path || 'https://via.placeholder.com/150';
                        html += '<div style="border: 1px solid #ddd; border-radius: 8px; padding: 12px; text-align: center; cursor: pointer;" onclick="window.location.href=\'/customization/designer/' + productId + '\'">';
                        html += '<img src="' + imgUrl + '" style="width: 100%; height: 120px; object-fit: contain; margin-bottom: 8px;" onerror="this.src=\'https://via.placeholder.com/150\'" />';
                        html += '<p style="margin: 0; font-size: 14px;">区域 ' + item.id + '</p>';
                        html += '</div>';
                    });
                    
                    html += '</div>';
                    html += '<p style="margin-top: 16px; color: #666; font-size: 14px;">点击选择要定制的区域</p>';
                } else {
                    html = '<p style="text-align: center; padding: 40px; color: #666;">此商品暂不支持定制服务</p>';
                }
                
                dialogContent.innerHTML = html;
            })
            .catch(function(error) {
                console.error('Error:', error);
                dialogContent.innerHTML = '<p style="text-align: center; padding: 40px; color: red;">加载失败，请重试</p>';
            });
    });
    
    // Close dialog
    closeBtn.addEventListener('click', function() {
        dialog.style.display = 'none';
    });
    
    // Close on background click
    dialog.addEventListener('click', function(e) {
        if (e.target === dialog) {
            dialog.style.display = 'none';
        }
    });
    
    console.log('Customization script initialized');
})();
</script>
