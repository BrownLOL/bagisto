@php
    $productId = $product->id;
@endphp

<div class="container mx-auto max-w-[1200px] px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">{{ __('Customization Designer') }}</h1>
    
    <div class="flex gap-4" style="height: 600px;">
        <!-- Left Panel - Selection -->
        <div class="w-64 bg-white border rounded-lg flex flex-col">
            <!-- Tabs -->
            <div class="flex border-b">
                <button 
                    onclick="switchTab('product')"
                    class="tab-btn flex-1 px-4 py-2 text-sm font-medium border-b-2 border-blue-500 text-blue-500"
                    data-tab="product">
                    {{ __('Product') }}
                </button>
                <button 
                    onclick="switchTab('image')"
                    class="tab-btn flex-1 px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                    data-tab="image">
                    {{ __('Image') }}
                </button>
                <button 
                    onclick="switchTab('text')"
                    class="tab-btn flex-1 px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                    data-tab="text">
                    {{ __('Text') }}
                </button>
            </div>
            
            <!-- Tab Content -->
            <div class="flex-1 overflow-auto p-4">
                <!-- Product Tab -->
                <div id="tab-product" class="tab-content">
                    <div id="product-images" class="grid grid-cols-2 gap-2">
                        <!-- Product images will be loaded here -->
                    </div>
                </div>
                
                <!-- Image Tab -->
                <div id="tab-image" class="tab-content hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">{{ __('Upload Image') }}</label>
                        <input 
                            type="file" 
                            id="custom-image-upload" 
                            accept="image/*"
                            class="w-full border rounded p-2"
                            onchange="handleImageUpload(this)">
                    </div>
                    <div id="uploaded-images" class="grid grid-cols-2 gap-2">
                        <!-- Uploaded images will appear here -->
                    </div>
                </div>
                
                <!-- Text Tab -->
                <div id="tab-text" class="tab-content hidden">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('Text Content') }}</label>
                            <input 
                                type="text" 
                                id="text-content" 
                                class="w-full border rounded p-2"
                                placeholder="{{ __('Enter text') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('Font Size') }}</label>
                            <input 
                                type="number" 
                                id="text-size" 
                                value="24" 
                                min="12" 
                                max="72"
                                class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('Text Color') }}</label>
                            <input 
                                type="color" 
                                id="text-color" 
                                value="#000000"
                                class="w-full h-10 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('Font Family') }}</label>
                            <select id="text-font" class="w-full border rounded p-2">
                                <option value="Arial">Arial</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Verdana">Verdana</option>
                            </select>
                        </div>
                        <button 
                            onclick="addTextElement()"
                            class="w-full bg-blue-500 text-white rounded py-2 px-4 hover:bg-blue-600">
                            {{ __('Add Text') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Center Panel - Design Canvas -->
        <div class="flex-1 bg-gray-100 border rounded-lg p-4 relative overflow-hidden">
            <div id="design-canvas" class="relative w-full h-full bg-white rounded overflow-hidden" style="aspect-ratio: 1;">
                <!-- Product image with print area overlay -->
                <img 
                    id="canvas-product-image" 
                    src="" 
                    class="absolute top-0 left-0 w-full h-full object-contain"
                    draggable="false">
                
                <!-- Print area indicator -->
                <div 
                    id="print-area-indicator"
                    class="absolute border-2 border-dashed border-blue-500 bg-blue-100 bg-opacity-30 pointer-events-none">
                </div>
                
                <!-- Custom elements will be added here -->
                <div id="custom-elements" class="absolute top-0 left-0 w-full h-full pointer-events-none">
                </div>
            </div>
            
            <!-- Zoom Controls -->
            <div class="absolute bottom-4 right-4 flex gap-2">
                <button onclick="zoomIn()" class="bg-white border rounded px-3 py-1 hover:bg-gray-50">+</button>
                <span id="zoom-level" class="bg-white border rounded px-3 py-1">100%</span>
                <button onclick="zoomOut()" class="bg-white border rounded px-3 py-1 hover:bg-gray-50">-</button>
            </div>
        </div>
        
        <!-- Right Panel - Properties -->
        <div class="w-64 bg-white border rounded-lg flex flex-col">
            <div class="p-4 border-b">
                <h3 class="font-medium">{{ __('Properties') }}</h3>
            </div>
            
            <div class="flex-1 overflow-auto p-4">
                <!-- Element Actions -->
                <div id="element-actions" class="hidden space-y-3">
                    <h4 class="font-medium text-sm text-gray-700">{{ __('Selected Element') }}</h4>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="maximizeElement()" class="border rounded py-2 px-3 text-sm hover:bg-gray-50">
                            {{ __('Maximize') }}
                        </button>
                        <button onclick="flipHorizontal()" class="border rounded py-2 px-3 text-sm hover:bg-gray-50">
                            {{ __('Flip H') }}
                        </button>
                        <button onclick="flipVertical()" class="border rounded py-2 px-3 text-sm hover:bg-gray-50">
                            {{ __('Flip V') }}
                        </button>
                        <button onclick="rotateLeft()" class="border rounded py-2 px-3 text-sm hover:bg-gray-50">
                            {{ __('Rotate L') }}
                        </button>
                        <button onclick="rotateRight()" class="border rounded py-2 px-3 text-sm hover:bg-gray-50">
                            {{ __('Rotate R') }}
                        </button>
                    </div>
                    
                    <div class="border-t pt-3">
                        <p class="text-sm font-medium mb-2">{{ __('Layer') }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="bringToFront()" class="border rounded py-2 px-3 text-sm hover:bg-gray-50">
                                {{ __('Front') }}
                            </button>
                            <button onclick="sendToBack()" class="border rounded py-2 px-3 text-sm hover:bg-gray-50">
                                {{ __('Back') }}
                            </button>
                        </div>
                    </div>
                    
                    <button onclick="deleteElement()" class="w-full bg-red-500 text-white rounded py-2 px-4 hover:bg-red-600">
                        {{ __('Delete') }}
                    </button>
                </div>
                
                <!-- No Selection Message -->
                <div id="no-selection" class="text-center text-gray-500 py-8">
                    <p class="text-sm">{{ __('Select an element to edit') }}</p>
                </div>
            </div>
            
            <!-- Preview -->
            <div class="border-t p-4">
                <h4 class="font-medium text-sm mb-2">{{ __('Preview') }}</h4>
                <div id="preview-container" class="bg-gray-100 rounded overflow-hidden" style="aspect-ratio: 1;">
                    <canvas id="preview-canvas" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="mt-6 flex justify-end gap-4">
        <button onclick="cancelDesign()" class="px-6 py-2 border rounded hover:bg-gray-50">
            {{ __('Cancel') }}
        </button>
        <button onclick="saveDesign()" class="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            {{ __('Save & Add to Cart') }}
        </button>
    </div>
</div>

<!-- Load JS -->
@vite(['src/Resources/assets/js/components/customization/designer.js'])

<script>
window.productId = {{ $productId }};
window.printAreas = @json($printAreas ?? []);
window.productImage = @json($productImage ?? null);
window.baseUrl = '{{ url('/') }}';

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initDesigner();
});
</script>
