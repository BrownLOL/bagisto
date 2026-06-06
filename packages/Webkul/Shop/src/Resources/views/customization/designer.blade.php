<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('shop::app.products.customization.designer-title') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fabric@5.3.0/dist/fabric.min.js"></script>
    <style>
        .print-area-highlight {
            border: 2px dashed #3b82f6;
            background-color: rgba(59, 130, 246, 0.1);
        }
        .canvas-container {
            display: inline-block;
        }
        .toolbar-btn {
            @apply px-4 py-2 rounded transition-colors;
        }
        .toolbar-btn:hover {
            @apply bg-blue-100;
        }
        .toolbar-btn.active {
            @apply bg-blue-500 text-white;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">{{ __('shop::app.products.customization.designer-title') }}</h1>
            
            <div class="flex flex-wrap gap-8">
                <!-- Left: Product Image with Print Areas -->
                <div class="flex-1 min-w-0">
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">{{ __('shop::app.products.customization.select-area') }}</h3>
                        <div id="print-areas-container" class="relative inline-block border border-gray-300 rounded">
                            <img id="product-image" src="" alt="Product" class="max-w-full">
                            <div id="print-areas-overlay" class="absolute inset-0 pointer-events-none"></div>
                        </div>
                    </div>
                    
                    <!-- Preview Section -->
                    <div id="preview-section" class="hidden">
                        <h3 class="font-semibold mb-2">{{ __('shop::app.products.customization.preview') }}</h3>
                        <div class="border border-gray-300 rounded p-4 bg-gray-50">
                            <canvas id="preview-canvas" width="400" height="400"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Tools and Options -->
                <div class="w-full md:w-80">
                    <!-- Print Areas List -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">{{ __('shop::app.products.customization.print-areas') }}</h3>
                        <div id="areas-list" class="space-y-2">
                            <!-- Print areas will be listed here -->
                        </div>
                        @if(count($printAreas) == 0)
                            <p class="text-gray-500 text-sm">{{ __('shop::app.products.customization.no-areas') }}</p>
                        @endif
                    </div>
                    
                    <!-- Upload Section -->
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">{{ __('shop::app.products.customization.upload-image') }}</h3>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                            <input type="file" id="custom-image-input" accept="image/*" class="hidden">
                            <label for="custom-image-input" class="cursor-pointer">
                                <div class="text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p>{{ __('shop::app.products.customization.click-to-upload') }}</p>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Text Tool -->
                        <div class="mt-4">
                            <button id="add-text-btn" class="toolbar-btn w-full border border-gray-300">
                                {{ __('shop::app.products.customization.add-text') }}
                            </button>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="space-y-3">
                        <button id="preview-btn" class="toolbar-btn w-full bg-blue-500 text-white hover:bg-blue-600" disabled>
                            {{ __('shop::app.products.customization.preview') }}
                        </button>
                        <button id="add-to-cart-btn" class="toolbar-btn w-full bg-green-500 text-white hover:bg-green-600" disabled>
                            {{ __('shop::app.products.customization.add-to-cart') }}
                        </button>
                        <a href="{{ route('shop.product_or_category.index') }}" class="block text-center py-2 text-gray-600 hover:text-gray-800">
                            {{ __('shop::app.products.customization.continue-shopping') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Product and print area data
        const productId = {{ $product->id }};
        const printAreas = @json($printAreas);
        const productImage = @json($productImage);
        
        // State
        let selectedArea = null;
        let uploadedImage = null;
        let canvas = null;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Display product image
            if (productImage && productImage.url) {
                document.getElementById('product-image').src = productImage.url;
            }
            
            // Display print areas
            displayPrintAreas();
            
            // Setup event listeners
            setupEventListeners();
        });
        
        function displayPrintAreas() {
            const container = document.getElementById('areas-list');
            container.innerHTML = '';
            
            printAreas.forEach((area, index) => {
                const div = document.createElement('div');
                div.className = 'p-3 border border-gray-200 rounded cursor-pointer hover:bg-gray-50';
                div.innerHTML = `
                    <div class="flex justify-between items-center">
                        <span>{{ __('shop::app.products.customization.area') }} ${index + 1}</span>
                        <span class="text-sm text-gray-500">${Math.round(area.width)} x ${Math.round(area.height)}px</span>
                    </div>
                `;
                div.onclick = () => selectArea(area);
                container.appendChild(div);
            });
            
            document.getElementById('preview-btn').disabled = printAreas.length === 0;
        }
        
        function selectArea(area) {
            selectedArea = area;
            
            // Highlight selected area on image
            const overlay = document.getElementById('print-areas-overlay');
            overlay.innerHTML = '';
            
            const highlight = document.createElement('div');
            highlight.className = 'absolute border-2 border-blue-500 bg-blue-100 bg-opacity-30 print-area-highlight';
            highlight.style.left = area.x + 'px';
            highlight.style.top = area.y + 'px';
            highlight.style.width = area.width + 'px';
            highlight.style.height = area.height + 'px';
            overlay.appendChild(highlight);
            
            document.getElementById('preview-btn').disabled = false;
        }
        
        function setupEventListeners() {
            // Image upload
            document.getElementById('custom-image-input').addEventListener('change', handleImageUpload);
            
            // Add text
            document.getElementById('add-text-btn').addEventListener('click', addText);
            
            // Preview
            document.getElementById('preview-btn').addEventListener('click', showPreview);
            
            // Add to cart
            document.getElementById('add-to-cart-btn').addEventListener('click', addToCart);
        }
        
        function handleImageUpload(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(event) {
                uploadedImage = event.target.result;
                document.getElementById('add-to-cart-btn').disabled = false;
                
                // Show preview section
                document.getElementById('preview-section').classList.remove('hidden');
                showPreview();
            };
            reader.readAsDataURL(file);
        }
        
        function addText() {
            const text = prompt('{{ __('shop::app.products.customization.enter-text') }}:');
            if (text) {
                uploadedImage = { type: 'text', content: text };
                document.getElementById('add-to-cart-btn').disabled = false;
                document.getElementById('preview-section').classList.remove('hidden');
                showPreview();
            }
        }
        
        function showPreview() {
            if (!selectedArea) {
                alert('{{ __('shop::app.products.customization.select-area-first') }}');
                return;
            }
            
            const canvas = document.getElementById('preview-canvas');
            const ctx = canvas.getContext('2d');
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw product image
            const img = new Image();
            img.onload = function() {
                // Draw image scaled to fit canvas
                const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
                const x = (canvas.width - img.width * scale) / 2;
                const y = (canvas.height - img.height * scale) / 2;
                ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
                
                // Calculate scaled area position
                const scaledArea = {
                    x: x + selectedArea.x * scale,
                    y: y + selectedArea.y * scale,
                    width: selectedArea.width * scale,
                    height: selectedArea.height * scale
                };
                
                // Draw print area border
                ctx.strokeStyle = '#3b82f6';
                ctx.lineWidth = 2;
                ctx.setLineDash([5, 5]);
                ctx.strokeRect(scaledArea.x, scaledArea.y, scaledArea.width, scaledArea.height);
                ctx.setLineDash([]);
                
                // Draw uploaded content in print area
                if (uploadedImage) {
                    if (typeof uploadedImage === 'string' && !uploadedImage.startsWith('{')) {
                        // It's an image
                        const uploadImg = new Image();
                        uploadImg.onload = function() {
                            // Draw image to fit in print area
                            ctx.drawImage(uploadImg, scaledArea.x, scaledArea.y, scaledArea.width, scaledArea.height);
                        };
                        uploadImg.src = uploadedImage;
                    } else if (uploadedImage.type === 'text') {
                        // It's text
                        ctx.fillStyle = '#000';
                        ctx.font = '24px Arial';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(
                            uploadedImage.content,
                            scaledArea.x + scaledArea.width / 2,
                            scaledArea.y + scaledArea.height / 2
                        );
                    }
                }
            };
            img.src = productImage.url;
        }
        
        async function addToCart() {
            if (!selectedArea) {
                alert('{{ __('shop::app.products.customization.select-area-first') }}');
                return;
            }
            
            // Prepare customization data
            const customizationData = {
                area_id: selectedArea.id,
                image_url: typeof uploadedImage === 'string' ? uploadedImage : null,
                text_content: uploadedImage?.type === 'text' ? uploadedImage.content : null,
            };
            
            try {
                const response = await fetch('/api/cart/add-customization', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1,
                        customization: customizationData,
                    }),
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('{{ __('shop::app.products.customization.added-to-cart') }}');
                    window.location.href = '{{ route('shop.checkout.cart.index') }}';
                } else {
                    alert(result.message || '{{ __('shop::app.products.customization.error') }}');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('{{ __('shop::app.products.customization.error') }}');
            }
        }
    </script>
</body>
</html>
