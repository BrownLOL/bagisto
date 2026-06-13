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
                        console.log('[DEBUG checkDesignStatus] Saved design found:', currentUuid);
                        return;
                    }
                }
            } catch (e) {}
        }
    }
    
    statusIcon.classList.add('hidden');
    console.log('[DEBUG checkDesignStatus] No saved design');
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
                    console.log('[DEBUG checkPrintAreas] Product has print areas, showing button');
                } else {
                    // No print areas → hide button
                    btn.classList.add('hidden');
                    console.log('[DEBUG checkPrintAreas] Product has no print areas, hiding button');
                }
            })
            .catch(function(err) {
                console.error('[DEBUG checkPrintAreas] Error:', err);
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
        console.log('[DEBUG getDesignUUID] Using current design UUID:', uuid);
        return uuid;
    }
    
    // Fallback: generate new one
    uuid = generateDesignUUID();
    localStorage.setItem(currentKey, uuid);
    window.designUUID = uuid;
    console.log('[DEBUG getDesignUUID] Generated new UUID:', uuid);
    return uuid;
}

function openCustomizationDialog() {
    // Get or create UUID (persists during session via sessionStorage)
    window.designUUID = getDesignUUID();
    console.log('[DEBUG openCustomizationDialog] Design UUID:', window.designUUID);
    
    document.getElementById('customization-dialog').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    currentCanvas.elements = [];
    currentCanvas.selectedElement = null;
    updateOperationButtons();
    loadPrintAreas();
    initTextControls();
    resetZoom();
    initCanvasPan();
    console.log('Opening dialog, productId:', window.customizationProductId);
    // loadSavedCustomization is called by loadPrintAreas on success
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
    
    console.log('[DEBUG clearSavedDesign] Elements cleared from canvas');
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
    
    // Generate composed preview image with all layers using canvas
    var productId = window.customizationProductId;
    var previewImage = '';
    
    console.log('[DEBUG saveCustomization] Starting with elements count:', elements.length);
    console.log('[DEBUG saveCustomization] window.currentAreaData:', window.currentAreaData);
    
    (async function() {
        try {
            // Priority 1: Get original product image from currentAreaData (always the original, not the saved preview)
            var bgImageUrl = '';
            
            if (window.currentAreaData) {
                bgImageUrl = window.currentAreaData.image_url || window.currentAreaData.url || '';
                console.log('[DEBUG saveCustomization] Got bgImageUrl from currentAreaData:', bgImageUrl ? bgImageUrl.substring(0, 80) : 'empty');
            }
            
            // Priority 2: Try to get from product-bg background-image, but need to check if it's original or saved preview
            if (!bgImageUrl) {
                var productBg = document.querySelector('.product-bg');
                if (productBg) {
                    var bgStyle = productBg.style.backgroundImage;
                    if (bgStyle && bgStyle !== 'none') {
                        // Extract URL from background-image: url('...')
                        var urlMatch = bgStyle.match(/url\(["']?([^"']+)["']?\)/);
                        if (urlMatch && urlMatch[1]) {
                            bgImageUrl = urlMatch[1];
                            console.log('[DEBUG saveCustomization] Got bgImageUrl from product-bg:', bgImageUrl.substring(0, 80));
                        }
                    }
                }
            }
            
            // Priority 3: Get from product images list
            if (!bgImageUrl && window.productImages && window.productImages.length > 0) {
                bgImageUrl = window.productImages[0].url;
                console.log('[DEBUG saveCustomization] Got bgImageUrl from productImages list:', bgImageUrl.substring(0, 80));
            }
            
            if (!bgImageUrl) {
                console.error('[DEBUG saveCustomization] No background image found!');
                // Save without preview image
                saveCustomizationWithPreview('', elements);
                return;
            }
            
            console.log('[DEBUG saveCustomization] Loading background image...');
            
            // Helper function to check if URL is cross-origin
            function isCrossOrigin(url) {
                if (!url || url.startsWith('/') || url.startsWith('data:') || url.startsWith('blob:')) {
                    return false;
                }
                try {
                    var urlObj = new URL(url);
                    return urlObj.origin !== window.location.origin;
                } catch (e) {
                    return false;
                }
            }
            
            // Load background image to get dimensions
            var bgImg = new Image();
            if (isCrossOrigin(bgImageUrl)) {
                bgImg.crossOrigin = 'anonymous';
            }
            
            await new Promise(function(resolve, reject) {
                bgImg.onload = resolve;
                bgImg.onerror = function() {
                    console.error('[DEBUG saveCustomization] Failed to load background image:', bgImageUrl);
                    reject(new Error('Failed to load background image'));
                };
                bgImg.src = bgImageUrl;
            });
            
            console.log('saveCustomization: bgImg loaded, size:', bgImg.width, 'x', bgImg.height);
            
            // Create temporary canvas
            var tempCanvas = document.createElement('canvas');
            tempCanvas.width = bgImg.width;
            tempCanvas.height = bgImg.height;
            var ctx = tempCanvas.getContext('2d');
            
            // Draw background image
            ctx.drawImage(bgImg, 0, 0);
            
            // Get print area info for coordinate conversion
            var areaData = window.currentAreaData || {};
            var areaX = areaData.x ? parseFloat(areaData.x) : 0; // print area left percent
            var areaY = areaData.y ? parseFloat(areaData.y) : 0; // print area top percent
            var areaW = areaData.width ? parseFloat(areaData.width) : 100; // print area width percent
            var areaH = areaData.height ? parseFloat(areaData.height) : 100; // print area height percent
            
            console.log('saveCustomization: areaData:', areaX, areaY, areaW, areaH);
            
            // Convert element position to canvas pixels
            // Element position is relative to print area (in %)
            // Need to convert to absolute position on background image (in pixels)
            function getCanvasX(elemX) {
                // elemX is in percentage relative to print area
                // print area starts at areaX% on background image
                var absolutePercent = areaX + (elemX / 100) * areaW;
                return (absolutePercent / 100) * bgImg.width;
            }
            
            function getCanvasY(elemY) {
                var absolutePercent = areaY + (elemY / 100) * areaH;
                return (absolutePercent / 100) * bgImg.height;
            }
            
            // Draw each element (text or image)
            var drawPromises = [];
            
            for (var i = 0; i < elements.length; i++) {
                var elem = elements[i];
                
                if (elem.type === 'text') {
                    // Draw text element - position is relative to print area
                    var x = getCanvasX(elem.x);
                    var y = getCanvasY(elem.y);
                    var fontSize = elem.styles && elem.styles.fontSize ? elem.styles.fontSize : 24;
                    var color = elem.styles && elem.styles.color ? elem.styles.color : '#000000';
                    var fontFamily = elem.styles && elem.styles.fontFamily ? elem.styles.fontFamily : 'Arial';
                    var rotation = elem.rotation || 0;
                    
                    ctx.save();
                    ctx.translate(x, y);
                    ctx.rotate(rotation * Math.PI / 180);
                    ctx.font = fontSize + 'px ' + fontFamily;
                    ctx.fillStyle = color;
                    ctx.fillText(elem.content, 0, 0);
                    ctx.restore();
                    
                } else if (elem.type === 'image') {
                    // Draw uploaded image element
                    var elImg = new Image();
                    if (isCrossOrigin(elem.content)) {
                        elImg.crossOrigin = 'anonymous';
                    }
                    
                    // Capture elem in closure
                    var capturedElem = elem;
                    
                    var drawPromise = new Promise(function(resolve) {
                        elImg.onload = function() {
                            var x = getCanvasX(capturedElem.x);
                            var y = getCanvasY(capturedElem.y);
                            var rotation = capturedElem.rotation || 0;
                            var scaleX = capturedElem.scaleX || 1;
                            var scaleY = capturedElem.scaleY || 1;
                            var drawWidth = elImg.width * scaleX;
                            var drawHeight = elImg.height * scaleY;
                            
                            ctx.save();
                            ctx.translate(x, y);
                            ctx.rotate(rotation * Math.PI / 180);
                            ctx.drawImage(elImg, -drawWidth / 2, -drawHeight / 2, drawWidth, drawHeight);
                            ctx.restore();
                            
                            resolve();
                        };
                        elImg.onerror = function() {
                            console.log('[DEBUG saveCustomization] Failed to load image element:', capturedElem.content ? capturedElem.content.substring(0, 50) : 'no content');
                            resolve(); // Continue even if image fails
                        };
                        elImg.src = capturedElem.content;
                    });
                    
                    drawPromises.push(drawPromise);
                }
            }
            
            console.log('[DEBUG saveCustomization] Waiting for', drawPromises.length, 'image draws to complete');
            
            // Wait for all images to load and draw
            await Promise.all(drawPromises);
            
            console.log('[DEBUG saveCustomization] All images drawn, generating data URL');
            
            // Check if canvas is tainted (cross-origin issue)
            let isTainted = false;
            try {
                const testPixel = tempCtx.getImageData(0, 0, 1, 1);
            } catch (e) {
                isTainted = true;
                console.error('[DEBUG saveCustomization] Canvas is tainted (cross-origin images):', e.message);
            }
            
            if (isTainted) {
                console.error('[DEBUG saveCustomization] Cannot export tainted canvas - will use fallback');
                // Fallback: try to use just the background image
                previewImage = bgImageUrl;
                console.log('[DEBUG saveCustomization] Using background image as fallback');
            } else {
                // Get composed preview image
                previewImage = tempCanvas.toDataURL('image/png');
                console.log('[DEBUG saveCustomization] SUCCESS: composed preview generated, length:', previewImage.length);
            }
            
        } catch (e) {
            console.error('[DEBUG saveCustomization] ERROR: Failed to generate composed preview:', e.message || e);
            
            // Fallback: use background image
            previewImage = bgImageUrl;
            console.log('[DEBUG saveCustomization] Using background image as fallback');
        }
        
        // Continue with saving after preview image is ready
        console.log('[DEBUG saveCustomization] Calling saveCustomizationWithPreview, previewImage length:', previewImage ? previewImage.length : 0);
        saveCustomizationWithPreview(previewImage, elements);
    })();
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
            console.log('[DEBUG] Preview image uploaded:', result.url);
            return result.url;
        }
    } catch (error) {
        console.error('[DEBUG] Preview upload failed:', error);
    }
    
    return previewImage; // 上传失败，返回原始 base64
}

