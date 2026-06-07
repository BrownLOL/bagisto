<div id="test-customization">
    <p>Customization Test - Product ID: <?php echo $product->id; ?></p>
    
    <button id="test-btn" class="px-4 py-2 bg-blue-500 text-white rounded">
        Test Button
    </button>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('test-btn');
            btn.addEventListener('click', function() {
                console.log('Button clicked');
            });
        });
    </script>
</div>