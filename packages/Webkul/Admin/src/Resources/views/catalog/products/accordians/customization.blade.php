<div class="customization-areas-wrapper">
    <div class="section">
        <div class="sectitle">
            <span>Customization Areas</span>
        </div>

        <div class="section-content">
            <div class="customization-instructions">
                <p>Upload product images and define printable areas for customer customization.</p>
            </div>

            <!-- Image Selection -->
            <div class="image-selector mb-4">
                <label>Select Image:</label>
                <select id="customization-image-select" class="control">
                    <option value="">Choose an image...</option>
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
                    <svg id="print-area-svg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: all;"></svg>
                </div>
            </div>

            <!-- No Images Message -->
            @if ($product->images->isEmpty())
                <div class="no-images-message">
                    <p>Please upload product images first.</p>
                </div>
            @endif

            <!-- Print Areas List -->
            <div id="print-areas-list" class="print-areas-list mt-4">
                <h4>Defined Areas:</h4>
                <div id="areas-container"></div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons mt-4">
                <button type="button" id="start-draw-btn" class="btn btn-secondary" disabled>
                    Set Print Area
                </button>
                <button type="button" id="save-areas-btn" class="btn btn-primary" disabled>
                    Save Areas
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.customization-areas-wrapper { padding: 20px; }
.customization-areas-wrapper .section-content { padding: 20px; background: #f8f9fa; border-radius: 4px; }
.print-area-item { display: flex; align-items: center; justify-content: space-between; padding: 10px; margin: 5px 0; background: white; border: 1px solid #ddd; border-radius: 4px; }
.print-area-item .area-info { font-size: 14px; }
.print-area-item .delete-btn { color: #f14d54; cursor: pointer; font-weight: bold; }
</style>

@php
    $productId = $product->id;
    $existingAreas = $product->images->flatMap->printAreas->toArray();
@endphp

<script>
(function() {
    const imageSelect = document.getElementById('customization-image-select');
    const container = document.getElementById('print-area-container');
    const image = document.getElementById('customization-image');
    const svg = document.getElementById('print-area-svg');
    const startDrawBtn = document.getElementById('start-draw-btn');
    const saveAreasBtn = document.getElementById('save-areas-btn');
    const areasContainer = document.getElementById('areas-container');
    
    let currentImageId = null;
    let printAreas = {!! json_encode($existingAreas) !!};
    let isDrawing = false;
    let startX, startY;
    let tempRect = null;
    
    const productId = {!! $productId !!};

    // Initialize areas display
    updateAreasDisplay();

    // Load image on selection
    imageSelect.addEventListener('change', function() {
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
        
        // Filter areas for current image
        printAreas = printAreas.filter(area => area.product_image_id == currentImageId);
        updateAreasDisplay();
    });

    // Start drawing mode
    startDrawBtn.addEventListener('click', function() {
        if (!currentImageId) return;
        isDrawing = true;
        startDrawBtn.textContent = 'Drawing... Click and drag on image';
        svg.style.cursor = 'crosshair';
    });

    // Mouse down - start drawing
    svg.addEventListener('mousedown', function(e) {
        if (!isDrawing) return;
        
        const rect = svg.getBoundingClientRect();
        startX = ((e.clientX - rect.left) / rect.width) * 100;
        startY = ((e.clientY - rect.top) / rect.height) * 100;
        
        tempRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        tempRect.setAttribute('stroke', '#0068e1');
        tempRect.setAttribute('stroke-dasharray', '5,5');
        tempRect.setAttribute('fill', 'rgba(0, 104, 225, 0.1)');
        tempRect.setAttribute('stroke-width', '2');
        svg.appendChild(tempRect);
    });

    // Mouse move - update rectangle
    svg.addEventListener('mousemove', function(e) {
        if (!isDrawing || !tempRect) return;
        
        const rect = svg.getBoundingClientRect();
        const currentX = ((e.clientX - rect.left) / rect.width) * 100;
        const currentY = ((e.clientY - rect.top) / rect.height) * 100;
        
        const x = Math.min(startX, currentX);
        const y = Math.min(startY, currentY);
        const width = Math.abs(currentX - startX);
        const height = Math.abs(currentY - startY);
        
        tempRect.setAttribute('x', x + '%');
        tempRect.setAttribute('y', y + '%');
        tempRect.setAttribute('width', width + '%');
        tempRect.setAttribute('height', height + '%');
    });

    // Mouse up - finish drawing
    svg.addEventListener('mouseup', function(e) {
        if (!isDrawing || !tempRect) return;
        
        const rect = svg.getBoundingClientRect();
        const endX = ((e.clientX - rect.left) / rect.width) * 100;
        const endY = ((e.clientY - rect.top) / rect.height) * 100;
        
        const x = Math.min(startX, endX);
        const y = Math.min(startY, endY);
        const width = Math.abs(endX - startX);
        const height = Math.abs(endY - startY);
        
        if (width > 1 && height > 1) {
            const areaNumber = printAreas.filter(a => a.product_image_id == currentImageId).length + 1;
            printAreas.push({
                id: null,
                product_image_id: currentImageId,
                name: 'Area ' + areaNumber,
                x: x,
                y: y,
                width: width,
                height: height
            });
            updateAreasDisplay();
        }
        
        if (tempRect) {
            tempRect.remove();
            tempRect = null;
        }
        
        isDrawing = false;
        startDrawBtn.textContent = 'Set Print Area';
        svg.style.cursor = 'default';
        
        saveAreasBtn.disabled = false;
    });

    // Update areas display
    function updateAreasDisplay() {
        const imageAreas = printAreas.filter(area => area.product_image_id == currentImageId);
        
        if (!currentImageId || imageAreas.length === 0) {
            areasContainer.innerHTML = '<p style="color:#666;">No areas defined yet. Select an image and click "Set Print Area" to draw.</p>';
            return;
        }
        
        let html = '';
        imageAreas.forEach((area, index) => {
            html += '<div class="print-area-item">';
            html += '<span class="area-info">Area ' + (index + 1) + ' (X: ' + area.x.toFixed(1) + '%, Y: ' + area.y.toFixed(1) + '%, W: ' + area.width.toFixed(1) + '%, H: ' + area.height.toFixed(1) + '%)</span>';
            html += '<span class="delete-btn" data-index="' + printAreas.indexOf(area) + '">X</span>';
            html += '</div>';
        });
        areasContainer.innerHTML = html;
        
        // Add delete handlers
        areasContainer.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                printAreas.splice(index, 1);
                updateAreasDisplay();
                saveAreasBtn.disabled = false;
            });
        });
    }

    // Save areas
    saveAreasBtn.addEventListener('click', async function() {
        if (!currentImageId) return;
        
        const imageAreas = printAreas.filter(area => area.product_image_id == currentImageId);
        
        try {
            const response = await fetch('/customization/print-areas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    product_id: productId,
                    image_id: currentImageId,
                    areas: imageAreas
                })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Areas saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error saving areas: ' + error.message);
        }
    });
})();
</script>
