<div id="customization-product-id" data-id="{{ $product->id ?? 0 }}" class="hidden"></div>

<button
    type="button"
    onclick="event.preventDefault(); openCustomizationDialog();"
    class="secondary-button w-full mt-4"
>
    Customize Now
</button>

@pushOnce('scripts')
<script>
(function() {
    var el = document.getElementById('customization-product-id');
    if (el) {
        window.customizationProductId = parseInt(el.dataset.id) || 0;
    } else {
        window.customizationProductId = 0;
    }
})();

var currentCanvas = {
    elements: [],
    selectedElement: null,
    currentImageKey: null,
    layerStore: {}
};

function openCustomizationDialog() {
    document.getElementById('customization-dialog').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    currentCanvas.elements = [];
    currentCanvas.selectedElement = null;
    updateOperationButtons();
    loadPrintAreas();
    initTextControls();
    resetZoom();
    initCanvasPan();
}

function closeDialog() {
    document.getElementById('customization-dialog').classList.add('hidden');
    document.body.style.overflow = '';
    currentCanvas.selectedElement = null;
}

function saveCustomization() {
    var areaData = window.currentAreaData;
    if (!areaData) {
        alert('Please select a product image first.');
        return;
    }
    
    var printAreaId = areaData.id || areaData.print_area_id;
    
    if (!printAreaId) {
        alert('This image has no customizable area. Please select an image with print area.');
        return;
    }
    
    // Get elements for this print area
    var areaElemId = 'print-area-' + printAreaId;
    var printArea = document.getElementById(areaElemId);
    if (!printArea) {
        alert('Print area not found.');
        return;
    }
    
    var elements = [];
    var elemDivs = printArea.querySelectorAll('.canvas-elem');
    elemDivs.forEach(function(div) {
        var elemData = div._elemData;
        if (elemData) {
            var elem = {
                type: elemData.type,
                content: elemData.content,
                x: parseFloat(div.style.left) || 0,
                y: parseFloat(div.style.top) || 0,
                width: parseFloat(div.style.width) || 0,
                height: parseFloat(div.style.height) || 0,
                rotation: elemData.rotation || 0,
                scaleX: elemData.scaleX || 1,
                scaleY: elemData.scaleY || 1,
                styles: elemData.styles || {}
            };
            elements.push(elem);
        }
    });
    
    // Get product image from canvas
    var canvas = document.getElementById('design-canvas-inner');
    var previewImage = '';
    if (canvas) {
        var imgs = canvas.querySelectorAll('img');
        if (imgs.length > 0) {
            previewImage = imgs[0].src; // First img is the product bg
        }
    }
    
    // Prepare customization data
    var customizationData = {
        product_id: window.customizationProductId,
        quantity: 1,
        customization: {
            print_area_id: parseInt(printAreaId),
            preview_image: previewImage,
            elements: elements
        }
    };
    
    // Send to API
    fetch('/api/checkout/cart/add-customization', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(customizationData)
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.message) {
            alert(data.message);
        }
        if (data.data) {
            closeDialog();
            // Optionally redirect to cart
            if (confirm('Product added to cart! Go to cart?')) {
                window.location.href = '/checkout/cart';
            }
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        alert('Failed to add to cart. Please try again.');
    });
}

// Add event listener for save button
document.addEventListener('DOMContentLoaded', function() {
    var saveBtn = document.getElementById('save-customization-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', saveCustomization);
    }
});

var canvasZoom = 100;
var panX = 0, panY = 0;
var isPanning = false;
var panStartX, panStartY;

function zoomIn() {
    if (canvasZoom < 200) {
        canvasZoom += 25;
        updateCanvasZoom();
    }
}

function zoomOut() {
    if (canvasZoom > 50) {
        canvasZoom -= 25;
        updateCanvasZoom();
    }
}

function updateCanvasZoom() {
    var inner = document.getElementById('design-canvas-inner');
    var zoomLabel = document.getElementById('zoom-level');
    if (inner && zoomLabel) {
        inner.style.transform = 'translate(' + panX + 'px, ' + panY + 'px) scale(' + (canvasZoom / 100) + ')';
        zoomLabel.textContent = canvasZoom + '%';
    }
    updatePreview();
}

