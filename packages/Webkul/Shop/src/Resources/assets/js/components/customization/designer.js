// Customization Designer JavaScript

// State
let state = {
    currentTab: 'product',
    selectedElement: null,
    elements: [],
    canvasScale: 1,
    canvasImage: null,
    printArea: null,
    productId: window.productId,
    printAreas: window.printAreas || [],
    productImage: window.productImage,
    baseUrl: window.baseUrl
};

// Initialize
function initDesigner() {
    // Load product images
    loadProductImages();
    
    // Set product image if available
    if (state.productImage) {
        const img = document.getElementById('canvas-product-image');
        img.src = state.productImage.url || state.productImage;
        
        // Set print area if available
        if (state.printAreas.length > 0) {
            const area = state.printAreas[0];
            state.printArea = area;
            updatePrintAreaIndicator(area);
        }
    }
}

// Tab switching
function switchTab(tab) {
    state.currentTab = tab;
    
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        if (btn.dataset.tab === tab) {
            btn.classList.add('border-blue-500', 'text-blue-500');
            btn.classList.remove('border-transparent', 'text-gray-500');
        } else {
            btn.classList.remove('border-blue-500', 'text-blue-500');
            btn.classList.add('border-transparent', 'text-gray-500');
        }
    });
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    document.getElementById('tab-' + tab).classList.remove('hidden');
}

// Load product images with print areas
async function loadProductImages() {
    try {
        const response = await fetch(`/api/product/${state.productId}/print-areas`);
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            state.printAreas = result.data;
            
            // Display images
            const container = document.getElementById('product-images');
            container.innerHTML = '';
            
            // Get unique images
            const uniqueImages = {};
            result.data.forEach(area => {
                if (area.image_url && !uniqueImages[area.image_url]) {
                    uniqueImages[area.image_url] = area;
                }
            });
            
            Object.values(uniqueImages).forEach(area => {
                const div = document.createElement('div');
                div.className = 'aspect-square bg-gray-100 rounded overflow-hidden cursor-pointer hover:ring-2 hover:ring-blue-500';
                div.innerHTML = `<img src="${area.image_url}" class="w-full h-full object-cover">`;
                div.onclick = () => selectProductImage(area);
                container.appendChild(div);
            });
            
            // Auto-select first image
            if (Object.keys(uniqueImages).length > 0) {
                selectProductImage(Object.values(uniqueImages)[0]);
            }
        }
    } catch (error) {
        console.error('Error loading product images:', error);
    }
}

// Select product image
function selectProductImage(area) {
    state.productImage = area;
    state.printArea = area;
    
    const img = document.getElementById('canvas-product-image');
    img.src = area.image_url;
    
    updatePrintAreaIndicator(area);
}

// Update print area indicator
function updatePrintAreaIndicator(area) {
    const indicator = document.getElementById('print-area-indicator');
    
    if (area) {
        indicator.style.left = area.x + '%';
        indicator.style.top = area.y + '%';
        indicator.style.width = area.width + '%';
        indicator.style.height = area.height + '%';
        indicator.classList.remove('hidden');
    } else {
        indicator.classList.add('hidden');
    }
}

// Handle image upload
async function handleImageUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const formData = new FormData();
        formData.append('image', file);
        
        try {
            // Upload to server
            const uploadResponse = await fetch('/api/customization/upload-preview', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });
            
            const uploadResult = await uploadResponse.json();
            
            if (uploadResult.success) {
                addImageToCanvas(uploadResult.url);
                
                // Also add to uploaded images list
                const container = document.getElementById('uploaded-images');
                const div = document.createElement('div');
                div.className = 'aspect-square bg-gray-100 rounded overflow-hidden cursor-pointer hover:ring-2 hover:ring-blue-500';
                div.innerHTML = `<img src="${uploadResult.url}" class="w-full h-full object-cover">`;
                div.onclick = () => addImageToCanvas(uploadResult.url);
                container.appendChild(div);
            }
        } catch (error) {
            console.error('Error uploading image:', error);
            // Use local preview as fallback
            const reader = new FileReader();
            reader.onload = (e) => {
                addImageToCanvas(e.target.result);
            };
            reader.readAsDataURL(file);
        }
        
        input.value = '';
    }
}

// Add image to canvas
function addImageToCanvas(src) {
    const element = createCustomElement('image', src);
    state.elements.push(element);
    renderElement(element);
    selectElement(element);
}

