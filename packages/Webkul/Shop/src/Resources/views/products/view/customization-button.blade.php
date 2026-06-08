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
        })
        .catch(function(error) {
            console.error('API error:', error);
        });
}

var currentCanvas = {
    productImage: null,
    printArea: null,
    elements: []
};

function selectProductImage(imgUrl, areaData) {
    var canvas = document.getElementById('design-canvas');
    
    // 保存当前元素
    var savedElements = currentCanvas.elements.slice();
    
    // 重置
    currentCanvas = {
        productImage: imgUrl,
        printArea: areaData,
        elements: []
    };
    
    // 渲染设计区域
    canvas.innerHTML = '';
    canvas.style.position = 'relative';
    
    // 添加商品图片作为背景
    var bgImg = document.createElement('img');
    bgImg.src = imgUrl;
    bgImg.style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;object-fit:contain;';
    canvas.appendChild(bgImg);
    
    // 添加可打印区域
    if (areaData && areaData.x !== undefined) {
        var printArea = document.createElement('div');
        printArea.id = 'print-area';
        printArea.style.cssText = 'position:absolute;left:' + areaData.x + '%;top:' + areaData.y + '%;width:' + areaData.width + '%;height:' + areaData.height + '%;border:2px dashed red;background:rgba(255,255,255,0.5);';
        canvas.appendChild(printArea);
    }
    
    // 恢复之前的元素
    savedElements.forEach(function(elem) {
        addElementToCanvas(elem.type, elem.content, elem.styles);
    });
}

function addUploadedImage(dataUrl) {
    addElementToCanvas('image', dataUrl, {});
}

function addElementToCanvas(type, content, styles) {
    var printArea = document.getElementById('print-area');
    if (!printArea) return;
    
    var elem;
    if (type === 'image') {
        elem = document.createElement('img');
        elem.src = content;
    } else if (type === 'text') {
        elem = document.createElement('div');
        elem.textContent = content;
        elem.style.cssText = 'position:absolute;cursor:move;font-size:24px;color:' + (styles.color || '#000') + ';';
    }
    
    if (elem) {
        elem.style.cssText += 'position:absolute;left:10%;top:10%;width:80%;height:80%;object-fit:contain;transform-origin:center center;';
        elem.style.userSelect = 'none';
        elem.dataset.elementId = currentCanvas.elements.length;
        
        makeTransformable(elem);
        printArea.appendChild(elem);
        
        var elementData = {
            type: type,
            content: content,
            styles: styles,
            dom: elem,
            transform: { x: 10, y: 10, width: 80, height: 80, rotation: 0 }
        };
        currentCanvas.elements.push(elementData);
        currentCanvas.selectedElement = elementData;
    }
}

function makeTransformable(elem) {
    var isDragging = false;
    var isRotating = false;
    var startX, startY, startLeft, startTop, startRotation;
    var elementData = null;
    
    for (var i = 0; i < currentCanvas.elements.length; i++) {
        if (currentCanvas.elements[i].dom === elem) {
            elementData = currentCanvas.elements[i];
            break;
        }
    }
    
    elem.addEventListener('mousedown', function(e) {
        if (e.target.classList.contains('rotate-handle')) return;
        
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        startLeft = parseFloat(elem.style.left) || 10;
        startTop = parseFloat(elem.style.top) || 10;
        
        selectElement(elem);
        e.stopPropagation();
    });
    
    document.addEventListener('mousemove', function(e) {
        if (isDragging && elementData) {
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            var parent = elem.parentElement;
            var parentWidth = parent.offsetWidth;
            var parentHeight = parent.offsetHeight;
            
            var newLeft = Math.max(0, Math.min(100 - elementData.transform.width, startLeft + (dx / parentWidth) * 100));
            var newTop = Math.max(0, Math.min(100 - elementData.transform.height, startTop + (dy / parentHeight) * 100));
            
            elem.style.left = newLeft + '%';
            elem.style.top = newTop + '%';
            elementData.transform.x = newLeft;
            elementData.transform.y = newTop;
        }
        
        if (isRotating && elementData) {
            var rect = elem.getBoundingClientRect();
            var centerX = rect.left + rect.width / 2;
            var centerY = rect.top + rect.height / 2;
            var angle = Math.atan2(e.clientY - centerY, e.clientX - centerX) * 180 / Math.PI + 90;
            
            elem.style.transform = 'rotate(' + angle + 'deg)';
            elementData.transform.rotation = angle;
        }
    });
    
    document.addEventListener('mouseup', function() {
        isDragging = false;
        isRotating = false;
    });
    
    elem.addEventListener('click', function(e) {
        if (!e.target.classList.contains('rotate-handle')) {
            selectElement(elem);
        }
    });
}