function resetZoom() {
    canvasZoom = 100;
    panX = 0;
    panY = 0;
    updateCanvasZoom();
}

function initCanvasPan() {
    var wrapper = document.getElementById('design-canvas-wrapper');
    if (!wrapper) return;
    
    wrapper.onmousedown = function(e) {
        if (e.target === wrapper || e.target.id === 'design-canvas-inner' || e.target.classList.contains('bg-gray-50')) {
            if (canvasZoom !== 100) {
                isPanning = true;
                panStartX = e.clientX - panX;
                panStartY = e.clientY - panY;
                wrapper.style.cursor = 'grabbing';
                e.preventDefault();
            }
        }
    };
    
    document.onmousemove = function(e) {
        if (isPanning) {
            panX = e.clientX - panStartX;
            panY = e.clientY - panStartY;
            updateCanvasZoom();
        }
    };
    
    document.onmouseup = function() {
        if (isPanning) {
            isPanning = false;
            var wrapper = document.getElementById('design-canvas-wrapper');
            if (wrapper) wrapper.style.cursor = '';
        }
    };
}

function updatePreview() {
    var previewCanvas = document.getElementById('preview-canvas');
    var inner = document.getElementById('design-canvas-inner');
    if (!previewCanvas || !inner) return;
    
    previewCanvas.innerHTML = '';
    
    // Clone the inner canvas content with fixed 250x250 dimensions
    var clone = inner.cloneNode(true);
    clone.style.cssText = 'position: relative; width: 250px; height: 250px; flex-shrink: 0;';
    clone.querySelectorAll('.canvas-elem').forEach(function(elem) {
        elem.style.pointerEvents = 'none';
    });
    previewCanvas.appendChild(clone);
}

function loadPrintAreas() {
    var productId = window.customizationProductId;
    
    fetch('/customization/print-areas/' + productId)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.data.length > 0) {
                var productImagesDiv = document.getElementById('product-images');
                productImagesDiv.innerHTML = '';
                
                data.data.forEach(function(item) {
                    var imgUrl = item.image_url || item.url || '';
                    var div = document.createElement('div');
                    div.className = 'cursor-pointer border-2 border-gray-300 rounded p-1 hover:border-blue-500';
                    div.style.cssText = 'width: 100%; aspect-ratio: 1; object-fit: contain;';
                    div.innerHTML = '<img src="' + imgUrl + '" class="w-full h-full object-contain" />';
                    div.onclick = function() { selectProductImage(imgUrl, item); };
                    productImagesDiv.appendChild(div);
                });
                
                selectProductImage(data.data[0].image_url || data.data[0].url, data.data[0]);
            }
        });
}

