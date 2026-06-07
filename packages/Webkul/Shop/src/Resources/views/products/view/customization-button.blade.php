<div id="customization-app" style="margin: 20px 0;">
    <button
        id="customize-btn"
        data-product-id="{{ $product->id }}"
        onclick="openCustomizationDialog()"
        style="display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;"
    >
        {{ __('Customize Now') }}
    </button>
</div>

<!-- Customization Dialog -->
<div id="customization-dialog" class="fixed inset-0 bg-black bg-opacity-50" style="display: none; z-index: 99999; overflow-y: auto;">
    <div class="bg-white rounded-lg shadow-xl" style="width: 90%; max-width: 1200px; height: 80vh; margin: 5vh auto; display: flex; flex-direction: column;">
        <div class="flex justify-between items-center p-4 border-b" style="background: #f9fafb;">
            <h2 class="text-xl font-bold">Product Customization</h2>
            <button id="close-dialog" onclick="document.getElementById('customization-dialog').style.display='none'; document.body.style.overflow='auto';" style="padding: 8px 16px; background: #e5e7eb; border: none; border-radius: 6px; cursor: pointer;">Close</button>
        </div>
        
        <div class="flex flex-1 overflow-hidden">
            <div class="w-64 border-r p-4 overflow-y-auto" style="background: #f9fafb;">
                <div class="flex border-b mb-4">
                    <button class="tab-btn px-4 py-2 border-b-2 border-blue-500 text-blue-500 font-medium" data-tab="product">Product</button>
                    <button class="tab-btn px-4 py-2 border-b-2 border-transparent text-gray-500" data-tab="image">Image</button>
                    <button class="tab-btn px-4 py-2 border-b-2 border-transparent text-gray-500" data-tab="text">Text</button>
                </div>
                
                <div id="tab-product" class="tab-content">
                    <h3 class="font-semibold mb-2">Select Product Image</h3>
                    <div id="product-images" class="grid grid-cols-2 gap-2"></div>
                    <p id="no-areas-msg" class="text-red-500 mt-2 hidden">This product has no customizable areas.</p>
                </div>
                
                <div id="tab-image" class="tab-content hidden">
                    <h3 class="font-semibold mb-2">Upload Your Image</h3>
                    <input type="file" id="image-upload" accept="image/*" class="mb-4 w-full">
                    <div id="uploaded-images" class="grid grid-cols-2 gap-2"></div>
                </div>
                
                <div id="tab-text" class="tab-content hidden">
                    <h3 class="font-semibold mb-2">Add Text</h3>
                    <input type="text" id="text-content" placeholder="Enter text" class="w-full p-2 border rounded mb-2">
                    <label class="block text-sm mb-1">Size: <span id="size-value">24</span>px</label>
                    <input type="range" id="text-size" min="12" max="72" value="24" class="w-full mb-2">
                    <label class="block text-sm mb-1">Color</label>
                    <input type="color" id="text-color" value="#000000" class="w-full h-10 mb-2">
                    <label class="block text-sm mb-1">Font</label>
                    <select id="text-font" class="w-full p-2 border rounded mb-4">
                        <option value="Arial">Arial</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Verdana">Verdana</option>
                    </select>
                    <button id="add-text-btn" style="width: 100%; padding: 10px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">Add Text</button>
                </div>
            </div>
            
            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="flex-1 overflow-auto p-4 flex items-center justify-center bg-gray-100">
                    <div id="design-canvas" class="relative bg-white shadow-lg" style="width: 400px; height: 400px; overflow: hidden;">
                        <img id="canvas-product-image" class="w-full h-full object-contain" src="" alt="Product">
                        <div id="print-area-indicator" class="absolute border-2 border-dashed border-red-500 bg-red-100 bg-opacity-30 pointer-events-none"></div>
                        <div id="custom-elements" class="absolute inset-0"></div>
                    </div>
                </div>
                <div class="flex justify-center items-center gap-4 p-2 border-t bg-white">
                    <button id="zoom-out" class="px-3 py-1 bg-gray-200 rounded">-</button>
                    <span id="zoom-level">100%</span>
                    <button id="zoom-in" class="px-3 py-1 bg-gray-200 rounded">+</button>
                </div>
            </div>
            
            <div class="w-64 border-l p-4 overflow-y-auto" style="background: #f9fafb;">
                <h3 class="font-semibold mb-4">Properties</h3>
                <div id="no-selection" class="text-gray-500 text-sm">Select an element to edit</div>
                <div id="element-actions" class="hidden">
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <button id="maximize-btn" class="px-3 py-2 bg-blue-100 text-blue-700 rounded text-sm">Maximize</button>
                        <button id="flip-h-btn" class="px-3 py-2 bg-blue-100 text-blue-700 rounded text-sm">Flip H</button>
                        <button id="flip-v-btn" class="px-3 py-2 bg-blue-100 text-blue-700 rounded text-sm">Flip V</button>
                        <button id="rotate-l-btn" class="px-3 py-2 bg-blue-100 text-blue-700 rounded text-sm">Rotate L</button>
                        <button id="rotate-r-btn" class="px-3 py-2 bg-blue-100 text-blue-700 rounded text-sm">Rotate R</button>
                    </div>
                    <div class="border-t pt-4">
                        <p class="text-sm font-medium mb-2">Layer</p>
                        <div class="flex gap-2">
                            <button id="bring-front-btn" class="flex-1 px-3 py-2 bg-green-100 text-green-700 rounded text-sm">Front</button>
                            <button id="send-back-btn" class="flex-1 px-3 py-2 bg-green-100 text-green-700 rounded text-sm">Back</button>
                        </div>
                    </div>
                    <div class="border-t pt-4 mt-4">
                        <button id="delete-btn" class="w-full px-3 py-2 bg-red-100 text-red-700 rounded text-sm">Delete</button>
                    </div>
                </div>
                <div class="border-t pt-4 mt-4">
                    <p class="text-sm font-medium mb-2">Preview</p>
                    <div class="bg-white p-2 rounded border">
                        <canvas id="preview-canvas" class="w-full" style="max-height: 150px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end gap-4 p-4 border-t bg-white">
            <button id="cancel-btn" style="padding: 10px 24px; background: #e5e7eb; border: none; border-radius: 6px; cursor: pointer;">Cancel</button>
            <button id="save-btn" style="padding: 10px 24px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">Save & Add to Cart</button>
        </div>
    </div>
