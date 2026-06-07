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



<script>
window.productId = {{ $productId }};
window.printAreas = @json($printAreas ?? []);
window.productImage = @json($productImage ?? null);
window.baseUrl = '{{ url('/') }};

// State
var state = {
    selectedElement: null,
    elements: [],
    zoom: 1,
    currentTab: 'product',
    uploadedImages: []
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initDesigner();
});

function initDesigner() {
    console.log('Designer initialized');
    console.log('Product ID:', window.productId);
    console.log('Print Areas:', window.printAreas);
    console.log('Product Image:', window.productImage);
    
    // Display product images if available
    if (window.productImage) {
        displayProductImage(window.productImage);
    }
}

function displayProductImage(image) {
    var canvasProductImage = document.getElementById('canvas-product-image');
    if (canvasProductImage && image.url) {
        canvasProductImage.src = window.baseUrl + image.url;
        canvasProductImage.onload = function() {
            // Update print area indicator based on first area
            if (window.printAreas && window.printAreas.length > 0) {
                updatePrintAreaIndicator(window.printAreas[0]);
            }
        };
    }
}

function updatePrintAreaIndicator(area) {
    var indicator = document.getElementById('print-area-indicator');
    if (indicator) {
        indicator.style.left = area.x + '%';
        indicator.style.top = area.y + '%';
        indicator.style.width = area.width + '%';
        indicator.style.height = area.height + '%';
    }
}

function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        if (btn.dataset.tab === tab) {
            btn.classList.add('border-blue-500', 'text-blue-500');
            btn.classList.remove('border-transparent', 'text-gray-500');
        } else {
            btn.classList.remove('border-blue-500', 'text-blue-500');
            btn.classList.add('border-transparent', 'text-gray-500');
        }
    });
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(function(content) {
        content.classList.add('hidden');
    });
    document.getElementById('tab-' + tab).classList.remove('hidden');
    
    state.currentTab = tab;
}

function handleImageUpload(input) {
    var file = input.files[0];
    if (!file) return;
    
    var reader = new FileReader();
    reader.onload = function(e) {
        var imageData = e.target.result;
        addUploadedImage(imageData);
    };
    reader.readAsDataURL(file);
}

function addUploadedImage(dataUrl) {
    state.uploadedImages.push(dataUrl);
    var container = document.getElementById('uploaded-images');
    var div = document.createElement('div');
    div.className = 'cursor-pointer border rounded p-1 hover:border-blue-500';
    div.innerHTML = '<img src="' + dataUrl + '" class="w-full aspect-square object-cover">';
    div.onclick = function() {
        addImageToCanvas(dataUrl);
    };
    container.appendChild(div);
}

function addImageToCanvas(dataUrl) {
    var img = document.createElement('img');
    img.src = dataUrl;
    img.className = 'absolute cursor-move custom-element';
    img.style.width = '100px';
    img.style.height = '100px';
    img.style.left = '50%';
    img.style.top = '50%';
    img.style.transform = 'translate(-50%, -50%)';
    img.style.zIndex = 10;
    img.onclick = function(e) {
        e.stopPropagation();
        selectElement(img);
    };
    
    makeDraggable(img);
    
    var elements = document.getElementById('custom-elements');
    elements.appendChild(img);
    
    state.elements.push({
        type: 'image',
        element: img,
        dataUrl: dataUrl
    });
    
    selectElement(img);
}

function addTextElement() {
    var content = document.getElementById('text-content').value || 'Text';
    var size = document.getElementById('text-size').value || 24;
    var color = document.getElementById('text-color').value || '#000000';
    var font = document.getElementById('text-font').value || 'Arial';
    
    var text = document.createElement('div');
    text.className = 'absolute cursor-move custom-element flex items-center justify-center';
    text.textContent = content;
    text.style.fontSize = size + 'px';
    text.style.color = color;
    text.style.fontFamily = font;
    text.style.left = '50%';
    text.style.top = '50%';
    text.style.transform = 'translate(-50%, -50%)';
    text.style.zIndex = 10;
    text.style.minWidth = '50px';
    text.style.minHeight = size + 'px';
    text.onclick = function(e) {
        e.stopPropagation();
        selectElement(text);
    };
    
    makeDraggable(text);
    
    var elements = document.getElementById('custom-elements');
    elements.appendChild(text);
    
    state.elements.push({
        type: 'text',
        element: text
    });
    
    selectElement(text);
}

