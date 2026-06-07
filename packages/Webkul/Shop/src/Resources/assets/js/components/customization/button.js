document.addEventListener('DOMContentLoaded', function() {
    console.log('Customization initialized');
    
    var btn = document.getElementById('customize-btn');
    if (btn) {
        btn.addEventListener('click', function() {
            console.log('Customize button clicked');
        });
    }
});