function selectProductImage(imgUrl, areaData) {
    // Save current area data
    window.currentAreaData = areaData;
    
    var canvas = document.getElementById('design-canvas-inner');
    
    if (currentCanvas.currentImageKey && currentCanvas.elements.length > 0) {
        currentCanvas.layerStore[currentCanvas.currentImageKey] = currentCanvas.elements.slice();
    }
    
    currentCanvas.currentImageKey = imgUrl;
    
    if (currentCanvas.layerStore[currentCanvas.currentImageKey]) {
        currentCanvas.elements = currentCanvas.layerStore[currentCanvas.currentImageKey];
    } else {
        currentCanvas.elements = [];
    }
    currentCanvas.selectedElement = null;
    
    document.querySelectorAll('.canvas-elem').forEach(function(e) { e.remove(); });
    document.querySelectorAll('.elem-control').forEach(function(c) { c.remove(); });
    
    canvas.innerHTML = '';
    canvas.style.position = 'relative';
    
    var bgImg = document.createElement('img');
    bgImg.src = imgUrl;
    bgImg.style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;object-fit:contain;pointer-events:none;';
    canvas.appendChild(bgImg);
    
    if (areaData && areaData.x !== undefined) {
        var printAreaId = 'print-area-' + (areaData.id || areaData.print_area_id || 'default');
        var printArea = document.createElement('div');
        printArea.id = printAreaId;
        printArea.style.cssText = 'position:absolute;left:' + areaData.x + '%;top:' + areaData.y + '%;width:' + areaData.width + '%;height:' + areaData.height + '%;border:2px dashed red;background:rgba(255,255,255,0.3);overflow:hidden;';
        canvas.appendChild(printArea);
        
        currentCanvas.elements.forEach(function(elemData) {
            var wrapper = document.createElement('div');
            wrapper.className = 'canvas-elem';
            wrapper.style.cssText = 'position:absolute;left:' + elemData.x + '%;top:' + elemData.y + '%;width:' + elemData.w + '%;height:' + elemData.h + '%;cursor:move;transform:rotate(' + elemData.rotation + 'deg);transform-origin:center center;';
            
            if (elemData.type === 'image') {
                var img = document.createElement('img');
                img.src = elemData.content;
                img.style.cssText = 'width:100%;height:100%;object-fit:fill;pointer-events:none;';
                wrapper.appendChild(img);
            } else if (elemData.type === 'text') {
                wrapper.textContent = elemData.content;
                wrapper.style.fontSize = '24px';
                wrapper.style.color = elemData.styles.color || '#000';
                wrapper.style.display = 'flex';
                wrapper.style.alignItems = 'center';
                wrapper.style.justifyContent = 'center';
            }
            
            setupElemEvents(wrapper, elemData);
            printArea.appendChild(wrapper);
        });
    }
    
    updateLayersList();
    updateOperationButtons();
    updatePreview();
}

function addUploadedImage(dataUrl) {
    var areaData = window.currentAreaData;
    var areaElemId = 'print-area-' + (areaData && (areaData.id || areaData.print_area_id) ? (areaData.id || areaData.print_area_id) : 'default');
    var printArea = document.getElementById(areaElemId);
    if (!printArea) return;
    
    var wrapper = document.createElement('div');
    wrapper.className = 'canvas-elem';
    wrapper.style.cssText = 'position:absolute;left:10%;top:10%;width:80%;height:80%;cursor:move;transform-origin:center center;';
    
    var img = document.createElement('img');
    img.src = dataUrl;
    img.style.cssText = 'width:100%;height:100%;object-fit:fill;pointer-events:none;';
    wrapper.appendChild(img);
    
    var elemData = {
        dom: wrapper,
        type: 'image',
        content: dataUrl,
        x: 10, y: 10, w: 80, h: 80, rotation: 0,
        styles: {},
        id: Date.now()
    };
    
    setupElemEvents(wrapper, elemData);
    printArea.appendChild(wrapper);
    currentCanvas.elements.push(elemData);
    selectElem(elemData);
    updateLayersList();
    updatePreview();
}

function updateLayersList() {
    var layersDiv = document.getElementById('layers-list');
    layersDiv.innerHTML = '';
    
    var reversed = currentCanvas.elements.slice().reverse();
    reversed.forEach(function(elemData, index) {
        var item = document.createElement('div');
        item.className = 'flex items-center gap-2 p-2 border rounded cursor-pointer hover:bg-gray-100 ' + (currentCanvas.selectedElement === elemData ? 'bg-blue-50 border-blue-500' : 'border-gray-300');
        item.style.cssText = 'width: 100%; height: 50px;';
        
        var icon = document.createElement('span');
        icon.style.cssText = 'width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; background: #e5e7eb; border-radius: 4px; overflow: hidden;';
        if (elemData.type === 'image') {
            var thumb = document.createElement('img');
            thumb.src = elemData.content;
            thumb.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
            icon.appendChild(thumb);
        } else if (elemData.type === 'text') {
            icon.innerHTML = '<span class="text-lg font-bold text-gray-700">T</span>';
        } else {
            icon.innerHTML = '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
        }
        item.appendChild(icon);
        
        var name = document.createElement('span');
        name.className = 'flex-1 text-sm truncate';
        name.textContent = elemData.type === 'image' ? 'Image ' + (currentCanvas.elements.length - index) : elemData.content;
        item.appendChild(name);
        
        item.onclick = function() {
            selectElem(elemData);
            updateLayersList();
        };
        
        layersDiv.appendChild(item);
    });
}