// Add text element
function addTextElement() {
    const content = document.getElementById('text-content').value || 'Text';
    const size = document.getElementById('text-size').value || 24;
    const color = document.getElementById('text-color').value || '#000000';
    const font = document.getElementById('text-font').value || 'Arial';
    
    const element = createCustomElement('text', content, {
        fontSize: size,
        color: color,
        fontFamily: font
    });
    
    state.elements.push(element);
    renderElement(element);
    selectElement(element);
}

// Create custom element
function createCustomElement(type, content, options = {}) {
    const id = 'el-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    
    return {
        id,
        type,
        content,
        x: 50,
        y: 50,
        width: type === 'text' ? 100 : 80,
        height: type === 'text' ? 40 : 60,
        rotation: 0,
        scaleX: 1,
        scaleY: 1,
        zIndex: state.elements.length + 1,
        ...options
    };
}

// Render element on canvas
function renderElement(element) {
    let el = document.getElementById(element.id);
    
    if (!el) {
        el = document.createElement('div');
        el.id = element.id;
        el.className = 'custom-element absolute cursor-move';
        el.style.pointerEvents = 'auto';
        
        // Add event listeners
        el.addEventListener('mousedown', (e) => startDrag(e, element));
        el.addEventListener('click', () => selectElement(element));
        
        document.getElementById('custom-elements').appendChild(el);
    }
    
    // Set styles
    el.style.left = element.x + '%';
    el.style.top = element.y + '%';
    el.style.width = element.width + 'px';
    el.style.height = element.height + 'px';
    el.style.transform = `rotate(${element.rotation}deg) scaleX(${element.scaleX}) scaleY(${element.scaleY})`;
    el.style.zIndex = element.zIndex;
    
    // Set content
    if (element.type === 'image') {
        el.innerHTML = `<img src="${element.content}" class="w-full h-full object-contain pointer-events-none" draggable="false">`;
    } else if (element.type === 'text') {
        el.innerHTML = `<span class="pointer-events-none" style="font-size: ${element.fontSize}px; color: ${element.color}; font-family: ${element.fontFamily}; white-space: nowrap;">${element.content}</span>`;
    }
}

// Select element
function selectElement(element) {
    state.selectedElement = element;
    
    // Update UI
    document.getElementById('element-actions').classList.remove('hidden');
    document.getElementById('no-selection').classList.add('hidden');
    
    // Highlight selected element
    document.querySelectorAll('.custom-element').forEach(el => {
        el.classList.remove('ring-2', 'ring-blue-500');
    });
    document.getElementById(element.id).classList.add('ring-2', 'ring-blue-500');
    
    // Update preview
    updatePreview();
}

// Deselect element
function deselectElement() {
    state.selectedElement = null;
    document.getElementById('element-actions').classList.add('hidden');
    document.getElementById('no-selection').classList.remove('hidden');
    
    document.querySelectorAll('.custom-element').forEach(el => {
        el.classList.remove('ring-2', 'ring-blue-500');
    });
}

// Drag functionality
let dragState = {
    isDragging: false,
    element: null,
    startX: 0,
    startY: 0,
    startLeft: 0,
    startTop: 0
};

function startDrag(e, element) {
    e.preventDefault();
    e.stopPropagation();
    
    dragState.isDragging = true;
    dragState.element = element;
    dragState.startX = e.clientX;
    dragState.startY = e.clientY;
    dragState.startLeft = element.x;
    dragState.startTop = element.y;
    
    selectElement(element);
    
    document.addEventListener('mousemove', doDrag);
    document.addEventListener('mouseup', stopDrag);
}

function doDrag(e) {
    if (!dragState.isDragging) return;
    
    const canvas = document.getElementById('design-canvas');
    const rect = canvas.getBoundingClientRect();
    
    const deltaX = (e.clientX - dragState.startX) / rect.width * 100;
    const deltaY = (e.clientY - dragState.startY) / rect.height * 100;
    
    dragState.element.x = Math.max(0, Math.min(100, dragState.startLeft + deltaX));
    dragState.element.y = Math.max(0, Math.min(100, dragState.startTop + deltaY));
    
    renderElement(dragState.element);
    updatePreview();
}

function stopDrag() {
    dragState.isDragging = false;
    document.removeEventListener('mousemove', doDrag);
    document.removeEventListener('mouseup', stopDrag);
}