function selectElement(elem) {
    document.querySelectorAll('.transform-controls').forEach(function(c) { c.remove(); });
    
    var elementData = null;
    for (var i = 0; i < currentCanvas.elements.length; i++) {
        if (currentCanvas.elements[i].dom === elem) {
            elementData = currentCanvas.elements[i];
            break;
        }
    }
    currentCanvas.selectedElement = elementData;
    
    var controls = document.createElement('div');
    controls.className = 'transform-controls';
    controls.style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;pointer-events:none;';
    
    // 旋转手柄（顶部中心）
    var rotateHandle = document.createElement('div');
    rotateHandle.className = 'rotate-handle';
    rotateHandle.style.cssText = 'position:absolute;top:-25px;left:50%;transform:translateX(-50%);width:16px;height:16px;background:#3b82f6;border-radius:50%;cursor:grab;pointer-events:auto;';
    rotateHandle.addEventListener('mousedown', function(e) {
        isRotating = true;
        e.stopPropagation();
    });
    controls.appendChild(rotateHandle);
    
    // 连接线
    var line = document.createElement('div');
    line.style.cssText = 'position:absolute;top:-20px;left:50%;width:1px;height:20px;background:#3b82f6;';
    controls.appendChild(line);
    
    // 四角缩放手柄
    var handles = ['nw', 'ne', 'sw', 'se'];
    var handlePositions = {
        nw: 'top:-4px;left:-4px;cursor:nw-resize;',
        ne: 'top:-4px;right:-4px;cursor:ne-resize;',
        sw: 'bottom:-4px;left:-4px;cursor:sw-resize;',
        se: 'bottom:-4px;right:-4px;cursor:se-resize;'
    };
    
    handles.forEach(function(h) {
        var handle = document.createElement('div');
        handle.className = 'resize-handle';
        handle.style.cssText = 'position:absolute;width:8px;height:8px;background:#3b82f6;border:1px solid #fff;' + handlePositions[h];
        handle.style.pointerEvents = 'auto';
        
        handle.addEventListener('mousedown', function(e) {
            isResizing = true;
            startX = e.clientX;
            startY = e.clientY;
            startLeft = parseFloat(elem.style.left) || 0;
            startTop = parseFloat(elem.style.top) || 0;
            startWidth = parseFloat(elem.style.width) || 80;
            startHeight = parseFloat(elem.style.height) || 80;
            e.stopPropagation();
        });
        
        controls.appendChild(handle);
    });
    
    elem.parentElement.appendChild(controls);
    
    // 添加元素自身的缩放监听
    document.addEventListener('mousemove', function(e) {
        if (isResizing && elementData) {
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            var parent = elem.parentElement;
            var parentWidth = parent.offsetWidth;
            var parentHeight = parent.offsetHeight;
            
            var newWidth = Math.max(10, Math.min(100, startWidth + (dx / parentWidth) * 100));
            var newHeight = Math.max(10, Math.min(100, startHeight + (dy / parentHeight) * 100));
            
            elem.style.width = newWidth + '%';
            elem.style.height = newHeight + '%';
            elementData.transform.width = newWidth;
            elementData.transform.height = newHeight;
        }
    });
}

var isResizing = false;
var startX, startY, startLeft, startTop, startWidth, startHeight;

function switchTab(tab) {
    console.log('Switching to tab:', tab);
    document.querySelectorAll('.tab-btn').forEach(function(b) {
        b.classList.remove('bg-blue-100', 'text-blue-600');
        b.classList.add('text-gray-500');
    });
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.add('bg-blue-100', 'text-blue-600');
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.remove('text-gray-500');
    document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.add('hidden'); });
    document.getElementById('tab-' + tab).classList.remove('hidden');
}

// Handle image upload
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
                    div.onclick = function() {
                        addUploadedImage(dataUrl);
                    };
                    uploadedImagesDiv.appendChild(div);
                };
                reader.readAsDataURL(file);
            }
        }
        
        e.target.value = '';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    console.log('Customization dialog script loaded');
    
    document.getElementById('close-dialog').onclick = function() { closeDialog(); };
    document.getElementById('cancel-btn').onclick = function() { closeDialog(); };
    document.getElementById('customization-dialog').onclick = function(e) {
        if (e.target === this) closeDialog();
    };
});

// Use event delegation for tab buttons (they are inside hidden dialog)
document.addEventListener('click', function(e) {
    if (e.target.closest('.tab-btn')) {
        var btn = e.target.closest('.tab-btn');
        var tab = btn.dataset.tab;
        switchTab(tab);
    }
});
</script>
@endpushOnce
