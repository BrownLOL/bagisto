@php
    $productId = $product->id ?? 0;
@endphp

<button
    type="button"
    onclick="event.preventDefault(); openCustomizationDialog();"
    class="secondary-button w-full mt-4"
>
    Customize Now
</button>

@pushOnce('scripts')
<script>
var currentCanvas = {
    productImage: null,
    printArea: null,
    elements: [],
    selectedElement: null
};

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
                    div.style.cssText = 'width: 120px; height: 120px;';
                    div.innerHTML = '<img src="' + imgUrl + '" class="w-full h-full object-contain" />';
                    div.onclick = function() { selectProductImage(imgUrl, item); };
                    productImagesDiv.appendChild(div);
                });
                
                selectProductImage(data.data[0].image_url || data.data[0].url, data.data[0]);
            }
        });
}

function selectProductImage(imgUrl, areaData) {
    var canvas = document.getElementById('design-canvas');
    var savedElements = currentCanvas.elements.slice();
    
    currentCanvas = {
        productImage: imgUrl,
        printArea: areaData,
        elements: [],
        selectedElement: null
    };
    
    canvas.innerHTML = '';
    canvas.style.position = 'relative';
    
    var bgImg = document.createElement('img');
    bgImg.src = imgUrl;
    bgImg.style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;object-fit:contain;pointer-events:none;';
    canvas.appendChild(bgImg);
    
    if (areaData && areaData.x !== undefined) {
        var printArea = document.createElement('div');
        printArea.id = 'print-area';
        printArea.style.cssText = 'position:absolute;left:' + areaData.x + '%;top:' + areaData.y + '%;width:' + areaData.width + '%;height:' + areaData.height + '%;border:2px dashed red;background:rgba(255,255,255,0.3);';
        canvas.appendChild(printArea);
        currentCanvas.printAreaDom = printArea;
    }
    
    savedElements.forEach(function(elem) {
        addElementToCanvas(elem.type, elem.content, elem.styles, false);
    });
}

function addUploadedImage(dataUrl) {
    addElementToCanvas('image', dataUrl, {}, true);
}

function addElementToCanvas(type, content, styles, autoSelect) {
    var printArea = document.getElementById('print-area');
    if (!printArea) return;
    
    var elem = document.createElement('div');
    elem.style.cssText = 'position:absolute;left:10%;top:10%;width:80%;height:80%;cursor:move;transform-origin:center center;';
    
    if (type === 'image') {
        var img = document.createElement('img');
        img.src = content;
        img.style.cssText = 'width:100%;height:100%;object-fit:contain;pointer-events:none;';
        elem.appendChild(img);
    } else if (type === 'text') {
        elem.textContent = content;
        elem.style.fontSize = '24px';
        elem.style.color = styles.color || '#000';
        elem.style.display = 'flex';
        elem.style.alignItems = 'center';
        elem.style.justifyContent = 'center';
    }
    
    var elementData = {
        type: type,
        content: content,
        styles: styles,
        dom: elem,
        transform: { x: 10, y: 10, width: 80, height: 80, rotation: 0 }
    };
    
    setupElementControls(elem, elementData);
    printArea.appendChild(elem);
    currentCanvas.elements.push(elementData);
    
    if (autoSelect || currentCanvas.elements.length === 1) {
        selectElement(elementData);
    }
}

function setupElementControls(elem, elementData) {
    var isDragging = false;
    var isResizing = false;
    var isRotating = false;
    var startX, startY, startLeft, startTop, startW, startH, startAngle;
    
    elem.addEventListener('mousedown', function(e) {
        if (e.target.classList.contains('ctrl-handle')) return;
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        startLeft = elementData.transform.x;
        startTop = elementData.transform.y;
        selectElement(elementData);
        e.stopPropagation();
        e.preventDefault();
    });
    
    document.addEventListener('mousemove', function(e) {
        if (!currentCanvas.selectedElement) return;
        
        if (isDragging && currentCanvas.selectedElement === elementData) {
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            var parent = elem.parentElement;
            var pw = parent.offsetWidth;
            var ph = parent.offsetHeight;
            
            var newX = Math.max(0, Math.min(100 - elementData.transform.width, startLeft + (dx / pw) * 100));
            var newY = Math.max(0, Math.min(100 - elementData.transform.height, startTop + (dy / ph) * 100));
            
            elementData.transform.x = newX;
            elementData.transform.y = newY;
            elem.style.left = newX + '%';
            elem.style.top = newY + '%';
        }
        
        if (isResizing && currentCanvas.selectedElement === elementData) {
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            var parent = elem.parentElement;
            var pw = parent.offsetWidth;
            var ph = parent.offsetHeight;
            
            var newW = Math.max(10, Math.min(100, startW + (dx / pw) * 100));
            var newH = Math.max(10, Math.min(100, startH + (dy / ph) * 100));
            
            elementData.transform.width = newW;
            elementData.transform.height = newH;
            elem.style.width = newW + '%';
            elem.style.height = newH + '%';
        }
        
        if (isRotating && currentCanvas.selectedElement === elementData) {
            var rect = elem.getBoundingClientRect();
            var cx = rect.left + rect.width / 2;
            var cy = rect.top + rect.height / 2;
            var angle = Math.atan2(e.clientY - cy, e.clientX - cx) * 180 / Math.PI + 90;
            
            elementData.transform.rotation = angle;
            elem.style.transform = 'rotate(' + angle + 'deg)';
            updateControlPositions(elementData);
        }
    });
    
    document.addEventListener('mouseup', function() {
        isDragging = false;
        isResizing = false;
        isRotating = false;
    });
}

