<div id="customization-product-id" data-id="{{ $product->id ?? 0 }}" class="hidden"></div>

<button
    type="button"
    onclick="event.preventDefault(); openCustomizationDialog();"
    id="customize-now-btn"
    class="secondary-button w-full mt-4 flex items-center justify-center gap-2 hidden"
>
    Customize Now
    <span id="design-status-icon" class="hidden">
        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
    </span>
</button>

@pushOnce('styles')
<style>
.product-image-item.selected {
    border-color: #3b82f6 !important;
    background-color: rgba(59, 130, 246, 0.1);
}
</style>
@endpushOnce

@pushOnce('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function checkDesignStatus() {
    var statusIcon = document.getElementById('design-status-icon');
    if (!statusIcon || !window.customizationProductId) return;
    
    var productId = window.customizationProductId;
    var currentKey = 'current_design_' + productId;
    var currentUuid = localStorage.getItem(currentKey);
    
    if (currentUuid) {
        var designKey = 'design_' + currentUuid;
        var saved = localStorage.getItem(designKey);
        if (saved) {
            try {
                var data = JSON.parse(saved);
                // Check if any print_area has elements
                if (data.print_areas && Array.isArray(data.print_areas)) {
                    var hasElements = data.print_areas.some(function(pa) {
                        return pa.elements && pa.elements.length > 0;
                    });
                    if (hasElements) {
                        statusIcon.classList.remove('hidden');

                        return;
                    }
                }
            } catch (e) {}
        }
    }
    
    statusIcon.classList.add('hidden');

}

(function() {
    var el = document.getElementById('customization-product-id');
    if (el) {
        window.customizationProductId = parseInt(el.dataset.id) || 0;
    } else {
        window.customizationProductId = 0;
    }
    
    if (!window.customizationProductId) return;
    
    var productId = window.customizationProductId;
    var currentKey = 'current_design_' + productId;
    
    // Check URL parameter
    var urlParams = new URLSearchParams(window.location.search);
    var urlUuid = urlParams.get('design_uuid');
    
    if (urlUuid) {
        // URL has UUID → use it
        localStorage.setItem(currentKey, urlUuid);
    } else {
        // No UUID in URL → generate new one
        var uuid = generateDesignUUID();
        localStorage.setItem(currentKey, uuid);
    }
    
    // Wait for DOM to be ready, then check design status
    function waitForElementAndInit() {
        var el = document.getElementById('customization-product-id');
        var statusIcon = document.getElementById('design-status-icon');
        
        if (!el || !statusIcon) {
            setTimeout(waitForElementAndInit, 50);
            return;
        }
        
        window.customizationProductId = parseInt(el.dataset.id) || 0;
        
        if (!window.customizationProductId) return;
        
        var productId = window.customizationProductId;
        var currentKey = 'current_design_' + productId;
        
        // Check URL parameter
        var urlParams = new URLSearchParams(window.location.search);
        var urlUuid = urlParams.get('design_uuid');
        
        if (urlUuid) {
            // URL has UUID → use it
            localStorage.setItem(currentKey, urlUuid);
        } else {
            // No UUID in URL → generate new one
            var uuid = generateDesignUUID();
            localStorage.setItem(currentKey, uuid);
        }
        
        // Check if product has print areas and show button
        checkPrintAreasAndShowButton();
        
        // Check if there's a saved design and show status icon
        checkDesignStatus();
    }
    
    function checkPrintAreasAndShowButton() {
        var productId = window.customizationProductId;
        
        fetch('/customization/print-areas/' + productId)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var btn = document.getElementById('customize-now-btn');
                if (!btn) return;
                
                if (data.success && data.data && data.data.length > 0) {
                    // Has print areas → show button
                    btn.classList.remove('hidden');

                } else {
                    // No print areas → hide button
                    btn.classList.add('hidden');

                }
            })
            .catch(function(err) {

                // On error, hide button
                var btn = document.getElementById('customize-now-btn');
                if (btn) btn.classList.add('hidden');
            });
    }
    
    // Start polling
    waitForElementAndInit();
})();