function selectElem(elemData) {
    currentCanvas.selectedElement = elemData;
    document.querySelectorAll('.elem-control').forEach(function(c) { c.remove(); });
    
    var wrapper = elemData.dom;
    var control = document.createElement('div');
    control.className = 'elem-control';
    control.style.cssText = 'position:absolute;left:' + elemData.x + '%;top:' + elemData.y + '%;width:' + elemData.w + '%;height:' + elemData.h + '%;border:2px solid #3b82f6;transform:rotate(' + elemData.rotation + 'deg);pointer-events:none;';
    wrapper.parentNode.appendChild(control);
    
    var rotH = document.createElement('div');
    rotH.style.cssText = 'position:absolute;top:-30px;left:50%;transform:translateX(-50%);width:14px;height:14px;background:#3b82f6;border-radius:50%;cursor:grab;pointer-events:auto;';
    rotH.dataset.action = 'rotate';
    control.appendChild(rotH);
    
    var line = document.createElement('div');
    line.style.cssText = 'position:absolute;top:-15px;left:50%;width:1px;height:15px;background:#3b82f6;';
    control.appendChild(line);
    
    var corners = [
        {css: 'top:-4px;left:-4px;cursor:nw-resize;', action: 'nw'},
        {css: 'top:-4px;right:-4px;cursor:ne-resize;', action: 'ne'},
        {css: 'bottom:-4px;left:-4px;cursor:sw-resize;', action: 'sw'},
        {css: 'bottom:-4px;right:-4px;cursor:se-resize;', action: 'se'}
    ];
    corners.forEach(function(c) {
        var h = document.createElement('div');
        h.style.cssText = 'position:absolute;' + c.css + 'width:8px;height:8px;background:#3b82f6;border:1px solid #fff;pointer-events:auto;';
        h.dataset.action = c.action;
        control.appendChild(h);
    });
    
    updateOperationButtons();
}

function updateControl(elemData) {
    var control = document.querySelector('.elem-control');
    if (control) {
        control.style.left = elemData.x + '%';
        control.style.top = elemData.y + '%';
        control.style.width = elemData.w + '%';
        control.style.height = elemData.h + '%';
        control.style.transform = 'rotate(' + elemData.rotation + 'deg)';
    }
}

function updateOperationButtons() {
    var enabled = !!currentCanvas.selectedElement;
    var btns = ['btn-maximize', 'btn-flip-h', 'btn-flip-v', 'btn-rotate-l', 'btn-rotate-r', 'btn-forward', 'btn-backward', 'btn-delete'];
    btns.forEach(function(id) {
        var btn = document.getElementById(id);
        if (btn) {
            btn.disabled = !enabled;
            if (enabled) {
                btn.classList.remove('opacity-50', 'cursor-not-allowed', 'disabled:opacity-50', 'disabled:cursor-not-allowed');
                btn.classList.add('hover:bg-gray-300');
            } else {
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                btn.classList.remove('hover:bg-gray-300');
            }
        }
    });
}

function maximizeElement() {
    if (!currentCanvas.selectedElement) return;
    var elemData = currentCanvas.selectedElement;
    elemData.x = 0;
    elemData.y = 0;
    elemData.w = 100;
    elemData.h = 100;
    elemData.dom.style.left = '0%';
    elemData.dom.style.top = '0%';
    elemData.dom.style.width = '100%';
    elemData.dom.style.height = '100%';
    
    // Scale font size for text elements
    if (elemData.type === 'text' && elemData.styles && elemData.styles.fontSize) {
        var scale = Math.min(elemData.w / 80, elemData.h / 80);
        var newFontSize = Math.round(elemData.styles.fontSize * scale);
        elemData.dom.style.fontSize = newFontSize + 'px';
    }

    updateControl(elemData);
    updatePreview();
}