// 保存设计（异步，上传预览图后保存）
async function saveCustomizationWithPreview(previewImage, elements) {
    var productId = window.customizationProductId;
    
    console.log('[DEBUG saveCustomizationWithPreview] Starting with previewImage length:', previewImage ? previewImage.length : 0);
    console.log('[DEBUG saveCustomizationWithPreview] elements count:', elements.length);
    
    // 先上传预览图获取 URL
    var previewImageUrl = await uploadPreviewImage(previewImage);
    console.log('[DEBUG saveCustomizationWithPreview] previewImageUrl:', previewImageUrl);
    
    // Prepare customization data for current print area
    var uuid = getDesignUUID(); // Ensure we have a UUID
    
    console.log('[DEBUG saveCustomizationWithPreview] UUID:', uuid);
    
    // Get current print area record key
    var currentRecordKey = currentCanvas.currentImageKey || 'default';
    var printAreaId = window.currentAreaData?.id || window.currentAreaData?.print_area_id;
    
    console.log('[DEBUG saveCustomizationWithPreview] currentRecordKey:', currentRecordKey, 'printAreaId:', printAreaId);
    
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
        
        var recordData = {
            print_area_id: parseInt(printAreaId),
            record_key: currentRecordKey,
            image_url: window.currentAreaData?.image_url || window.currentAreaData?.url || '',
            preview_image: previewImageUrl, // 保存预览图 URL（上传后）
            elements: elements
        };
        
        console.log('[DEBUG saveCustomizationWithPreview] recordData elements count:', elements.length);
        
        if (existingIndex >= 0) {
            printAreas[existingIndex] = recordData;
        } else {
            printAreas.push(recordData);
        }
        
        // 保存完整数据到 localStorage（包含 preview_image URL）
        var designDataForStorage = {
            product_id: productId,
            print_areas: printAreas,
            updated_at: new Date().toISOString()
        };
        
        localStorage.setItem(designKey, JSON.stringify(designDataForStorage));
        
        console.log('[DEBUG saveCustomizationWithPreview] Saved to localStorage, key:', designKey);
        console.log('[DEBUG saveCustomizationWithPreview] preview_image URL length:', printAreas[0]?.preview_image?.length || 0);
        
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
        console.log('[DEBUG loadSavedCustomization] No design UUID, skipping load');
        return;
    }
    
    var key = 'design_' + uuid;
    console.log('[DEBUG loadSavedCustomization] Start, key:', key);
    
    var saved = localStorage.getItem(key);
    if (!saved) {
        console.log('[DEBUG loadSavedCustomization] No saved data');
        return;
    }
    
    var savedData;
    try {
        savedData = JSON.parse(saved);
        console.log('[DEBUG loadSavedCustomization] Parsed:', savedData);
    } catch(e) {
        console.error('[DEBUG loadSavedCustomization] Parse error:', e);
        return;
    }
    
    // Get the print_areas array
    var printAreas = savedData.print_areas || [];
    
    if (!printAreas || printAreas.length === 0) {
        console.log('[DEBUG loadSavedCustomization] No print areas to restore');
        return;
    }
    
    // Restore all print area data to layerStore
    printAreas.forEach(function(pa) {
        if (pa.elements && pa.elements.length > 0) {
            var recordKey = pa.record_key || 'default';
            currentCanvas.layerStore[recordKey] = pa.elements;
            console.log('[DEBUG loadSavedCustomization] Restored record:', recordKey, 'with', pa.elements.length, 'elements');
        }
    });
    
    // Check if current view has saved elements
    var currentRecordKey = currentCanvas.currentImageKey;
    if (currentRecordKey && currentCanvas.layerStore[currentRecordKey]) {
        var elements = currentCanvas.layerStore[currentRecordKey];
        
        // Don't try to load preview_image - it's no longer stored in localStorage
        // Background image is already set from window.currentAreaData.image_url
        
        // Update currentCanvas.elements to match layerStore (important for other functions)
        currentCanvas.elements = elements.slice();
        
        // Wait for content to be ready, then restore elements
        setTimeout(function() {
            // Get canvas dimensions for debugging
            var wrapper = document.querySelector('.canvas-wrapper');
            var printArea = document.querySelector('.print-area-inner');
            var wrapperRect = wrapper ? wrapper.getBoundingClientRect() : null;
            var printAreaRect = printArea ? printArea.getBoundingClientRect() : null;
            
            console.log('[DEBUG loadSavedCustomization] Canvas dims - wrapper:', wrapperRect ? wrapperRect.width + 'x' + wrapperRect.height : 'null', 
                        'printArea:', printAreaRect ? printAreaRect.width + 'x' + printAreaRect.height : 'null');
            console.log('[DEBUG loadSavedCustomization] areaData:', JSON.stringify(window.currentAreaData));
            
            elements.forEach(function(elem, idx) {
                console.log('[DEBUG loadSavedCustomization] Element', idx, ':', elem.type, 'at', elem.x + '%,', elem.y + '%');
                
                // Directly create DOM element instead of calling addUploadedImage/addTextToCanvas
                // because selectProductImage already creates them from currentCanvas.elements
                var areaData = window.currentAreaData;
                var printAreaId = 'print-area-' + (areaData && (areaData.id || areaData.print_area_id) ? (areaData.id || areaData.print_area_id) : 'default');
                var printArea = document.getElementById(printAreaId);
                if (!printArea) return;
                
                var wrapper = document.createElement('div');
                wrapper.className = 'canvas-elem';
                var rotation = elem.rotation || 0;
                var scaleX = elem.scaleX || 1;
                var scaleY = elem.scaleY || 1;
                wrapper.style.cssText = 'position:absolute;left:' + elem.x + '%;top:' + elem.y + '%;width:' + (elem.width || elem.w || 80) + '%;height:' + (elem.height || elem.h || 80) + '%;cursor:move;transform-origin:center center;transform:rotate(' + rotation + 'deg) scaleX(' + scaleX + ') scaleY(' + scaleY + ');';
                
                var elemData = {
                    dom: wrapper,
                    type: elem.type,
                    content: elem.content,
                    x: elem.x,
                    y: elem.y,
                    width: elem.width || elem.w,
                    height: elem.height || elem.h,
                    rotation: rotation,
                    scaleX: scaleX,
                    scaleY: scaleY,
                    styles: elem.styles || {},
                    id: Date.now() + idx
                };
                
                if (elem.type === 'text') {
                    var textSpan = document.createElement('span');
                    textSpan.textContent = elem.content;
                    textSpan.style.cssText = 'width:100%;height:100%;display:flex;align-items:center;justify-content:center;overflow:hidden;';
                    if (elem.styles) {
                        textSpan.style.fontSize = elem.styles.fontSize || '24px';
                        textSpan.style.color = elem.styles.color || '#000';
                        textSpan.style.fontFamily = elem.styles.fontFamily || 'Arial';
                    }
                    wrapper.appendChild(textSpan);
                } else if (elem.type === 'image') {
                    var img = document.createElement('img');
                    img.src = elem.content;
                    img.style.cssText = 'width:100%;height:100%;object-fit:fill;pointer-events:none;';
                    wrapper.appendChild(img);
                }
                
                wrapper._elemData = elemData;
                setupElemEvents(wrapper, elemData);
                printArea.appendChild(wrapper);
                // Don't push to currentCanvas.elements - selectProductImage already did that
            });
            
            console.log('[DEBUG loadSavedCustomization] Restored', elements.length, 'elements');
            // Update preview after elements are created
            updatePreview();
        }, 200);
    }
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
                    var recordKey = item.id || item.record_id || imgUrl;
                    var div = document.createElement('div');
                    div.className = 'product-image-item cursor-pointer border-2 border-gray-300 rounded p-1 hover:border-blue-500';
                    div.setAttribute('data-record-key', recordKey);
                    div.style.cssText = 'width: 100%; aspect-ratio: 1; object-fit: contain;';
                    div.innerHTML = '<img src="' + imgUrl + '" class="w-full h-full object-contain" />';
                    div.onclick = function() { selectProductImage(imgUrl, item); };
                    productImagesDiv.appendChild(div);
                });
                
                selectProductImage(data.data[0].image_url || data.data[0].url, data.data[0]);
                loadSavedCustomization(); // 恢复保存的设计
            }
        });
}

