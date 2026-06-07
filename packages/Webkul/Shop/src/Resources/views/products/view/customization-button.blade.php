<!-- Product Customization Button -->
<div class="customization-section" id="customization-section" style="display: none; margin: 15px 0;">
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

<script>
    // Apply button styles
    document.getElementById('customize-btn')?.addEventListener('load', function() {
        this.style.cssText = 'width:100%;padding:15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s ease;box-shadow:0 4px 15px rgba(102,126,234,0.4);';
    });

    // Global variable to store product info
    window.productCustomizationData = {
        productId: @json($product->id),
        printAreas: [],
        images: []
    };

    // Load print areas data
    async function loadPrintAreas() {
        try {
            const response = await fetch('/customization/api/print-areas/' + window.productCustomizationData.productId);
            const data = await response.json();

            if (data.success && data.data && data.data.length > 0) {
                window.productCustomizationData.printAreas = data.data;
                const section = document.getElementById('customization-section');
                if (section) section.style.display = 'block';

                const btn = document.getElementById('customize-btn');
                if (btn) {
                    btn.style.cssText = 'width:100%;padding:15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s ease;box-shadow:0 4px 15px rgba(102,126,234,0.4);';
                }
            }
        } catch (error) {
            console.error('Failed to load print areas:', error);
        }
    }

    // Expose method to open modal
    window.openCustomizationModal = function() {
        window.dispatchEvent(new CustomEvent('open-customization-modal'));
    };

    // Load data on page load
    document.addEventListener('DOMContentLoaded', loadPrintAreas);
</script>
