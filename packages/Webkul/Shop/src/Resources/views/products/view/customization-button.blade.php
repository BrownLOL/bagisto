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
    elements: [],
    selectedElement: null
};

function openCustomizationDialog() {
    document.getElementById('customization-dialog').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    currentCanvas.elements = [];
    currentCanvas.selectedElement = null;
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
    
    currentCanvas.elements = [];
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
        var printArea = document.createElement('div');
        printArea.id = 'print-area';
        printArea.style.cssText = 'position:absolute;left:' + areaData.x + '%;top:' + areaData.y + '%;width:' + areaData.width + '%;height:' + areaData.height + '%;border:2px dashed red;background:rgba(255,255,255,0.3);';
        canvas.appendChild(printArea);
    }
}

function addUploadedImage(dataUrl) {
    var printArea = document.getElementById('print-area');
    if (!printArea) return;
    
    var wrapper = document.createElement('div');
    wrapper.className = 'canvas-elem';
    wrapper.style.cssText = 'position:absolute;left:10%;top:10%;width:80%;height:80%;cursor:move;transform-origin:center center;';
    
    var img = document.createElement('img');
    img.src = dataUrl;
    img.style.cssText = 'width:100%;height:100%;object-fit:contain;pointer-events:none;';
    wrapper.appendChild(img);
    
    var elemData = {
        dom: wrapper,
        type: 'image',
        content: dataUrl,
        x: 10, y: 10, w: 80, h: 80, rotation: 0
    };
    
    setupElemControls(wrapper, elemData);
    setupDrag(wrapper, elemData);
    setupResize(wrapper, elemData);
    setupRotate(wrapper, elemData);
    
    printArea.appendChild(wrapper);
    currentCanvas.elements.push(elemData);
    selectElem(elemData);
}