function makeDraggable(element) {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    
    element.onmousedown = dragMouseDown;
    element.ontouchstart = dragTouchStart;
    
    function dragMouseDown(e) {
        if (e.target !== element) return;
        e.preventDefault();
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
    }
    
    function dragTouchStart(e) {
        if (e.target !== element) return;
        e.preventDefault();
        var touch = e.touches[0];
        pos3 = touch.clientX;
        pos4 = touch.clientY;
        document.ontouchend = closeDragElement;
        document.ontouchmove = elementDragTouch;
    }
    
    function elementDrag(e) {
        e.preventDefault();
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        element.style.top = (element.offsetTop - pos2) + 'px';
        element.style.left = (element.offsetLeft - pos1) + 'px';
    }
    
    function elementDragTouch(e) {
        e.preventDefault();
        var touch = e.touches[0];
        pos1 = pos3 - touch.clientX;
        pos2 = pos4 - touch.clientY;
        pos3 = touch.clientX;
        pos4 = touch.clientY;
        element.style.top = (element.offsetTop - pos2) + 'px';
        element.style.left = (element.offsetLeft - pos1) + 'px';
    }
    
    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
        document.ontouchend = null;
        document.ontouchmove = null;
    }
}

function selectElement(el) {
    // Deselect previous
    if (state.selectedElement) {
        state.selectedElement.style.outline = '';
    }
    
    state.selectedElement = el;
    el.style.outline = '2px solid blue';
    
    document.getElementById('element-actions').classList.remove('hidden');
    document.getElementById('no-selection').classList.add('hidden');
}

function deselectAll() {
    if (state.selectedElement) {
        state.selectedElement.style.outline = '';
        state.selectedElement = null;
    }
    document.getElementById('element-actions').classList.add('hidden');
    document.getElementById('no-selection').classList.remove('hidden');
}

document.getElementById('design-canvas').onclick = function(e) {
    if (e.target.id === 'design-canvas' || e.target.id === 'custom-elements') {
        deselectAll();
    }
};

function maximizeElement() {
    if (!state.selectedElement) return;
    state.selectedElement.style.width = '100%';
    state.selectedElement.style.height = '100%';
    state.selectedElement.style.left = '0';
    state.selectedElement.style.top = '0';
    state.selectedElement.style.transform = 'none';
}

function flipHorizontal() {
    if (!state.selectedElement) return;
    var transform = state.selectedElement.style.transform || '';
    if (transform.includes('scaleX(-1)')) {
        transform = transform.replace('scaleX(-1)', '');
    } else {
        transform += ' scaleX(-1)';
    }
    state.selectedElement.style.transform = transform.trim();
}

function flipVertical() {
    if (!state.selectedElement) return;
    var transform = state.selectedElement.style.transform || '';
    if (transform.includes('scaleY(-1)')) {
        transform = transform.replace('scaleY(-1)', '');
    } else {
        transform += ' scaleY(-1)';
    }
    state.selectedElement.style.transform = transform.trim();
}

function rotateLeft() {
    if (!state.selectedElement) return;
    var current = state.selectedElement.style.transform || '';
    var match = current.match(/rotate\(([-\d.]+)deg\)/);
    var angle = match ? parseFloat(match[1]) - 90 : -90;
    current = current.replace(/rotate\([-\d.]+deg\)/, '');
    state.selectedElement.style.transform = (current.trim() + ' rotate(' + angle + 'deg)').trim();
}

function rotateRight() {
    if (!state.selectedElement) return;
    var current = state.selectedElement.style.transform || '';
    var match = current.match(/rotate\(([-\d.]+)deg\)/);
    var angle = match ? parseFloat(match[1]) + 90 : 90;
    current = current.replace(/rotate\([-\d.]+deg\)/, '');
    state.selectedElement.style.transform = (current.trim() + ' rotate(' + angle + 'deg)').trim();
}

function bringToFront() {
    if (!state.selectedElement) return;
    var maxZ = 10;
    state.elements.forEach(function(item) {
        var z = parseInt(item.element.style.zIndex) || 10;
        if (z > maxZ) maxZ = z;
    });
    state.selectedElement.style.zIndex = maxZ + 1;
}

function sendToBack() {
    if (!state.selectedElement) return;
    var minZ = 1;
    state.elements.forEach(function(item) {
        var z = parseInt(item.element.style.zIndex) || 10;
        if (z < minZ) minZ = z;
    });
    state.selectedElement.style.zIndex = Math.max(1, minZ - 1);
}

function deleteElement() {
    if (!state.selectedElement) return;
    state.selectedElement.remove();
    state.elements = state.elements.filter(function(item) {
        return item.element !== state.selectedElement;
    });
    state.selectedElement = null;
    deselectAll();
}

function zoomIn() {
    state.zoom = Math.min(2, state.zoom + 0.1);
    document.getElementById('design-canvas').style.transform = 'scale(' + state.zoom + ')';
    document.getElementById('zoom-level').textContent = Math.round(state.zoom * 100) + '%';
}

function zoomOut() {
    state.zoom = Math.max(0.5, state.zoom - 0.1);
    document.getElementById('design-canvas').style.transform = 'scale(' + state.zoom + ')';
    document.getElementById('zoom-level').textContent = Math.round(state.zoom * 100) + '%';
}

function cancelDesign() {
    if (confirm('Are you sure you want to cancel?')) {
        history.back();
    }
}

function saveDesign() {
    alert('Design saved! (Integration with cart coming soon)');
}
</script>