var currentCanvas = {
    elements: [],
    selectedElement: null,
    currentImageKey: null,
    layerStore: {}
};

// Generate UUID for this design session
function generateDesignUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0;
        var v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// Get design UUID for this product (from current_design)
function getDesignUUID() {
    if (!window.customizationProductId) return null;
    
    var productId = window.customizationProductId;
    var currentKey = 'current_design_' + productId;
    var uuid = localStorage.getItem(currentKey);
    
    if (uuid) {
        window.designUUID = uuid;

        return uuid;
    }
    
    // Fallback: generate new one
    uuid = generateDesignUUID();
    localStorage.setItem(currentKey, uuid);
    window.designUUID = uuid;

    return uuid;
}

function openCustomizationDialog() {
    // Get or create UUID (persists during session via sessionStorage)
    window.designUUID = getDesignUUID();

    document.getElementById('customization-dialog').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    currentCanvas.elements = [];
    currentCanvas.selectedElement = null;
    updateOperationButtons();
    loadSavedCustomization(); // Load from localStorage first
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

function clearSavedDesign() {
    // 清空当前界面上的元素，不删除缓存
    currentCanvas.elements = [];
    currentCanvas.selectedElement = null;
    document.querySelectorAll('.canvas-elem').forEach(function(e) { e.remove(); });
    document.querySelectorAll('.elem-control').forEach(function(c) { c.remove(); });
    
    updateLayersList();
    updateOperationButtons();
    updatePreview();

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

    // 获取屏幕显示尺寸和原图尺寸用于坐标转换
    var displayRect = printArea.getBoundingClientRect();
    var areaX = areaData.x;      // 原图 print area X 百分比
    var areaY = areaData.y;      // 原图 print area Y 百分比
    var areaW = areaData.width;   // 原图 print area 宽度百分比
    var areaH = areaData.height;  // 原图 print area 高度百分比

    elemDivs.forEach(function(div, index) {
        var elemData = div._elemData;

        if (elemData) {
            // 屏幕百分比坐标
            var screenX = parseFloat(div.style.left) || 0;
            var screenY = parseFloat(div.style.top) || 0;
            var screenW = parseFloat(div.style.width) || 0;
            var screenH = parseFloat(div.style.height) || 0;
            
            // 转换为原图百分比坐标
            // screenX/screenY 是相对于 print area 的百分比（0-100）
            // originalX/originalY 是相对于原图的百分比（0-100）
            var originalX = areaX + screenX * areaW / 100;
            var originalY = areaY + screenY * areaH / 100;
            var originalW = screenW * areaW / 100;
            var originalH = screenH * areaH / 100;

            var elem = {
                id: elemData.id,  // 保存元素 id，用于图层切换
                type: elemData.type,
                content: elemData.content,
                // 屏幕坐标（用于前台显示和选择框）
                x: screenX,
                y: screenY,
                width: screenW,
                height: screenH,
                // 原图坐标（用于后台还原）
                originalX: originalX,
                originalY: originalY,
                originalWidth: originalW,
                originalHeight: originalH,
                rotation: elemData.rotation || 0,
                scaleX: elemData.scaleX || 1,
                scaleY: elemData.scaleY || 1,
                styles: elemData.styles || {}
            };
            elements.push(elem);
        }
    });
    
    // Save elements with their original screen coordinates
    // The coordinates will be converted when displaying in admin
    var productId = window.customizationProductId;


    // Save elements directly without generating preview image
    // Preview will be generated in admin when displaying
    saveCustomizationWithPreview('', elements);
}

// 上传预览图到服务器（返回 URL）
async function uploadPreviewImage(previewImage) {
    if (!previewImage || !previewImage.startsWith('data:')) {
        return previewImage; // 已经是 URL，直接返回
    }
    
    try {
        const response = await fetch('/api/customization/upload-preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ image: previewImage })
        });
        
        const result = await response.json();
        if (result.success) {

            return result.url;
        }
    } catch (error) {

    }
    
    return previewImage; // 上传失败，返回原始 base64
}