function selectElem(elemData) {
    currentCanvas.selectedElement = elemData;
    document.querySelectorAll('.elem-control').forEach(function(c) { c.remove(); });
    
    var wrapper = elemData.dom;
    var control = document.createElement('div');
    control.className = 'elem-control';
    control.style.cssText = 'position:absolute;left:' + elemData.x + '%;top:' + elemData.y + '%;width:' + elemData.w + '%;height:' + elemData.h + '%;border:2px solid #3b82f6;transform:rotate(' + elemData.rotation + 'deg);pointer-events:none;';
    wrapper.parentNode.appendChild(control);
    
    // 旋转手柄
    var rotH = document.createElement('div');
    rotH.style.cssText = 'position:absolute;top:-30px;left:50%;transform:translateX(-50%);width:14px;height:14px;background:#3b82f6;border-radius:50%;cursor:grab;pointer-events:auto;';
    rotH.dataset.action = 'rotate';
    control.appendChild(rotH);
    
    var line = document.createElement('div');
    line.style.cssText = 'position:absolute;top:-15px;left:50%;width:1px;height:15px;background:#3b82f6;';
    control.appendChild(line);
    
    // 四角
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

function setupDrag(wrapper, elemData) {
    var isDrag = false, startX, startY, startX2, startY2;
    
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
    
    document.addEventListener('mousemove', function(e) {
        if (!isDrag || currentCanvas.selectedElement !== elemData) return;
        var pa = wrapper.parentElement;
        var dx = e.clientX - startX;
        var dy = e.clientY - startY;
        var newX = Math.max(0, Math.min(100 - elemData.w, startX2 + (dx / pa.offsetWidth) * 100));
        var newY = Math.max(0, Math.min(100 - elemData.h, startY2 + (dy / pa.offsetHeight) * 100));
        elemData.x = newX;
        elemData.y = newY;
        wrapper.style.left = newX + '%';
        wrapper.style.top = newY + '%';
        updateControl(elemData);
    });
    
    document.addEventListener('mouseup', function() { isDrag = false; });
}

function setupResize(wrapper, elemData) {
    var isResize = false, action = '', startX, startY, startW, startH, startX2, startY2;
    
    document.addEventListener('mousedown', function(e) {
        if (!e.target.dataset.action || e.target.dataset.action === 'rotate') return;
        isResize = true;
        action = e.target.dataset.action;
        startX = e.clientX;
        startY = e.clientY;
        startW = elemData.w;
        startH = elemData.h;
        startX2 = elemData.x;
        startY2 = elemData.y;
        e.stopPropagation();
        e.preventDefault();
    });
    
    document.addEventListener('mousemove', function(e) {
        if (!isResize || currentCanvas.selectedElement !== elemData) return;
        var pa = wrapper.parentElement;
        var dx = e.clientX - startX;
        var dy = e.clientY - startY;
        
        if (action === 'se') {
            elemData.w = Math.max(10, Math.min(100 - elemData.x, startW + (dx / pa.offsetWidth) * 100));
            elemData.h = Math.max(10, Math.min(100 - elemData.y, startH + (dy / pa.offsetHeight) * 100));
        } else if (action === 'nw') {
            var newW = Math.max(10, startW - (dx / pa.offsetWidth) * 100);
            var newH = Math.max(10, startH - (dy / pa.offsetHeight) * 100);
            var newX = startX2 + (startW - newW);
            var newY = startY2 + (startH - newH);
            if (newX >= 0 && newY >= 0) {
                elemData.x = newX;
                elemData.y = newY;
                elemData.w = newW;
                elemData.h = newH;
            }
        } else if (action === 'ne') {
            elemData.w = Math.max(10, Math.min(100 - elemData.x, startW + (dx / pa.offsetWidth) * 100));
            var newH = Math.max(10, startH - (dy / pa.offsetHeight) * 100);
            var newY = startY2 + (startH - newH);
            if (newY >= 0) {
                elemData.y = newY;
                elemData.h = newH;
            }
        } else if (action === 'sw') {
            var newW = Math.max(10, startW - (dx / pa.offsetWidth) * 100);
            var newX = startX2 + (startW - newW);
            elemData.h = Math.max(10, Math.min(100 - elemData.y, startH + (dy / pa.offsetHeight) * 100));
            if (newX >= 0) {
                elemData.x = newX;
                elemData.w = newW;
            }
        }
        
        wrapper.style.left = elemData.x + '%';
        wrapper.style.top = elemData.y + '%';
        wrapper.style.width = elemData.w + '%';
        wrapper.style.height = elemData.h + '%';
        updateControl(elemData);
    });
    
    document.addEventListener('mouseup', function() { isResize = false; });
}

function setupRotate(wrapper, elemData) {
    var isRotate = false, startAngle, startRot;
    
    document.addEventListener('mousedown', function(e) {
        if (!e.target.dataset.action || e.target.dataset.action !== 'rotate') return;
        isRotate = true;
        startRot = elemData.rotation;
        var rect = wrapper.getBoundingClientRect();
        var cx = rect.left + rect.width / 2;
        var cy = rect.top + rect.height / 2;
        startAngle = Math.atan2(e.clientY - cy, e.clientX - cx) * 180 / Math.PI;
        e.stopPropagation();
        e.preventDefault();
    });
    
    document.addEventListener('mousemove', function(e) {
        if (!isRotate || currentCanvas.selectedElement !== elemData) return;
        var angle = Math.atan2(e.clientY - (wrapper.getBoundingClientRect().top + wrapper.offsetHeight/2), e.clientX - (wrapper.getBoundingClientRect().left + wrapper.offsetWidth/2)) * 180 / Math.PI;
        elemData.rotation = startRot + (angle - startAngle);
        wrapper.style.transform = 'rotate(' + elemData.rotation + 'deg)';
        updateControl(elemData);
    });
    
    document.addEventListener('mouseup', function() { isRotate = false; });
}

function setupElemControls(wrapper, elemData) {
    wrapper.addEventListener('click', function(e) {
        if (!e.target.dataset.action) {
            selectElem(elemData);
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
</script>
@endpushOnce