// Element actions
function maximizeElement() {
    if (!state.selectedElement) return;
    
    const element = state.selectedElement;
    
    if (state.printArea) {
        element.x = state.printArea.x;
        element.y = state.printArea.y;
        element.width = state.printArea.width * 3; // Convert percentage to approximate pixels
        element.height = state.printArea.height * 3;
    } else {
        element.x = 0;
        element.y = 0;
        element.width = 300;
        element.height = 300;
    }
    
    renderElement(element);
    updatePreview();
}

function flipHorizontal() {
    if (!state.selectedElement) return;
    state.selectedElement.scaleX *= -1;
    renderElement(state.selectedElement);
    updatePreview();
}

function flipVertical() {
    if (!state.selectedElement) return;
    state.selectedElement.scaleY *= -1;
    renderElement(state.selectedElement);
    updatePreview();
}

function rotateLeft() {
    if (!state.selectedElement) return;
    state.selectedElement.rotation -= 15;
    renderElement(state.selectedElement);
    updatePreview();
}

function rotateRight() {
    if (!state.selectedElement) return;
    state.selectedElement.rotation += 15;
    renderElement(state.selectedElement);
    updatePreview();
}

function bringToFront() {
    if (!state.selectedElement) return;
    const maxZ = Math.max(...state.elements.map(e => e.zIndex));
    state.selectedElement.zIndex = maxZ + 1;
    renderElement(state.selectedElement);
    updatePreview();
}

function sendToBack() {
    if (!state.selectedElement) return;
    const minZ = Math.min(...state.elements.map(e => e.zIndex));
    state.selectedElement.zIndex = minZ - 1;
    renderElement(state.selectedElement);
    updatePreview();
}

function deleteElement() {
    if (!state.selectedElement) return;
    
    const el = document.getElementById(state.selectedElement.id);
    if (el) el.remove();
    
    state.elements = state.elements.filter(e => e.id !== state.selectedElement.id);
    state.selectedElement = null;
    deselectElement();
    updatePreview();
}

// Zoom controls
function zoomIn() {
    state.canvasScale = Math.min(2, state.canvasScale + 0.1);
    updateCanvasZoom();
}

function zoomOut() {
    state.canvasScale = Math.max(0.5, state.canvasScale - 0.1);
    updateCanvasZoom();
}

function updateCanvasZoom() {
    const canvas = document.getElementById('design-canvas');
    canvas.style.transform = `scale(${state.canvasScale})`;
    document.getElementById('zoom-level').textContent = Math.round(state.canvasScale * 100) + '%';
}

// Update preview
function updatePreview() {
    const canvas = document.getElementById('preview-canvas');
    const ctx = canvas.getContext('2d');
    
    // Set canvas size
    canvas.width = 200;
    canvas.height = 200;
    
    // Clear
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Draw product image
    const img = document.getElementById('canvas-product-image');
    if (img.src) {
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    }
    
    // Draw elements (simplified)
    state.elements.forEach(element => {
        const x = (element.x / 100) * canvas.width;
        const y = (element.y / 100) * canvas.height;
        const w = (element.width / 300) * canvas.width;
        const h = (element.height / 300) * canvas.height;
        
        ctx.save();
        ctx.translate(x + w/2, y + h/2);
        ctx.rotate(element.rotation * Math.PI / 180);
        ctx.scale(element.scaleX, element.scaleY);
        
        if (element.type === 'image') {
            const imgEl = new Image();
            imgEl.src = element.content;
            if (imgEl.complete) {
                ctx.drawImage(imgEl, -w/2, -h/2, w, h);
            }
        } else if (element.type === 'text') {
            ctx.font = `${element.fontSize / 2}px ${element.fontFamily}`;
            ctx.fillStyle = element.color;
            ctx.fillText(element.content, -w/2, h/4);
        }
        
        ctx.restore();
    });
}