function selectProductImage(imgUrl, areaData) {
    // Save current area data
    window.currentAreaData = areaData;

    var canvas = document.getElementById('design-canvas-inner');

    // 用记录ID作为key，而不是图片URL（同一图片可能有多个PrintArea记录）
    var recordKey = areaData.id || areaData.record_id || imgUrl;

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
    bgImg.style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;object-fit:contain;pointer-events:none;';
    canvas.appendChild(bgImg);

    if (areaData && areaData.x !== undefined) {
        var printAreaId = 'print-area-' + (areaData.id || areaData.print_area_id || 'default');
        var printArea = document.createElement('div');
        printArea.id = printAreaId;
        printArea.style.cssText = 'position:absolute;left:' + areaData.x + '%;top:' + areaData.y + '%;width:' + areaData.width + '%;height:' + areaData.height + '%;border:2px dashed red;background:rgba(255,255,255,0.3);overflow:hidden;';
        canvas.appendChild(printArea);
        
        // DON'T create elements here - loadSavedCustomization will do it after checking localStorage
        // This prevents duplicate elements when reloading a saved design
        console.log('[DEBUG selectProductImage] Skip element creation, loadSavedCustomization will handle it');
    }
    
    updateLayersList();
    updateOperationButtons();
    updatePreview();
}

