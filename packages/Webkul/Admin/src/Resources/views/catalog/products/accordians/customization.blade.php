<div class="customization-areas-wrapper">
    <div class="section">
        <div class="sectitle">
            <span>Customization Areas</span>
        </div>

        <div class="section-content">
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
            <div id="print-area-container" class="print-area-container" style="display: none;">
                <div class="image-wrapper" style="position: relative; display: inline-block; max-width: 100%;">
                    <img id="customization-image" src="" alt="Product Image" style="max-width: 100%; display: block;">
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
#print-area-svg rect { cursor: pointer; }
</style>

@php
    $productId = $product->id;
    $existingAreas = $product->images->flatMap->printAreas->toArray();
@endphp

<script>
(function() {
    var imageSelect = document.getElementById('customization-image-select');
    var container = document.getElementById('print-area-container');
    var img = document.getElementById('customization-image');
    var svg = document.getElementById('print-area-svg');
    var startDrawBtn = document.getElementById('start-draw-btn');
    var saveAreasBtn = document.getElementById('save-areas-btn');
    var areasContainer = document.getElementById('areas-container');
    
    var currentImageId = null;
    var printAreas = {!! json_encode($existingAreas) !!};
    var isDrawing = false;
    var startX, startY;
    var tempRect = null;
    var isImageLoaded = false;
    
    var productId = {!! $productId !!};

    // Initialize areas display
    updateAreasDisplay();

    // Load image on selection
    imageSelect.addEventListener('change', function() {
        var option = this.options[this.selectedIndex];
        if (!option.value) {
            container.style.display = 'none';
            startDrawBtn.disabled = true;
            saveAreasBtn.disabled = true;
            return;
        }

        currentImageId = option.value;
        var imageUrl = option.dataset.url;
        
        // Clear previous SVG content
        svg.innerHTML = '';
        
        // Load image
        img.onload = function() {
            isImageLoaded = true;
            container.style.display = 'block';
            startDrawBtn.disabled = false;
            
            // Re-render existing areas for this image
            renderExistingAreas();
            updateAreasDisplay();
        };
        
        img.onerror = function() {
            alert('Failed to load image');
            container.style.display = 'none';
        };
        
        img.src = imageUrl;
    });

    // Render existing areas on the SVG
    function renderExistingAreas() {
        svg.innerHTML = '';
        var imageAreas = printAreas.filter(function(area) { 
            return area.product_image_id == currentImageId; 
        });
        
        imageAreas.forEach(function(area) {
            var rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rect.setAttribute('x', area.x + '%');
            rect.setAttribute('y', area.y + '%');
            rect.setAttribute('width', area.width + '%');
            rect.setAttribute('height', area.height + '%');
            rect.setAttribute('stroke', '#28a745');
            rect.setAttribute('stroke-width', '2');
            rect.setAttribute('fill', 'rgba(40, 167, 69, 0.2)');
            svg.appendChild(rect);
        });
    }

    // Start drawing mode
    startDrawBtn.addEventListener('click', function() {
        if (!currentImageId || !isImageLoaded) return;
        isDrawing = true;
        startDrawBtn.textContent = 'Drawing... Click and drag on image';
        svg.style.cursor = 'crosshair';
    });

    // Mouse down - start drawing
    svg.addEventListener('mousedown', function(e) {
        if (!isDrawing) return;
        
        var rect = svg.getBoundingClientRect();
        startX = ((e.clientX - rect.left) / rect.width) * 100;
        startY = ((e.clientY - rect.top) / rect.height) * 100;
        
        tempRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        tempRect.setAttribute('stroke', '#0068e1');
        tempRect.setAttribute('stroke-dasharray', '5,5');
        tempRect.setAttribute('fill', 'rgba(0, 104, 225, 0.2)');
        tempRect.setAttribute('stroke-width', '2');
        svg.appendChild(tempRect);
    });

    // Mouse move - update rectangle
    svg.addEventListener('mousemove', function(e) {
        if (!isDrawing || !tempRect) return;
        
        var rect = svg.getBoundingClientRect();
        var currentX = ((e.clientX - rect.left) / rect.width) * 100;
        var currentY = ((e.clientY - rect.top) / rect.height) * 100;
        
        var x = Math.min(startX, currentX);
        var y = Math.min(startY, currentY);
        var width = Math.abs(currentX - startX);
        var height = Math.abs(currentY - startY);
        
        tempRect.setAttribute('x', x + '%');
        tempRect.setAttribute('y', y + '%');
        tempRect.setAttribute('width', width + '%');
        tempRect.setAttribute('height', height + '%');
    });

    // Mouse up - finish drawing
    svg.addEventListener('mouseup', function(e) {
        if (!isDrawing || !tempRect) return;
        
        var rect = svg.getBoundingClientRect();
        var endX = ((e.clientX - rect.left) / rect.width) * 100;
        var endY = ((e.clientY - rect.top) / rect.height) * 100;
        
        var x = Math.min(startX, endX);
        var y = Math.min(startY, endY);
        var width = Math.abs(endX - startX);
        var height = Math.abs(endY - startY);
        
        if (width > 1 && height > 1) {
            var areaNumber = printAreas.filter(function(a) { return a.product_image_id == currentImageId; }).length + 1;
            printAreas.push({
                id: null,
                product_image_id: currentImageId,
                name: 'Area ' + areaNumber,
                x: x,
                y: y,
                width: width,
                height: height
            });
            
            // Render the new area
            var newRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            newRect.setAttribute('x', x + '%');
            newRect.setAttribute('y', y + '%');
            newRect.setAttribute('width', width + '%');
            newRect.setAttribute('height', height + '%');
            newRect.setAttribute('stroke', '#28a745');
            newRect.setAttribute('stroke-width', '2');
            newRect.setAttribute('fill', 'rgba(40, 167, 69, 0.2)');
            svg.appendChild(newRect);
            
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
        var imageAreas = printAreas.filter(function(area) { 
            return area.product_image_id == currentImageId; 
        });
        
        if (!currentImageId || imageAreas.length === 0) {
            if (currentImageId) {
                areasContainer.innerHTML = '<p style="color:#666;">No areas defined yet. Select an image and click "Set Print Area" to draw.</p>';
            } else {
                areasContainer.innerHTML = '<p style="color:#999;">Select an image above to start defining print areas.</p>';
            }
            return;
        }
        
        var html = '';
        imageAreas.forEach(function(area, index) {
            var globalIndex = printAreas.indexOf(area);
            html += '<div class="print-area-item">';
            html += '<span class="area-info">Area ' + (index + 1) + ' (X: ' + area.x.toFixed(1) + '%, Y: ' + area.y.toFixed(1) + '%, W: ' + area.width.toFixed(1) + '%, H: ' + area.height.toFixed(1) + '%)</span>';
            html += '<span class="delete-btn" data-index="' + globalIndex + '">X</span>';
            html += '</div>';
        });
        areasContainer.innerHTML = html;
        
        // Add delete handlers
        var deleteBtns = areasContainer.querySelectorAll('.delete-btn');
        for (var i = 0; i < deleteBtns.length; i++) {
            deleteBtns[i].addEventListener('click', (function(btn) {
                return function() {
                    var index = parseInt(btn.dataset.index);
                    printAreas.splice(index, 1);
                    renderExistingAreas();
                    updateAreasDisplay();
                    saveAreasBtn.disabled = false;
                };
            })(deleteBtns[i]));
        }
    }

    // Save areas
    saveAreasBtn.addEventListener('click', async function() {
        if (!currentImageId) return;
        
        var imageAreas = printAreas.filter(function(area) { 
            return area.product_image_id == currentImageId; 
        });
        
        saveAreasBtn.disabled = true;
        saveAreasBtn.textContent = 'Saving...';
        
        try {
            var response = await fetch('/customization/print-areas', {
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
            
            var result = await response.json();
            if (result.success) {
                alert('Areas saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + result.message);
                saveAreasBtn.disabled = false;
                saveAreasBtn.textContent = 'Save Areas';
            }
        } catch (error) {
            alert('Error saving areas: ' + error.message);
            saveAreasBtn.disabled = false;
            saveAreasBtn.textContent = 'Save Areas';
        }
    });
})();
</script>