// 保存设计（保存元素数据，不生成预览图）
async function saveCustomizationWithPreview(previewImage, elements) {
    var productId = window.customizationProductId;


    // Prepare customization data for current print area
    var uuid = getDesignUUID(); // Ensure we have a UUID

    // Get current print area record key
    var currentRecordKey = currentCanvas.currentImageKey || 'default';
    var printAreaId = window.currentAreaData?.id || window.currentAreaData?.print_area_id;

    if (uuid && elements.length > 0) {
        var designKey = 'design_' + uuid;
        
        // Load existing data or create new structure
        var existingData = null;
        try {
            var saved = localStorage.getItem(designKey);
            if (saved) {
                existingData = JSON.parse(saved);
            }
        } catch (e) {}
        
        // Initialize print_areas array
        var printAreas = existingData && existingData.print_areas ? existingData.print_areas : [];
        
        // Find and update or add print area record
        var existingIndex = printAreas.findIndex(function(pa) {
            return pa.record_key === currentRecordKey;
        });
        
        // 获取 print area 位置信息
        var areaData = window.currentAreaData || {};
        var recordData = {
            print_area_id: parseInt(printAreaId),
            record_key: currentRecordKey,
            image_url: areaData.image_url || areaData.url || '',
            // 保存 print area 位置信息（百分比）
            x: areaData.x || 0,
            y: areaData.y || 0,
            width: areaData.width || 100,
            height: areaData.height || 100,
            // 屏幕百分比坐标，元素内容
            elements: elements
        };

        if (existingIndex >= 0) {
            printAreas[existingIndex] = recordData;
        } else {
            printAreas.push(recordData);
        }
        
        // 保存完整数据到 localStorage
        var designDataForStorage = {
            product_id: productId,
            print_areas: printAreas,
            selectedImageKey: currentRecordKey || window.currentAreaData?.print_area_id || window.currentAreaData?.id,  // 保存当前选中的 print area
            updated_at: new Date().toISOString()
        };
        
        localStorage.setItem(designKey, JSON.stringify(designDataForStorage));

        
        
        // Ensure UUID is in the list
        var uuidsKey = 'design_uuids_' + productId;
        var uuids = JSON.parse(localStorage.getItem(uuidsKey) || '[]');
        if (!uuids.includes(uuid)) {
            uuids.push(uuid);
            localStorage.setItem(uuidsKey, JSON.stringify(uuids));
        }
        
        // Also update layerStore with current elements
        currentCanvas.layerStore[currentRecordKey] = elements;
    }
    
    closeDialog();
    checkDesignStatus();
}