function addUploadedImage(dataUrl, x, y, w, h, rotation, scaleX, scaleY) {
    x = x !== undefined ? x : 10;
    y = y !== undefined ? y : 10;
    w = w !== undefined ? w : 80;
    h = h !== undefined ? h : 80;
    rotation = rotation !== undefined ? rotation : 0;
    scaleX = scaleX !== undefined ? scaleX : 1;
    scaleY = scaleY !== undefined ? scaleY : 1;
    
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
        id: Date.now()
    };
    wrapper._elemData = elemData;
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
                            console.log('[DEBUG image upload] Success:', imgUrl);
                        } else {
                            console.error('[DEBUG image upload] Failed:', result);
                            alert('Image upload failed. Please try again.');
                        }
                    })
                    .catch(function(err) {
                        loadingDiv.remove();
                        console.error('[DEBUG image upload] Error:', err);
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
    
    // Add as image element
    addUploadedImage(dataUrl, finalX, finalY, finalW, finalH, {
        rotation: savedRotation !== null ? savedRotation : 0,
        scaleX: savedScaleX !== null ? savedScaleX : 1,
        scaleY: savedScaleY !== null ? savedScaleY : 1,
        styles: {
            ...savedStyles,
            originalType: 'text',
            originalText: text,
            fontSize: fontSize,
            color: textColor,
            fontFamily: fontFamily
        }
    });
}
</script>
@endpushOnce