// Generate preview image from all elements
function generatePreviewImage() {
    return new Promise((resolve) => {
        const canvas = document.getElementById('design-canvas');
        if (!canvas) {
            resolve('');
            return;
        }
        
        // Create a temporary canvas for preview
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;
        const tempCtx = tempCanvas.getContext('2d');
        
        // Draw background image if available
        const bgImg = document.getElementById('canvas-product-image');
        if (bgImg && bgImg.src) {
            tempCtx.drawImage(bgImg, 0, 0, tempCanvas.width, tempCanvas.height);
        } else {
            tempCtx.fillStyle = '#f3f4f6';
            tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        }
        
        // Draw all elements
        const drawPromises = state.elements.map(element => {
            return new Promise((res) => {
                const x = (element.x / 100) * tempCanvas.width;
                const y = (element.y / 100) * tempCanvas.height;
                const w = ((element.width || 100) / 100) * tempCanvas.width;
                const h = ((element.height || 100) / 100) * tempCanvas.height;
                
                if (element.type === 'image') {
                    const imgEl = new Image();
                    imgEl.crossOrigin = 'anonymous';
                    imgEl.onload = () => {
                        if (element.rotation) {
                            tempCtx.save();
                            tempCtx.translate(x + w/2, y + h/2);
                            tempCtx.rotate(element.rotation * Math.PI / 180);
                            tempCtx.drawImage(imgEl, -w/2, -h/2, w, h);
                            tempCtx.restore();
                        } else {
                            tempCtx.drawImage(imgEl, x, y, w, h);
                        }
                        res();
                    };
                    imgEl.onerror = () => res();
                    imgEl.src = element.content;
                } else if (element.type === 'text') {
                    const fontSize = (element.fontSize || 24) * (tempCanvas.width / 400);
                    const fontFamily = element.fontFamily || 'Arial';
                    const color = element.color || '#000000';
                    
                    tempCtx.font = `${fontSize}px ${fontFamily}`;
                    tempCtx.fillStyle = color;
                    tempCtx.textBaseline = 'top';
                    
                    const words = element.content.split(' ');
                    let line = '';
                    let lineY = y;
                    const maxWidth = w;
                    
                    words.forEach(word => {
                        const testLine = line + word + ' ';
                        const metrics = tempCtx.measureText(testLine);
                        if (metrics.width > maxWidth && line !== '') {
                            tempCtx.fillText(line, x, lineY);
                            line = word + ' ';
                            lineY += fontSize * 1.2;
                        } else {
                            line = testLine;
                        }
                    });
                    tempCtx.fillText(line, x, lineY);
                    res();
                } else {
                    res();
                }
            });
        });
        
        Promise.all(drawPromises).then(() => {
            resolve(tempCanvas.toDataURL('image/png'));
        }).catch(() => {
            resolve('');
        });
    });
}

// Save design
async function saveDesign() {
    let imageUrl = state.printArea?.image_url || '';

    // 如果是 blob 或 data URL，先上传获取真实 URL
    if (imageUrl && (imageUrl.startsWith('blob:') || imageUrl.startsWith('data:'))) {
        try {
            const formData = new FormData();
            const blob = await fetch(imageUrl).then(r => r.blob());
            formData.append('image', blob, 'design-image.png');

            const uploadResponse = await fetch('/api/customization/upload-preview', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const uploadResult = await uploadResponse.json();
            if (uploadResult.success) {
                imageUrl = uploadResult.url;
            }
        } catch (error) {
            console.error('Error uploading image:', error);
        }
    }

    // Generate preview image
    const previewImage = await generatePreviewImage();

    const designData = {
        product_id: state.productId,
        print_area_id: state.printArea?.id,
        image_url: imageUrl,
        preview_image: previewImage,
        elements: state.elements.map(e => ({
            type: e.type,
            content: e.content,
            x: e.x,
            y: e.y,
            width: e.width,
            height: e.height,
            rotation: e.rotation,
            scaleX: e.scaleX,
            scaleY: e.scaleY,
            zIndex: e.zIndex,
            fontSize: e.fontSize,
            color: e.color,
            fontFamily: e.fontFamily
        }))
    };
    
    try {
        // Add to cart
        const response = await fetch('/api/cart/add-customization', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(designData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Design saved! Added to cart.');
            window.location.href = '/checkout';
        } else {
            alert('Error: ' + (result.message || 'Failed to save design'));
        }
    } catch (error) {
        console.error('Error saving design:', error);
        alert('Failed to save design');
    }
}

// Cancel design
function cancelDesign() {
    if (confirm('Are you sure you want to cancel?')) {
        window.history.back();
    }
}

// Click outside to deselect
document.addEventListener('click', (e) => {
    if (!e.target.closest('.custom-element') && !e.target.closest('#element-actions')) {
        deselectElement();
    }
});
