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
        elem.style.cssText += 'position:absolute;left:0;top:0;width:80%;height:80%;object-fit:contain;';
        elem.style.userSelect = 'none';
        elem.dataset.elementId = currentCanvas.elements.length;
        printArea.appendChild(elem);
        
        currentCanvas.elements.push({
            type: type,
            content: content,
            styles: styles,
            dom: elem
        });
    }
}

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
