// Simple customization button
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('customize-btn');
    if (btn) {
        btn.addEventListener('click', function() {
            console.log('Customize clicked');
            alert('Customize clicked - Product ID: ' + window.productId);
        });
    }
});