// Load saved customization from localStorage by design_uuid
function loadSavedCustomization() {
    var uuid = getDesignUUID(); // Get current UUID (creates one if not exists)
    if (!uuid) {

        return;
    }
    
    var key = 'design_' + uuid;

    var saved = localStorage.getItem(key);
    if (!saved) {

        return;
    }
    
    var savedData;
    try {
        savedData = JSON.parse(saved);

    } catch(e) {

        return;
    }
    
    // Get the print_areas array
    var printAreas = savedData.print_areas || [];
    
    if (!printAreas || printAreas.length === 0) {

        return;
    }
    
    // Restore all print area data to layerStore
    // selectProductImage will read from layerStore and create DOM elements
    printAreas.forEach(function(pa) {
        // 统一使用 print_area_id 或 id 作为 key
        var recordKey = pa.print_area_id || pa.id || pa.record_key || 'default';
        if (pa.elements && pa.elements.length > 0) {
            currentCanvas.layerStore[recordKey] = pa.elements;

        }
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
    
    // Remove borders from print areas and canvas wrapper in preview
    clone.querySelectorAll('[style*="border"]').forEach(function(elem) {
        elem.style.border = 'none';
    });
    
    // Remove background from print areas in preview
    clone.querySelectorAll('[class*="print-area"]').forEach(function(elem) {
        elem.style.background = 'transparent';
        elem.style.backgroundColor = 'transparent';
    });
    
    // Also remove background from the main container if any
    clone.querySelectorAll('[style*="background"]').forEach(function(elem) {
        elem.style.background = 'none';
    });
    
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
                    // 统一使用 print_area_id 或 id 作为 key
                    var recordKey = item.print_area_id || item.id || item.record_id || imgUrl;
                    var div = document.createElement('div');
                    div.className = 'product-image-item cursor-pointer border-2 border-gray-300 rounded p-1 hover:border-blue-500';
                    div.setAttribute('data-record-key', recordKey);
                    div.setAttribute('data-image-url', imgUrl);
                    div.style.cssText = 'width: 100%; aspect-ratio: 1; object-fit: contain;';
                    div.innerHTML = '<img src="' + imgUrl + '" class="w-full h-full object-contain" />';
                    div.onclick = function() { selectProductImage(imgUrl, item); };
                    productImagesDiv.appendChild(div);
                });
                
                // 根据保存的状态选择图片
                var savedData = null;
                try {
                    savedData = JSON.parse(localStorage.getItem('design_' + window.designUUID));
                } catch(e) {}
                
                if (savedData && savedData.selectedImageKey) {
                    // 有保存的状态，选择对应的图片
                    var targetItem = data.data.find(function(item) {
                        return (item.id || item.record_id || item.image_url || item.url) === savedData.selectedImageKey;
                    });
                    if (targetItem) {
                        var targetUrl = targetItem.image_url || targetItem.url;

                        selectProductImage(targetUrl, targetItem);
                    } else {
                        // 找不到对应图片，选择第一张
                        selectProductImage(data.data[0].image_url || data.data[0].url, data.data[0]);
                    }
                } else {
                    // 没有保存的状态，选择第一张
                    selectProductImage(data.data[0].image_url || data.data[0].url, data.data[0]);
                }
            }
        });
}

function selectProductImage(imgUrl, areaData) {
    // Save current area data
    window.currentAreaData = areaData;

    var canvas = document.getElementById('design-canvas-inner');

    // 用记录ID作为key，而不是图片URL（同一图片可能有多个PrintArea记录）
    var recordKey = areaData.print_area_id || areaData.id || areaData.record_id || imgUrl;

    if (currentCanvas.currentImageKey && currentCanvas.elements.length > 0) {
        currentCanvas.layerStore[currentCanvas.currentImageKey] = currentCanvas.elements.slice();
    }

    currentCanvas.currentImageKey = recordKey;

    if (currentCanvas.layerStore[currentCanvas.currentImageKey]) {
        currentCanvas.elements = currentCanvas.layerStore[currentCanvas.currentImageKey];
    } else {
        currentCanvas.elements = [];
    }
    currentCanvas.selectedElement = null;

    document.querySelectorAll('.canvas-elem').forEach(function(e) { e.remove(); });
    document.querySelectorAll('.elem-control').forEach(function(c) { c.remove(); });

    // Update image selection visual state
    document.querySelectorAll('.product-image-item').forEach(function(item) {
        item.classList.remove('selected');
    });
    var selectedItem = document.querySelector('.product-image-item[data-record-key="' + recordKey + '"]');
    if (selectedItem) {
        selectedItem.classList.add('selected');
    }

    canvas.innerHTML = '';
    canvas.style.position = 'relative';

    var bgImg = document.createElement('img');
    bgImg.src = imgUrl;
    bgImg.style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;object-fit:fill;pointer-events:none;';
    canvas.appendChild(bgImg);

    if (areaData && areaData.x !== undefined) {
        var printAreaId = 'print-area-' + (areaData.id || areaData.print_area_id || 'default');
        var printArea = document.createElement('div');
        printArea.id = printAreaId;
        printArea.style.cssText = 'position:absolute;left:' + areaData.x + '%;top:' + areaData.y + '%;width:' + areaData.width + '%;height:' + areaData.height + '%;border:2px dashed red;background:rgba(255,255,255,0.3);overflow:hidden;';
        canvas.appendChild(printArea);
        
        // Re-create element DOMs from currentCanvas.elements
        var savedElements = currentCanvas.elements.slice();
        currentCanvas.elements = [];
        
        savedElements.forEach(function(elemData) {
            elemData.dom = null;
            if (elemData.type === 'image') {
                addUploadedImage(elemData.content, elemData.x, elemData.y, elemData.w || elemData.width, elemData.h || elemData.height, elemData.rotation || 0, elemData.scaleX || 1, elemData.scaleY || 1, elemData.id);
            } else if (elemData.type === 'text') {
                // Direct call to addTextToCanvas with id
                addTextToCanvas(elemData.content, elemData.styles?.fontSize || 24, elemData.styles?.color || '#000', elemData.styles?.fontFamily || 'Noto Sans TC, sans-serif', {
                    x: elemData.x,
                    y: elemData.y,
                    w: elemData.w || elemData.width,
                    h: elemData.h || elemData.height,
                    rotation: elemData.rotation || 0,
                    scaleX: elemData.scaleX || 1,
                    scaleY: elemData.scaleY || 1,
                    styles: elemData.styles,
                    id: elemData.id
                });
            }
        });
    }
    
    updateLayersList();
    updateOperationButtons();
    updatePreview();
}

