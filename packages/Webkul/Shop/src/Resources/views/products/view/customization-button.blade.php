<div id="test-customization">
    <p>Customization Test - Product ID: <?php echo $product->id; ?></p>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Test script loaded for product: ' + document.querySelector('#test-customization p').textContent);
        });
    </script>
</div>