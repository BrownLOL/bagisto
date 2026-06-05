<div class="customization-areas-wrapper">
    <div class="section">
        <div class="sectitle">
            <span>{{ __('admin::app.catalog.products.customization-areas.title') }}</span>
        </div>

        <div class="section-content">
            <div class="customization-instructions">
                <p>{{ __('admin::app.catalog.products.customization-areas.instructions') }}</p>
            </div>

            <!-- Image Selection -->
            <div class="image-selector mb-4">
                <label>{{ __('admin::app.catalog.products.customization-areas.select-image') }}</label>
                <select id="customization-image-select" class="control">
                    <option value="">{{ __('admin::app.catalog.products.customization-areas.choose-image') }}</option>
                    @foreach ($product->images as $image)
                        <option value="{{ $image->id }}" data-url="{{ url('storage/' . $image->path) }}">
                            {{ $image->id }} - {{ $image->path }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Image with Print Area Overlay -->
            <div id="print-area-container" class="print-area-container" style="display: none; position: relative;">
                <div class="image-wrapper" style="position: relative; display: inline-block;">
                    <img id="customization-image" src="" alt="Product Image" style="max-width: 100%;">
                    
                    <!-- Print area overlays -->
                    <div id="print-area-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></div>
                    
                    <!-- SVG for drawing -->
                    <svg id="print-area-svg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: all;"></svg>
                </div>
                
                <!-- Drawing rectangle indicator -->
                <div id="drawing-rect" style="position: absolute; border: 2px dashed #0068e1; background: rgba(0, 104, 225, 0.1); pointer-events: none; display: none;"></div>
            </div>

            <!-- No Images Message -->
            @if ($product->images->isEmpty())
                <div class="no-images-message">
                    <p>{{ __('admin::app.catalog.products.customization-areas.no-images') }}</p>
                </div>
            @endif

            <!-- Print Areas List -->
            <div id="print-areas-list" class="print-areas-list mt-4">
                <h4>{{ __('admin::app.catalog.products.customization-areas.defined-areas') }}</h4>
                <div id="areas-container"></div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons mt-4">
                <button type="button" id="start-draw-btn" class="btn btn-secondary" disabled>
                    {{ __('admin::app.catalog.products.customization-areas.set-print-area') }}
                </button>
                <button type="button" id="save-areas-btn" class="btn btn-primary" disabled>
                    {{ __('admin::app.catalog.products.customization-areas.save-areas') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.customization-areas-wrapper {
    padding: 20px;
}

.customization-areas-wrapper .section-content {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 4px;
}

.print-area-overlay {
    border: 2px dashed #0068e1;
    background: rgba(0, 104, 225, 0.1);
    box-sizing: border-box;
}

.print-area-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    margin: 5px 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.print-area-item .area-info {
    font-size: 14px;
}

.print-area-item .delete-btn {
    color: #f14d54;
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageSelect = document.getElementById('customization-image-select');
    const container = document.getElementById('print-area-container');
    const image = document.getElementById('customization-image');
    const svg = document.getElementById('print-area-svg');
    const startDrawBtn = document.getElementById('start-draw-btn');
    const saveAreasBtn = document.getElementById('save-areas-btn');
    const areasContainer = document.getElementById('areas-container');
    
    let currentImageId = null;
    let printAreas = [];
    let isDrawing = false;
    let startX, startY;
    let currentRect = null;
    let isDragging = false;
    let dragTarget = null;

    // Load image on selection
    imageSelect.addEventListener('change', async function() {
        const option = this.options[this.selectedIndex];
        if (!option.value) {
            container.style.display = 'none';
            startDrawBtn.disabled = true;
            saveAreasBtn.disabled = true;
            return;
        }

        currentImageId = option.value;
        const imageUrl = option.dataset.url;
        
        image.src = imageUrl;
        container.style.display = 'block';
        startDrawBtn.disabled = false;
        
        // Load existing print areas
        await loadPrintAreas(currentImageId);
    });

    // Load existing print areas
    async function loadPrintAreas(imageId) {
        try {
            const response = await fetch(`/customization/print-areas/{{ $product->id }}`);
            const result = await response.json();
            
            if (result.success) {
                printAreas = result.data.filter(area => area.image_id == imageId);
                renderPrintAreas();
            }
        } catch (error) {
            console.error('Error loading print areas:', error);
        }
    }

    // Start drawing mode
    startDrawBtn.addEventListener('click', function() {
        if (!currentImageId) return;
        isDrawing = true;
        startDrawBtn.textContent = '{{ __("admin::app.catalog.products.customization-areas.drawing") }}';
        startDrawBtn.disabled = true;
        svg.style.cursor = 'crosshair';
    });

    // Mouse down - start drawing
    svg.addEventListener('mousedown', function(e) {
        if (!isDrawing) return;
        
        const rect = svg.getBoundingClientRect();
        startX = ((e.clientX - rect.left) / rect.width) * 100;
        startY = ((e.clientY - rect.top) / rect.height) * 100;
        
        currentRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        currentRect.setAttribute('stroke', '#0068e1');
        currentRect.setAttribute('stroke-width', '2');
        currentRect.setAttribute('stroke-dasharray', '5,5');
        currentRect.setAttribute('fill', 'rgba(0, 104, 225, 0.1)');
        currentRect.style.pointerEvents = 'all';
        currentRect.style.cursor = 'move';
        svg.appendChild(currentRect);
    });

    // Mouse move - update rectangle
    svg.addEventListener('mousemove', function(e) {
        if (!isDrawing || !currentRect) return;
        
        const rect = svg.getBoundingClientRect();
        const currentX = ((e.clientX - rect.left) / rect.width) * 100;
        const currentY = ((e.clientY - rect.top) / rect.height) * 100;
        
        const x = Math.min(startX, currentX);
        const y = Math.min(startY, currentY);
        const width = Math.abs(currentX - startX);
        const height = Math.abs(currentY - startY);
        
        currentRect.setAttribute('x', x + '%');
        currentRect.setAttribute('y', y + '%');
        currentRect.setAttribute('width', width + '%');
        currentRect.setAttribute('height', height + '%');
    });

    // Mouse up - finish drawing
    svg.addEventListener('mouseup', function(e) {
        if (!isDrawing || !currentRect) return;
        
        const rect = svg.getBoundingClientRect();
        const endX = ((e.clientX - rect.left) / rect.width) * 100;
        const endY = ((e.clientY - rect.top) / rect.height) * 100;
        
        const x = Math.min(startX, endX);
        const y = Math.min(startY, endY);
        const width = Math.abs(endX - startX);
        const height = Math.abs(endY - startY);
        
        // Only add if area is big enough
        if (width > 5 && height > 5) {
            const areaId = Date.now();
            printAreas.push({
                id: areaId,
                x: x,
                y: y,
                width: width,
                height: height,
                name: 'Area ' + (printAreas.length + 1)
            });
            renderPrintAreas();
        }
        
        // Remove temporary rect and reset
        if (currentRect && currentRect.parentNode) {
            currentRect.parentNode.removeChild(currentRect);
        }
        currentRect = null;
        isDrawing = false;
        startDrawBtn.textContent = '{{ __("admin::app.catalog.products.customization-areas.set-print-area") }}';
        startDrawBtn.disabled = false;
        svg.style.cursor = 'default';
        
        // Enable save button
        saveAreasBtn.disabled = printAreas.length === 0;
    });

    // Render print areas
    function renderPrintAreas() {
        // Clear SVG
        svg.innerHTML = '';
        
        // Render rectangles
        printAreas.forEach((area, index) => {
            const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rect.setAttribute('x', area.x + '%');
            rect.setAttribute('y', area.y + '%');
            rect.setAttribute('width', area.width + '%');
            rect.setAttribute('height', area.height + '%');
            rect.setAttribute('stroke', '#28c76f');
            rect.setAttribute('stroke-width', '2');
            rect.setAttribute('fill', 'rgba(40, 199, 111, 0.2)');
            rect.style.pointerEvents = 'all';
            rect.dataset.index = index;
            rect.style.cursor = 'pointer';
            svg.appendChild(rect);
        });
        
        // Render list
        areasContainer.innerHTML = printAreas.length === 0 
            ? '<p>{{ __("admin::app.catalog.products.customization-areas.no-areas") }}</p>'
            : printAreas.map((area, index) => '
                <div class="print-area-item">
                    <span class="area-info">${area.name} (X: ${area.x.toFixed(1)}%, Y: ${area.y.toFixed(1)}%, W: ${area.width.toFixed(1)}%, H: ${area.height.toFixed(1)}%)</span>
                    <span class="delete-btn" data-index="${index}">✕</span>
                </div>
            ').join('');
        
        // Add delete handlers
        areasContainer.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                printAreas.splice(index, 1);
                renderPrintAreas();
                saveAreasBtn.disabled = printAreas.length === 0;
            });
        });
    }

    // Save print areas
    saveAreasBtn.addEventListener('click', async function() {
        if (!currentImageId || printAreas.length === 0) return;
        
        try {
            const response = await fetch('/customization/print-areas/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    image_id: currentImageId,
                    areas: printAreas
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('{{ __("admin::app.catalog.products.customization-areas.saved") }}');
            } else {
                alert('{{ __("admin::app.catalog.products.customization-areas.save-failed") }}');
            }
        } catch (error) {
            console.error('Error saving print areas:', error);
            alert('{{ __("admin::app.catalog.products.customization-areas.save-failed") }}');
        }
    });
});
</script>