</div>

<script>
(function() {
    console.log('Customization script starting...');
    var productId = {{ $product->id }};
    console.log('Product ID:', productId);
    var printAreas = [];
    var productImage = null;
    var selectedElement = null;
    var elements = [];
    var uploadedImages = [];
    var zoom = 1;
    
    var dialog = document.getElementById('customization-dialog');
    var customizeBtn = document.getElementById('customize-btn');
    var closeBtn = document.getElementById('close-dialog');
    var cancelBtn = document.getElementById('cancel-btn');
    
    console.log('Dialog:', dialog);
    console.log('Button:', customizeBtn);
    
    if (customizeBtn) {
        customizeBtn.addEventListener('click', function() {
            dialog.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            loadPrintAreas();
        });
    } else {
        console.error('Customize button not found!');
    }
    
    closeBtn.onclick = cancelBtn.onclick = function() {
        dialog.style.display = 'none';
        document.body.style.overflow = 'auto';
    };
    
    function loadPrintAreas() {
        console.log('loadPrintAreas called, productId:', productId);
        fetch('/customization/print-areas/' + productId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                console.log('API response:', data);
                if (data.success && data.data.length > 0) {
                    printAreas = data.data;
                    
                    // Display product images list
                    displayProductImagesList(data.data);
                    
                    // Display first image
                    productImage = data.data[0].image_url;
                    displayProductImage();
                } else {
                    document.getElementById('no-areas-msg').classList.remove('hidden');
                }
            })
            .catch(function(err) {
                console.error('API error:', err);
            });
    }
    
    function displayProductImagesList(areas) {
        var container = document.getElementById('product-images');
        container.innerHTML = '';
        
        // Group by image URL to show unique images
        var imagesMap = {};
        areas.forEach(function(area) {
            if (area.image_url && !imagesMap[area.image_url]) {
                imagesMap[area.image_url] = area;
            }
        });
        
        Object.keys(imagesMap).forEach(function(url) {
            var area = imagesMap[url];
            var div = document.createElement('div');
            div.className = 'cursor-pointer border-2 border-transparent hover:border-blue-500 rounded overflow-hidden';
            div.innerHTML = '<img src="' + area.image_url + '" class="w-full h-16 object-cover">';
            div.onclick = (function(imgUrl) {
                return function() {
                    productImage = imgUrl;
                    displayProductImage();
                    // Update selection visual
                    container.querySelectorAll('div').forEach(function(d) {
                        d.classList.remove('border-blue-500');
                    });
                    this.classList.add('border-blue-500');
                };
            })(area.image_url);
            container.appendChild(div);
        });
    }
    
    function displayProductImage() {
        if (!productImage) return;
        var img = document.getElementById('canvas-product-image');
        img.src = productImage;
        img.onload = function() {
            if (printAreas.length > 0) {
                updatePrintAreaIndicator(printAreas[0]);
            }
            updatePreview();
        };
    }
    
    function updatePrintAreaIndicator(area) {
        var indicator = document.getElementById('print-area-indicator');
        indicator.style.left = area.x + '%';
        indicator.style.top = area.y + '%';
        indicator.style.width = area.width + '%';
        indicator.style.height = area.height + '%';
    }
    
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.onclick = function() {
            var tab = this.dataset.tab;
            document.querySelectorAll('.tab-btn').forEach(function(b) {
                b.classList.remove('border-blue-500', 'text-blue-500');
                b.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.add('border-blue-500', 'text-blue-500');
            this.classList.remove('border-transparent', 'text-gray-500');
            document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.add('hidden'); });
            document.getElementById('tab-' + tab).classList.remove('hidden');
        };
    });
    
    document.getElementById('image-upload').onchange = function(e) {
        var file = e.target.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(ev) { addUploadedImage(ev.target.result); };
        reader.readAsDataURL(file);
    };
    
    function addUploadedImage(dataUrl) {
        uploadedImages.push(dataUrl);
        var container = document.getElementById('uploaded-images');
        var div = document.createElement('div');
        div.className = 'cursor-pointer border rounded p-1 hover:border-blue-500';
        div.innerHTML = '<img src="' + dataUrl + '" class="w-full aspect-square object-cover">';
        div.onclick = function() { addImageToCanvas(dataUrl); };
        container.appendChild(div);
    }
    
    document.getElementById('text-size').oninput = function() {
        document.getElementById('size-value').textContent = this.value;
    };
    document.getElementById('add-text-btn').onclick = function() {
        var content = document.getElementById('text-content').value || 'Text';
        var size = document.getElementById('text-size').value;
        var color = document.getElementById('text-color').value;
        var font = document.getElementById('text-font').value;
        addTextToCanvas(content, size, color, font);
    };
    
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
        img.onclick = function(e) { e.stopPropagation(); selectElement(img); };
        makeDraggable(img);
        document.getElementById('custom-elements').appendChild(img);
        elements.push({ type: 'image', el: img, dataUrl: dataUrl });
        selectElement(img);
    }
    
    function addTextToCanvas(content, size, color, font) {
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
        text.onclick = function(e) { e.stopPropagation(); selectElement(text); };
        makeDraggable(text);
        document.getElementById('custom-elements').appendChild(text);
        elements.push({ type: 'text', el: text });
        selectElement(text);
    }
    
    function makeDraggable(el) {
        var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        el.onmousedown = function(e) {
            if (e.target !== el) return;
            e.preventDefault();
            pos3 = e.clientX;
            pos4 = e.clientY;
            document.onmouseup = function() {
                document.onmouseup = null;
                document.onmousemove = null;
                updatePreview();
            };
            document.onmousemove = function(e) {
                e.preventDefault();
                pos1 = pos3 - e.clientX;
                pos2 = pos4 - e.clientY;
                pos3 = e.clientX;
                pos4 = e.clientY;
                el.style.top = (el.offsetTop - pos2) + 'px';
                el.style.left = (el.offsetLeft - pos1) + 'px';
            };
        };
    }
    
    function selectElement(el) {
        if (selectedElement) selectedElement.style.outline = '';
        selectedElement = el;
        el.style.outline = '2px solid blue';
        document.getElementById('element-actions').classList.remove('hidden');
        document.getElementById('no-selection').classList.add('hidden');
    }
    
    function deselectAll() {
        if (selectedElement) selectedElement.style.outline = '';
        selectedElement = null;
        document.getElementById('element-actions').classList.add('hidden');
        document.getElementById('no-selection').classList.remove('hidden');
    }
    
    document.getElementById('design-canvas').onclick = function(e) {
        if (e.target.id === 'design-canvas' || e.target.id === 'custom-elements') {
            deselectAll();
        }
    };
    
    document.getElementById('maximize-btn').onclick = function() {
        if (!selectedElement) return;
        selectedElement.style.width = '100%';
        selectedElement.style.height = '100%';
        selectedElement.style.left = '0';
        selectedElement.style.top = '0';
        selectedElement.style.transform = 'none';
        updatePreview();
    };
    
    document.getElementById('flip-h-btn').onclick = function() {
        if (!selectedElement) return;
        var t = selectedElement.style.transform || '';
        selectedElement.style.transform = t.includes('scaleX(-1)') ? t.replace('scaleX(-1)', '') : t + ' scaleX(-1)';
        updatePreview();
    };
    
    document.getElementById('flip-v-btn').onclick = function() {
        if (!selectedElement) return;
        var t = selectedElement.style.transform || '';
        selectedElement.style.transform = t.includes('scaleY(-1)') ? t.replace('scaleY(-1)', '') : t + ' scaleY(-1)';
        updatePreview();
    };
    
    document.getElementById('rotate-l-btn').onclick = function() {
        if (!selectedElement) return;
        var t = selectedElement.style.transform || '';
        var m = t.match(/rotate\(([-\d.]+)deg\)/);
        var a = m ? parseFloat(m[1]) - 90 : -90;
        selectedElement.style.transform = t.replace(/rotate\([-\d.]+deg\)/, '') + ' rotate(' + a + 'deg)';
        updatePreview();
    };
    
    document.getElementById('rotate-r-btn').onclick = function() {
        if (!selectedElement) return;
        var t = selectedElement.style.transform || '';
        var m = t.match(/rotate\(([-\d.]+)deg\)/);
        var a = m ? parseFloat(m[1]) + 90 : 90;
        selectedElement.style.transform = t.replace(/rotate\([-\d.]+deg\)/, '') + ' rotate(' + a + 'deg)';
        updatePreview();
    };
    
    document.getElementById('bring-front-btn').onclick = function() {
        if (!selectedElement) return;
        var maxZ = 10;
        elements.forEach(function(item) { var z = parseInt(item.el.style.zIndex) || 10; if (z > maxZ) maxZ = z; });
        selectedElement.style.zIndex = maxZ + 1;
    };
    
    document.getElementById('send-back-btn').onclick = function() {
        if (!selectedElement) return;
        selectedElement.style.zIndex = Math.max(1, parseInt(selectedElement.style.zIndex) - 1);
    };
    
    document.getElementById('delete-btn').onclick = function() {
        if (!selectedElement) return;
        selectedElement.remove();
        elements = elements.filter(function(item) { return item.el !== selectedElement; });
        selectedElement = null;
        deselectAll();
        updatePreview();
    };
    
    document.getElementById('zoom-in').onclick = function() {
        zoom = Math.min(2, zoom + 0.1);
        document.getElementById('design-canvas').style.transform = 'scale(' + zoom + ')';
        document.getElementById('zoom-level').textContent = Math.round(zoom * 100) + '%';
    };
    
    document.getElementById('zoom-out').onclick = function() {
        zoom = Math.max(0.5, zoom - 0.1);
        document.getElementById('design-canvas').style.transform = 'scale(' + zoom + ')';
        document.getElementById('zoom-level').textContent = Math.round(zoom * 100) + '%';
    };
    
    function updatePreview() {
        var canvas = document.getElementById('preview-canvas');
        var ctx = canvas.getContext('2d');
        canvas.width = 200;
        canvas.height = 200;
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        var img = document.getElementById('canvas-product-image');
        if (img.src) {
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        }
    }
    
    document.getElementById('save-btn').onclick = function() {
        alert('Customization saved! (Integration with cart coming soon)');
        dialog.style.display = 'none';
    };
    
    // Expose functions to global scope
    window.loadPrintAreas = loadPrintAreas;
    window.openCustomizationDialog = function() {
        dialog.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        loadPrintAreas();
    };
})();
</script>
