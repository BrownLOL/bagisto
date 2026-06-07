document.addEventListener('DOMContentLoaded', function() {
    console.log('Customization button script loaded');
    
    var btn = document.getElementById('customize-btn');
    var productId = window.productId;
    
    if (!btn || !productId) {
        console.log('Customization button or product ID not found');
        return;
    }
    
    btn.addEventListener('click', function() {
        console.log('Opening customization designer for product:', productId);
        
        // Check if product has print areas
        fetch('/api/product/' + productId + '/print-areas')
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function(data) {
                console.log('Print areas response:', data);
                
                if (data.print_areas && data.print_areas.length > 0) {
                    // Has print areas, open designer
                    window.location.href = '/customization/designer/' + productId;
                } else {
                    // No print areas, show alert
                    alert('此商品暂不支持定制服务');
                }
            })
            .catch(function(error) {
                console.error('Error fetching print areas:', error);
                // Still open designer even if API fails
                window.location.href = '/customization/designer/' + productId;
            });
    });
});