function addUploadedImage(dataUrl, x, y, w, h, rotation, scaleX, scaleY, id) {
    x = x !== undefined ? x : 10;
    y = y !== undefined ? y : 10;
    w = w !== undefined ? w : 80;
    h = h !== undefined ? h : 80;
    rotation = rotation !== undefined ? rotation : 0;
    scaleX = scaleX !== undefined ? scaleX : 1;
    scaleY = scaleY !== undefined ? scaleY : 1;
    var elemId = id !== undefined ? id : Date.now();
    
    var areaData = window.currentAreaData;
    var areaElemId = 'print-area-' + (areaData && (areaData.id || areaData.print_area_id) ? (areaData.id || areaData.print_area_id) : 'default');
    var printArea = document.getElementById(areaElemId);
    if (!printArea) return;
    
    var wrapper = document.createElement('div');
    wrapper.className = 'canvas-elem';
    wrapper.style.cssText = 'position:absolute;left:' + x + '%;top:' + y + '%;width:' + w + '%;height:' + h + '%;cursor:move;transform-origin:center center;transform:rotate(' + rotation + 'deg) scaleX(' + scaleX + ') scaleY(' + scaleY + ');';
    
    var img = document.createElement('img');
    img.src = dataUrl;
    img.style.cssText = 'width:100%;height:100%;object-fit:fill;pointer-events:none;';
    wrapper.appendChild(img);
    
    var elemData = {
        dom: wrapper,
        type: 'image',
        content: dataUrl,
        x: x, y: y, w: w, h: h, rotation: rotation, scaleX: scaleX, scaleY: scaleY,
        styles: {},
        id: elemId
    };
    wrapper._elemData = elemData;
    wrapper.dataset.id = elemId;
    setupElemEvents(wrapper, elemData);
    printArea.appendChild(wrapper);
    currentCanvas.elements.push(elemData);
    wrapper.offsetWidth; // 强制触发布局，确保 getBoundingClientRect 返回正确值
    selectElem(elemData);
    updateLayersList();
    updatePreview();
    
    // 如果是 base64（文字元素），上传到服务器获取 URL
    if (dataUrl && dataUrl.startsWith('data:image')) {
        uploadAndUpdateContent(elemData, dataUrl);
    }
}