function flipHorizontal() {
    if (!currentCanvas.selectedElement) return;
    var elemData = currentCanvas.selectedElement;
    var scaleX = elemData.scaleX || 1;
    scaleX = scaleX * -1;
    elemData.scaleX = scaleX;
    elemData.dom.style.transform = 'rotate(' + elemData.rotation + 'deg) scaleX(' + scaleX + ')';
    updatePreview();
}

function flipVertical() {
    if (!currentCanvas.selectedElement) return;
    var elemData = currentCanvas.selectedElement;
    var scaleY = elemData.scaleY || 1;
    scaleY = scaleY * -1;
    elemData.scaleY = scaleY;
    elemData.dom.style.transform = 'rotate(' + elemData.rotation + 'deg) scaleY(' + scaleY + ')';
    updatePreview();
}

function rotateLeft() {
    if (!currentCanvas.selectedElement) return;
    var elemData = currentCanvas.selectedElement;
    elemData.rotation -= 90;
    elemData.dom.style.transform = 'rotate(' + elemData.rotation + 'deg)';
    updateControl(elemData);
    updatePreview();
}

function rotateRight() {
    if (!currentCanvas.selectedElement) return;
    var elemData = currentCanvas.selectedElement;
    elemData.rotation += 90;
    elemData.dom.style.transform = 'rotate(' + elemData.rotation + 'deg)';
    updateControl(elemData);
    updatePreview();
}

function bringForward() {
    if (!currentCanvas.selectedElement) return;
    var elemData = currentCanvas.selectedElement;
    var idx = currentCanvas.elements.indexOf(elemData);
    if (idx < currentCanvas.elements.length - 1) {
        currentCanvas.elements.splice(idx, 1);
        currentCanvas.elements.push(elemData);
        var pa = elemData.dom.parentElement;
        pa.appendChild(elemData.dom);
        updateLayersList();
        updateOperationButtons();
        updatePreview();
    }
}

function sendBackward() {
    if (!currentCanvas.selectedElement) return;
    var elemData = currentCanvas.selectedElement;
    var idx = currentCanvas.elements.indexOf(elemData);
    if (idx > 0) {
        currentCanvas.elements.splice(idx, 1);
        currentCanvas.elements.unshift(elemData);
        var pa = elemData.dom.parentElement;
        pa.insertBefore(elemData.dom, pa.firstChild);
        updateLayersList();
        updateOperationButtons();
        updatePreview();
    }
}

function deleteElement() {
    if (!currentCanvas.selectedElement) return;
    var elemData = currentCanvas.selectedElement;
    var idx = currentCanvas.elements.indexOf(elemData);
    if (idx > -1) {
        currentCanvas.elements.splice(idx, 1);
    }
    elemData.dom.remove();
    document.querySelectorAll('.elem-control').forEach(function(c) { c.remove(); });
    currentCanvas.selectedElement = null;
    updateLayersList();
    updateOperationButtons();
    updatePreview();
}

