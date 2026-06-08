@php
    $productId = $product->id ?? 0;
@endphp

<button
    onclick="openCustomizationDialog()"
    class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors mt-4"
>
    {{ __('customization::shop.customization.customize_now') }}
</button>

<script>
function openCustomizationDialog() {
    document.getElementById('customization-dialog').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    loadPrintAreas();
}

function closeDialog() {
    document.getElementById('customization-dialog').classList.add('hidden');
    document.body.style.overflow = '';
}

function loadPrintAreas() {
    var productId = {{ $productId }};
    console.log('loadPrintAreas called, productId:', productId);
    
    fetch('/customization/print-areas/' + productId)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            console.log('API response:', data);
            if (data.success && data.data.length > 0) {
                var productImagesDiv = document.getElementById('product-images');
                console.log('productImagesDiv:', productImagesDiv);
                productImagesDiv.innerHTML = '';
                console.log('Displaying', data.data.length, 'images');
                
                data.data.forEach(function(item) {
                    console.log('Item:', item);
                    var imgUrl = item.image_url || item.url || '';
                    console.log('Image URL:', imgUrl);
                    
                    var div = document.createElement('div');
                    div.className = 'cursor-pointer border-2 border-gray-300 rounded p-1 hover:border-blue-500';
                    div.innerHTML = '<img src="' + imgUrl + '" class="w-[120px] h-[120px] object-contain" />';
                    div.onclick = function() { selectProductImage(imgUrl, item); };
                    productImagesDiv.appendChild(div);
                });
                
                selectProductImage(data.data[0].image_url || data.data[0].url, data.data[0]);
            }
        })
        .catch(function(error) {
            console.error('API error:', error);
        });
}

function selectProductImage(imgUrl, areaData) {
    var canvas = document.getElementById('design-canvas');
    canvas.innerHTML = '<img src="' + imgUrl + '" class="w-full h-full object-contain" />';
    
    if (areaData && areaData.x !== undefined) {
        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:absolute;left:' + areaData.x + '%;top:' + areaData.y + '%;width:' + areaData.width + '%;height:' + areaData.height + '%;border:2px dashed red;pointer-events:none;';
        canvas.style.position = 'relative';
        canvas.appendChild(overlay);
    }
}

function switchTab(tab) {
    console.log('Switching to tab:', tab);
    document.querySelectorAll('.tab-btn').forEach(function(b) {
        b.classList.remove('border-blue-500', 'text-blue-500');
        b.classList.add('border-transparent', 'text-gray-500');
    });
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.add('border-blue-500', 'text-blue-500');
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.remove('border-transparent', 'text-gray-500');
    document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.add('hidden'); });
    document.getElementById('tab-' + tab).classList.remove('hidden');
}

function addUploadedImage(dataUrl) {
    var canvas = document.getElementById('design-canvas');
    var img = document.createElement('img');
    img.src = dataUrl;
    img.className = 'w-full h-full object-contain';
    canvas.innerHTML = '';
    canvas.appendChild(img);
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Customization dialog script loaded');
    
    document.getElementById('close-dialog').onclick = function() { closeDialog(); };
    document.getElementById('cancel-btn').onclick = function() { closeDialog(); };
    document.getElementById('customization-dialog').onclick = function(e) {
        if (e.target === this) closeDialog();
    };
    
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tab = this.dataset.tab;
            switchTab(tab);
        });
    });
});
</script>

<div id="customization-dialog" class="fixed inset-0 bg-black bg-opacity-50 z-[99999] hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-6xl mx-auto mt-20 p-6 max-h-[90vh] overflow-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">{{ __('customization::shop.customization.title') }}</h2>
            <button id="close-dialog" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        
        <div class="flex gap-4">
            <div class="w-1/4 border-r pr-4">
                <div class="flex gap-2 mb-4">
                    <button class="tab-btn border-b-2 border-blue-500 text-blue-500 pb-2 px-2" data-tab="product">Product</button>
                    <button class="tab-btn border-b-2 border-transparent text-gray-500 pb-2 px-2" data-tab="image">Image</button>
                    <button class="tab-btn border-b-2 border-transparent text-gray-500 pb-2 px-2" data-tab="text">Text</button>
                </div>
                
                <div id="tab-product" class="tab-content">
                    <h3 class="font-semibold mb-2">Select Product Image</h3>
                    <div id="product-images" class="grid grid-cols-2 gap-2"></div>
                </div>
                
                <div id="tab-image" class="tab-content hidden">
                    <h3 class="font-semibold mb-2">Upload Image</h3>
                    <input type="file" id="image-upload" accept="image/*" class="w-full border rounded p-2" />
                </div>
                
                <div id="tab-text" class="tab-content hidden">
                    <h3 class="font-semibold mb-2">Add Text</h3>
                    <input type="text" id="text-input" placeholder="Enter text" class="w-full border rounded p-2 mb-2" />
                    <select id="text-color" class="w-full border rounded p-2 mb-2">
                        <option value="#000000">Black</option>
                        <option value="#FF0000">Red</option>
                        <option value="#0000FF">Blue</option>
                        <option value="#00FF00">Green</option>
                    </select>
                    <input type="number" id="font-size" value="24" min="12" max="72" class="w-full border rounded p-2 mb-2" />
                    <button onclick="addText()" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Add Text</button>
                </div>
            </div>
            
            <div class="w-1/2">
                <h3 class="font-semibold mb-2">Design Area</h3>
                <div id="design-canvas" class="border-2 border-dashed border-gray-300 rounded-lg w-full aspect-square bg-gray-50 flex items-center justify-center overflow-hidden"></div>
            </div>
            
            <div class="w-1/4">
                <h3 class="font-semibold mb-2">Settings</h3>
                <div class="space-y-2">
                    <button onclick="maximizeElement()" class="w-full bg-gray-200 py-2 rounded hover:bg-gray-300">Maximize</button>
                    <button onclick="flipHorizontal()" class="w-full bg-gray-200 py-2 rounded hover:bg-gray-300">Flip H</button>
                    <button onclick="flipVertical()" class="w-full bg-gray-200 py-2 rounded hover:bg-gray-300">Flip V</button>
                    <button onclick="rotateLeft()" class="w-full bg-gray-200 py-2 rounded hover:bg-gray-300">Rotate Left</button>
                    <button onclick="rotateRight()" class="w-full bg-gray-200 py-2 rounded hover:bg-gray-300">Rotate Right</button>
                    <button onclick="bringForward()" class="w-full bg-gray-200 py-2 rounded hover:bg-gray-300">Layer Up</button>
                    <button onclick="sendBackward()" class="w-full bg-gray-200 py-2 rounded hover:bg-gray-300">Layer Down</button>
                    <button onclick="deleteElement()" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-600">Delete</button>
                </div>
                
                <h3 class="font-semibold mt-4 mb-2">Preview</h3>
                <div id="preview" class="border rounded p-2 bg-gray-50">
                    <div id="preview-content" class="w-full aspect-square bg-white"></div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end gap-2 mt-4 pt-4 border-t">
            <button id="cancel-btn" class="px-4 py-2 border rounded hover:bg-gray-100">Cancel</button>
            <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
        </div>
    </div>
</div>