function selectElement(elementData) {
    document.querySelectorAll('.ctrl-panel').forEach(function(p) { p.remove(); });
    currentCanvas.selectedElement = elementData;
    
    var parent = elementData.dom.parentElement;
    var panel = document.createElement('div');
    panel.className = 'ctrl-panel';
    panel.style.cssText = 'position:absolute;left:' + elementData.transform.x + '%;top:' + elementData.transform.y + '%;width:' + elementData.transform.width + '%;height:' + elementData.transform.height + ';border:2px solid #3b82f6;pointer-events:none;transform:rotate(' + elementData.transform.rotation + 'deg);';
    parent.appendChild(panel);
    
    // 旋转手柄
    var rotHandle = document.createElement('div');
    rotHandle.className = 'ctrl-handle';
    rotHandle.style.cssText = 'position:absolute;top:-30px;left:50%;transform:translateX(-50%);width:16px;height:16px;background:#3b82f6;border-radius:50%;cursor:grab;pointer-events:auto;';
    rotHandle.addEventListener('mousedown', function(e) {
        isRotating = true;
        e.stopPropagation();
        e.preventDefault();
    });
    panel.appendChild(rotHandle);
    
    // 连接线
    var line = document.createElement('div');
    line.style.cssText = 'position:absolute;top:-15px;left:50%;width:1px;height:15px;background:#3b82f6;';
    panel.appendChild(line);
    
    // 四角缩放手柄
    var corners = [
        { name: 'nw', top: '-4px', left: '-4px', cursor: 'nw-resize' },
        { name: 'ne', top: '-4px', right: '-4px', cursor: 'ne-resize' },
        { name: 'sw', bottom: '-4px', left: '-4px', cursor: 'sw-resize' },
        { name: 'se', bottom: '-4px', right: '-4px', cursor: 'se-resize' }
    ];
    
    corners.forEach(function(c) {
        var h = document.createElement('div');
        h.className = 'ctrl-handle';
        var pos = 'position:absolute;';
        if (c.top) pos += 'top:' + c.top + ';';
        if (c.bottom) pos += 'bottom:' + c.bottom + ';';
        if (c.left) pos += 'left:' + c.left + ';';
        if (c.right) pos += 'right:' + c.right + ';';
        h.style.cssText = pos + 'width:10px;height:10px;background:#3b82f6;border:1px solid #fff;cursor:' + c.cursor + ';pointer-events:auto;';
        h.addEventListener('mousedown', function(e) {
            isResizing = true;
            startX = e.clientX;
            startY = e.clientY;
            startW = elementData.transform.width;
            startH = elementData.transform.height;
            e.stopPropagation();
            e.preventDefault();
        });
        panel.appendChild(h);
    });
}

function updateControlPositions(elementData) {
    var panel = document.querySelector('.ctrl-panel');
    if (panel) {
        panel.style.left = elementData.transform.x + '%';
        panel.style.top = elementData.transform.y + '%';
        panel.style.width = elementData.transform.width + '%';
        panel.style.height = elementData.transform.height + '%';
    }
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
                    div.style.cssText = 'width: 120px; height: 120px;';
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
        var btn = e.target.closest('.tab-btn');
        switchTab(btn.dataset.tab);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('close-dialog').onclick = closeDialog;
    document.getElementById('cancel-btn').onclick = closeDialog;
    document.getElementById('customization-dialog').onclick = function(e) {
        if (e.target === this) closeDialog();
    };
});

var isDragging, isResizing, isRotating, startX, startY, startLeft, startTop, startW, startH;
</script>
@endpushOnce
