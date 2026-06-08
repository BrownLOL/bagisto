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
        b.classList.remove('bg-blue-100', 'text-blue-600');
        b.classList.add('text-gray-500');
    });
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.add('bg-blue-100', 'text-blue-600');
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.remove('text-gray-500');
    document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.add('hidden'); });
    document.getElementById('tab-' + tab).classList.remove('hidden');
}

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