function setupElemEvents(wrapper, elemData) {
    var isDrag = false, isResize = false, isRotate = false;
    var startX, startY, startX2, startY2, startW, startH, startAngle, startRot;
    
    wrapper.addEventListener('mousedown', function(e) {
        if (e.target.dataset.action) return;
        isDrag = true;
        startX = e.clientX;
        startY = e.clientY;
        startX2 = elemData.x;
        startY2 = elemData.y;
        selectElem(elemData);
        e.stopPropagation();
        e.preventDefault();
    });
    
    var currentResizeAction = null;
    
    document.addEventListener('mousedown', function(e) {
        if (!e.target.dataset.action) return;
        var action = e.target.dataset.action;
        if (action === 'rotate') {
            isRotate = true;
            startRot = elemData.rotation;
            var rect = wrapper.getBoundingClientRect();
            startAngle = Math.atan2(e.clientY - (rect.top + rect.height/2), e.clientX - (rect.left + rect.width/2)) * 180 / Math.PI;
        } else {
            isResize = true;
            currentResizeAction = action;
            startX = e.clientX;
            startY = e.clientY;
            startW = elemData.w;
            startH = elemData.h;
            startX2 = elemData.x;
            startY2 = elemData.y;
        }
        e.stopPropagation();
        e.preventDefault();
    });
    
    document.addEventListener('mousemove', function(e) {
        if (!currentCanvas.selectedElement || currentCanvas.selectedElement !== elemData) return;
        var pa = wrapper.parentElement;
        
        if (isDrag) {
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            elemData.x = startX2 + (dx / pa.offsetWidth) * 100;
            elemData.y = startY2 + (dy / pa.offsetHeight) * 100;
            wrapper.style.left = elemData.x + '%';
            wrapper.style.top = elemData.y + '%';
            updateControl(elemData);
        }
        
        if (isResize) {
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            var newW = startW;
            var newH = startH;
            var newX = startX2;
            var newY = startY2;
            
            // Adjust based on resize handle position
            if (currentResizeAction === 'se') {
                newW = startW + (dx / pa.offsetWidth) * 100;
                newH = startH + (dy / pa.offsetHeight) * 100;
            } else if (currentResizeAction === 'sw') {
                newW = startW - (dx / pa.offsetWidth) * 100;
                newH = startH + (dy / pa.offsetHeight) * 100;
                newX = startX2 + (dx / pa.offsetWidth) * 100;
            } else if (currentResizeAction === 'ne') {
                newW = startW + (dx / pa.offsetWidth) * 100;
                newH = startH - (dy / pa.offsetHeight) * 100;
                newY = startY2 + (dy / pa.offsetHeight) * 100;
            } else if (currentResizeAction === 'nw') {
                newW = startW - (dx / pa.offsetWidth) * 100;
                newH = startH - (dy / pa.offsetHeight) * 100;
                newX = startX2 + (dx / pa.offsetWidth) * 100;
                newY = startY2 + (dy / pa.offsetHeight) * 100;
            }
            
            elemData.w = Math.max(10, newW);
            elemData.h = Math.max(10, newH);
            elemData.x = newX;
            elemData.y = newY;
            wrapper.style.width = elemData.w + '%';
            wrapper.style.height = elemData.h + '%';
            wrapper.style.left = elemData.x + '%';
            wrapper.style.top = elemData.y + '%';
            
            // Scale font size for text elements
            if (elemData.type === 'text' && elemData.styles && elemData.styles.fontSize) {
                var scale = Math.min(elemData.w / 80, elemData.h / 80);
                var newFontSize = Math.round(elemData.styles.fontSize * scale);
                wrapper.style.fontSize = newFontSize + 'px';
            }
            
            updateControl(elemData);
        }
        
        if (isRotate) {
            var rect = wrapper.getBoundingClientRect();
            var angle = Math.atan2(e.clientY - (rect.top + rect.height/2), e.clientX - (rect.left + rect.width/2)) * 180 / Math.PI;
            elemData.rotation = startRot + (angle - startAngle);
            wrapper.style.transform = 'rotate(' + elemData.rotation + 'deg)';
            updateControl(elemData);
        }
    });
    
    document.addEventListener('mouseup', function() {
        if (isDrag || isResize || isRotate) {
            updatePreview();
        }
        isDrag = false;
        isResize = false;
        isRotate = false;
        currentResizeAction = null;
    });
    
    wrapper.addEventListener('click', function(e) {
        if (!e.target.dataset.action) {
            selectElem(elemData);
            updateLayersList();
        }
    });
}

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(function(b) {
        b.classList.remove('bg-blue-100', 'text-blue-600');
        b.classList.add('text-gray-500');
    });
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.add('bg-blue-100', 'text-blue-600');
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.remove('text-gray-500');
    document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.add('hidden'); });
    document.getElementById('tab-' + tab).classList.remove('hidden');
}