function uploadAndUpdateContent(elemData, base64Data) {
    fetch('/customization/save-base64-image', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ image: base64Data })
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success && result.url) {
            elemData.content = result.url;
            if (elemData.dom) {
                var img = elemData.dom.querySelector('img');
                if (img) img.src = result.url;
            }
            updateLayersList();

        }
    })
    .catch(function(err) {

    });
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
    var dialog = document.getElementById('customization-dialog');
    var control = document.createElement('div');
    control.className = 'elem-control';
    
    // 兼容 w/h 和 width/height 两种字段名
    var elemW = elemData.w || elemData.width || 80;
    var elemH = elemData.h || elemData.height || 80;
    
    // 蓝框使用像素坐标，初始位置设为 0，后续由 updateControl 更新
    control.style.cssText = 'position:absolute;left:0;top:0;width:0;height:0;border:2px solid #3b82f6;pointer-events:none;';
    
    // 添加到 dialog 层级，避免被 overflow:hidden 裁剪
    dialog.appendChild(control);
    
    // 立即更新控制框位置
    updateControl(elemData);
    
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
    var elem = document.querySelector('.canvas-elem[data-id="' + elemData.id + '"]');
    var dialog = document.getElementById('customization-dialog');
    
    if (control && elem && dialog) {
        var elemRect = elem.getBoundingClientRect();
        var dialogRect = dialog.getBoundingClientRect();
        
        // 获取实际内容的尺寸（考虑 object-fit:fill）
        var img = elem.querySelector('img');
        var contentWidth = elemRect.width;
        var contentHeight = elemRect.height;
        
        // 计算位置（容器尺寸）
        var left = elemRect.left - dialogRect.left;
        var top = elemRect.top - dialogRect.top;
        
        // 如果有图片，等待图片加载完成后再调整位置
        if (img) {
            if (!img.complete || img.naturalWidth === 0) {
                // 图片未加载，等待加载完成
                var imgClone = img.cloneNode();
                imgClone.onload = function() {
                    elemData._pendingUpdate = false;
                    updateControl(elemData);
                };
                imgClone.onerror = function() {
                    elemData._pendingUpdate = false;
                };
                // 如果已经等待过了，不再等待
                if (!elemData._pendingUpdate) {
                    elemData._pendingUpdate = true;
                    return;
                }
            }
            // 图片已加载，直接使用 wrapper 尺寸（object-fit:fill 会填满 wrapper）
        }
        
        control.style.left = left + 'px';
        control.style.top = top + 'px';
        control.style.width = contentWidth + 'px';
        control.style.height = contentHeight + 'px';
        control.style.transform = 'rotate(' + (elemData.rotation || 0) + 'deg)';
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
        if (!currentCanvas.selectedElement || currentCanvas.selectedElement !== elemData) {
            return;
        }
        // Get parent element of current dragged element, not the canvas wrapper
        var pa = elemData.dom ? elemData.dom.parentElement : null;
        
        // If parentElement is null or not a print area, find it
        if (!pa || (!pa.classList.contains('print-area-inner') && !pa.id.startsWith('print-area-'))) {
            pa = document.querySelector('.print-area-inner, [id^="print-area-"]');
        }
        
        if (!pa) {
            return;
        }

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
                // Read file as base64
                var reader = new FileReader();
                reader.onload = function(event) {
                    var dataUrl = event.target.result;
                    
                    // Show loading state
                    var loadingDiv = document.createElement('div');
                    loadingDiv.className = 'cursor-pointer border-2 border-gray-300 rounded p-1 bg-gray-100';
                    loadingDiv.style.cssText = 'width: 100%; aspect-ratio: 1; object-fit: contain; display: flex; align-items: center; justify-content: center;';
                    loadingDiv.innerHTML = '<span class="text-gray-500">Uploading...</span>';
                    uploadedImagesDiv.appendChild(loadingDiv);
                    
                    // Upload to server
                    fetch('/customization/save-base64-image', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({ image: dataUrl })
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(result) {
                        loadingDiv.remove();
                        if (result.success && result.url) {
                            var imgUrl = result.url;
                            var div = document.createElement('div');
                            div.className = 'cursor-pointer border-2 border-gray-300 rounded p-1 hover:border-blue-500';
                            div.style.cssText = 'width: 100%; aspect-ratio: 1; object-fit: contain;';
                            div.innerHTML = '<img src="' + imgUrl + '" class="w-full h-full object-contain" />';
                            div.onclick = function() { addUploadedImage(imgUrl); };
                            uploadedImagesDiv.appendChild(div);

                        } else {

                            alert('Image upload failed. Please try again.');
                        }
                    })
                    .catch(function(err) {
                        loadingDiv.remove();

                        alert('Image upload failed. Please try again.');
                    });
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

function addTextToCanvas(text, size, color, font, opts) {
    var areaData = window.currentAreaData;
    var areaElemId = 'print-area-' + (areaData && (areaData.id || areaData.print_area_id) ? (areaData.id || areaData.print_area_id) : 'default');
    var printArea = document.getElementById(areaElemId);
    if (!printArea) return;
    
    var savedX = opts ? (opts.x !== undefined ? opts.x : null) : null;
    var savedY = opts ? (opts.y !== undefined ? opts.y : null) : null;
    var savedW = opts ? (opts.w !== undefined ? opts.w : (opts.width !== undefined ? opts.width : null)) : null;
    var savedH = opts ? (opts.h !== undefined ? opts.h : (opts.height !== undefined ? opts.height : null)) : null;
    var savedRotation = opts ? opts.rotation : null;
    var savedScaleX = opts ? opts.scaleX : null;
    var savedScaleY = opts ? opts.scaleY : null;
    var savedStyles = opts ? opts.styles : null;
    
    var fontSize = savedStyles && savedStyles.fontSize ? savedStyles.fontSize : (size || 24);
    var textColor = savedStyles && savedStyles.color ? savedStyles.color : (color || '#000');
    var fontFamily = savedStyles && savedStyles.fontFamily ? savedStyles.fontFamily : (font || 'Noto Sans TC, sans-serif');
    
    // Render text to canvas and convert to image
    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');
    canvas.width = 1024;
    canvas.height = 256;
    
    // Measure text to set proper canvas size
    ctx.font = fontSize + 'px ' + fontFamily;
    var metrics = ctx.measureText(text);
    var textWidth = Math.min(metrics.width + 20, 1024);
    var textHeight = fontSize * 1.5;
    canvas.width = textWidth;
    canvas.height = textHeight;
    
    // Redraw with correct size
    ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.font = fontSize + 'px ' + fontFamily;
    ctx.fillStyle = textColor;
    ctx.textBaseline = 'middle';
    ctx.textAlign = 'center';
    ctx.fillText(text, canvas.width / 2, canvas.height / 2);
    
    var dataUrl = canvas.toDataURL('image/png');
    
    // Calculate percentage dimensions based on text size vs print area
    var areaWidth = printArea.offsetWidth;
    var areaHeight = printArea.offsetHeight;
    var imgAspect = canvas.width / canvas.height;
    var areaAspect = areaWidth / areaHeight;
    
    var imgWidth, imgHeight;
    if (imgAspect > areaAspect) {
        imgWidth = 80;
        imgHeight = 80 / imgAspect;
    } else {
        imgHeight = 80;
        imgWidth = 80 * imgAspect;
    }
    
    // Use provided dimensions or calculate new ones
    var finalW = savedW !== null ? savedW : imgWidth;
    var finalH = savedH !== null ? savedH : imgHeight;
    var finalX = savedX !== null ? savedX : 10;
    var finalY = savedY !== null ? savedY : 10;
    var savedId = opts && opts.id !== undefined ? opts.id : Date.now();
    
    // Add as image element - pass individual params
    addUploadedImage(dataUrl, finalX, finalY, finalW, finalH, 
        savedRotation !== null ? savedRotation : 0,
        savedScaleX !== null ? savedScaleX : 1,
        savedScaleY !== null ? savedScaleY : 1,
        savedId
    );
    
    // Update the elemData with text styles
    var elemData = currentCanvas.elements[currentCanvas.elements.length - 1];
    if (elemData) {
        elemData.styles = elemData.styles || {};
        Object.assign(elemData.styles, {
            originalType: 'text',
            originalText: text,
            fontSize: fontSize,
            color: textColor,
            fontFamily: fontFamily
        });
    }
}
</script>
@endpushOnce