document.addEventListener('change', function(e) {
    if (e.target.id === 'image-upload') {
        var files = e.target.files;
        var uploadedImagesDiv = document.getElementById('uploaded-images');
        
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(event) {
                    var dataUrl = event.target.result;
                    var div = document.createElement('div');
                    div.className = 'cursor-pointer border-2 border-gray-300 rounded p-1 hover:border-blue-500';
                    div.style.cssText = 'width: 100%; aspect-ratio: 1; object-fit: contain;';
                    div.innerHTML = '<img src="' + dataUrl + '" class="w-full h-full object-contain" />';
                    div.onclick = function() { addUploadedImage(dataUrl); };
                    uploadedImagesDiv.appendChild(div);
                };
                reader.readAsDataURL(file);
            }
        }
        e.target.value = '';
    }
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.tab-btn')) {
        switchTab(e.target.closest('.tab-btn').dataset.tab);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('close-dialog').onclick = closeDialog;
    document.getElementById('cancel-btn').onclick = closeDialog;
    document.getElementById('customization-dialog').onclick = function(e) {
        if (e.target === this) closeDialog();
    };
});

// Initialize text controls
function initTextControls() {
    var fontSizeInput = document.getElementById('font-size');
    var fontSizeLabel = document.getElementById('font-size-label');
    if (fontSizeInput && fontSizeLabel) {
        fontSizeInput.addEventListener('input', function() {
            fontSizeLabel.textContent = this.value + 'px';
        });
        fontSizeInput.addEventListener('change', function() {
            fontSizeLabel.textContent = this.value + 'px';
        });
        fontSizeLabel.textContent = fontSizeInput.value + 'px';
    }
    
    document.querySelectorAll('.color-swatch').forEach(function(swatch) {
        swatch.onclick = function() {
            document.querySelectorAll('.color-swatch').forEach(function(s) {
                s.classList.remove('border-blue-500');
                s.classList.add('border-gray-300');
            });
            swatch.classList.remove('border-gray-300');
            swatch.classList.add('border-blue-500');
            var color = swatch.dataset.color;
            setTextColor(color);
        };
    });
}

var currentTextColor = '#000000';

function setTextColor(color) {
    currentTextColor = color;
    document.getElementById('text-color-custom').value = color;
    document.querySelectorAll('.color-swatch').forEach(function(s) {
        s.classList.remove('border-blue-500');
        s.classList.add('border-gray-300');
    });
    document.getElementById('text-color-custom').classList.remove('border-gray-300');
    document.getElementById('text-color-custom').classList.add('border-blue-500');
}

function addText() {
    var textInput = document.getElementById('text-input');
    var fontSize = document.getElementById('font-size').value;
    var fontFamily = document.getElementById('text-font').value;
    
    if (!textInput.value.trim()) {
        alert('Please enter text');
        return;
    }
    
    addTextToCanvas(textInput.value, fontSize, currentTextColor, fontFamily);
    textInput.value = '';
}

function addTextToCanvas(text, size, color, font) {
    var areaData = window.currentAreaData;
    var areaElemId = 'print-area-' + (areaData && (areaData.id || areaData.print_area_id) ? (areaData.id || areaData.print_area_id) : 'default');
    var printArea = document.getElementById(areaElemId);
    if (!printArea) return;
    
    var wrapper = document.createElement('div');
    wrapper.className = 'canvas-elem';
    wrapper.style.cssText = 'position:absolute;left:10%;top:10%;width:80%;height:80%;cursor:move;transform-origin:center center;';
    wrapper.textContent = text;
    wrapper.style.fontSize = (size || 24) + 'px';
    wrapper.style.color = color || '#000';
    wrapper.style.fontFamily = font || 'Noto Sans TC, sans-serif';
    wrapper.style.display = 'flex';
    wrapper.style.alignItems = 'center';
    wrapper.style.justifyContent = 'center';
    wrapper.style.wordBreak = 'break-word';
    wrapper.style.textAlign = 'center';
    
    var elemData = {
        dom: wrapper,
        type: 'text',
        content: text,
        x: 10, y: 10, w: 80, h: 80, rotation: 0,
        styles: { color: color || '#000', fontSize: size || 24, fontFamily: font || 'Noto Sans TC, sans-serif' },
        id: Date.now()
    };
    
    setupElemEvents(wrapper, elemData);
    printArea.appendChild(wrapper);
    currentCanvas.elements.push(elemData);
    selectElem(elemData);
    updateLayersList();
    updatePreview();
}
</script>
@endpushOnce
