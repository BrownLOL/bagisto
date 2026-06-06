<v-product-customization></v-product-customization>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-customization-template"
    >
        <div class="grid gap-2.5">
            <!-- Panel -->
            <div class="box-shadow relative rounded bg-white dark:bg-gray-900">
                <div class="mb-2.5 flex justify-between gap-5 p-4">
                    <div class="flex flex-col gap-2">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            Customization Areas
                        </p>

                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                            Define printable areas on product images for customer customization.
                        </p>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-4 pt-0">
                    <!-- Image Selection -->
                    <div class="mb-4">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Select Image
                        </label>
                        <select
                            v-model="selectedImageId"
                            class="custom-select w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-900"
                            @change="onImageChange"
                        >
                            <option value="">Choose an image...</option>
                            <option
                                v-for="image in images"
                                :key="image.id"
                                :value="image.id"
                                :data-url="image.url"
                            >
                                @{{ image.id }} - @{{ image.path }}
                            </option>
                        </select>
                    </div>

                    <!-- Image Preview with SVG Overlay -->
                    <div
                        v-if="selectedImageId"
                        class="mb-4"
                    >
                        <div class="relative inline-block max-w-full overflow-hidden rounded border border-gray-200">
                            <img
                                :src="selectedImageUrl"
                                alt="Product Image"
                                class="max-w-full"
                                @load="onImageLoad"
                            >
                            <svg
                                ref="printAreaSvg"
                                class="pointer-events absolute top-0 left-0 h-full w-full"
                                @mousedown="startDraw"
                                @mousemove="updateDraw"
                                @mouseup="endDraw"
                            >
                                <rect
                                    v-for="(area, index) in currentImageAreas"
                                    :key="index"
                                    :x="area.x + '%'"
                                    :y="area.y + '%'"
                                    :width="area.width + '%'"
                                    :height="area.height + '%'"
                                    stroke="#28a745"
                                    stroke-width="2"
                                    fill="rgba(40, 167, 69, 0.2)"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Defined Areas List -->
                    <div class="mb-4">
                        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Defined Areas (@{{ currentImageAreas.length }})
                        </p>

                        <div v-if="currentImageAreas.length === 0" class="rounded bg-gray-50 p-4 text-center text-sm text-gray-500 dark:bg-gray-800">
                            No areas defined yet. Click "Set Print Area" to draw on the image.
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="(area, index) in currentImageAreas"
                                :key="index"
                                class="flex items-center justify-between rounded border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800"
                            >
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    Area @{{ index + 1 }} (X: @{{ area.x.toFixed(1) }}%, Y: @{{ area.y.toFixed(1) }}%, W: @{{ area.width.toFixed(1) }}%, H: @{{ area.height.toFixed(1) }}%)
                                </span>
                                <button
                                    type="button"
                                    class="text-red-500 hover:text-red-700"
                                    @click="deleteArea(index)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="cursor-pointer rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            :disabled="!selectedImageId || isDrawing || !imageLoaded"
                            :class="{'opacity-50 cursor-not-allowed': !selectedImageId || !imageLoaded}"
                            @click="toggleDrawMode"
                        >
                            @{{ isDrawing ? 'Drawing... Click and drag' : 'Set Print Area' }}
                        </button>

                        <button
                            type="button"
                            class="cursor-pointer rounded bg-blue-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-gray-300"
                            :disabled="currentImageAreas.length === 0 || saving"
                            @click="saveAreas"
                        >
                            @{{ saving ? 'Saving...' : 'Save Areas' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-product-customization', {
            template: '#v-product-customization-template',

            data() {
                return {
                    productId: {{ $product->id }},
                    images: @json($product->images->map(function($img) {
                        return [
                            'id' => $img->id,
                            'path' => $img->path,
                            'url' => url('storage/' . $img->path)
                        ];
                    })),
                    existingAreas: @json($product->images->flatMap->printAreas->toArray()),
                    selectedImageId: '',
                    isDrawing: false,
                    isSaving: false,
                    imageLoaded: false,
                    saving: false,
                    startX: 0,
                    startY: 0,
                    tempRect: null,
                    drawStartX: 0,
                    drawStartY: 0,
                }
            },

            computed: {
                selectedImageUrl() {
                    const image = this.images.find(img => img.id == this.selectedImageId);
                    return image ? image.url : '';
                },
                currentImageAreas() {
                    return this.existingAreas.filter(area => area.product_image_id == this.selectedImageId);
                }
            },

            methods: {
                onImageChange() {
                    this.imageLoaded = false;
                    this.isDrawing = false;
                },

                onImageLoad() {
                    this.imageLoaded = true;
                },

                toggleDrawMode() {
                    if (!this.selectedImageId || !this.imageLoaded) return;
                    this.isDrawing = !this.isDrawing;
                    
                    if (this.isDrawing) {
                        this.$refs.printAreaSvg.style.cursor = 'crosshair';
                    } else {
                        this.$refs.printAreaSvg.style.cursor = 'default';
                    }
                },

                startDraw(e) {
                    if (!this.isDrawing) return;

                    const rect = this.$refs.printAreaSvg.getBoundingClientRect();
                    this.drawStartX = ((e.clientX - rect.left) / rect.width) * 100;
                    this.drawStartY = ((e.clientY - rect.top) / rect.height) * 100;

                    this.tempRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    this.tempRect.setAttribute('stroke', '#0068e1');
                    this.tempRect.setAttribute('stroke-dasharray', '5,5');
                    this.tempRect.setAttribute('fill', 'rgba(0, 104, 225, 0.2)');
                    this.tempRect.setAttribute('stroke-width', '2');
                    this.$refs.printAreaSvg.appendChild(this.tempRect);
                },

                updateDraw(e) {
                    if (!this.isDrawing || !this.tempRect) return;

                    const rect = this.$refs.printAreaSvg.getBoundingClientRect();
                    const currentX = ((e.clientX - rect.left) / rect.width) * 100;
                    const currentY = ((e.clientY - rect.top) / rect.height) * 100;

                    const x = Math.min(this.drawStartX, currentX);
                    const y = Math.min(this.drawStartY, currentY);
                    const width = Math.abs(currentX - this.drawStartX);
                    const height = Math.abs(currentY - this.drawStartY);

                    this.tempRect.setAttribute('x', x + '%');
                    this.tempRect.setAttribute('y', y + '%');
                    this.tempRect.setAttribute('width', width + '%');
                    this.tempRect.setAttribute('height', height + '%');
                },

                endDraw(e) {
                    if (!this.isDrawing || !this.tempRect) return;

                    const rect = this.$refs.printAreaSvg.getBoundingClientRect();
                    const endX = ((e.clientX - rect.left) / rect.width) * 100;
                    const endY = ((e.clientY - rect.top) / rect.height) * 100;

                    const x = Math.min(this.drawStartX, endX);
                    const y = Math.min(this.drawStartY, endY);
                    const width = Math.abs(endX - this.drawStartX);
                    const height = Math.abs(endY - this.drawStartY);

                    if (width > 1 && height > 1) {
                        const areaNumber = this.currentImageAreas.length + 1;
                        this.existingAreas.push({
                            id: null,
                            product_image_id: this.selectedImageId,
                            name: 'Area ' + areaNumber,
                            x: x,
                            y: y,
                            width: width,
                            height: height
                        });
                    }

                    if (this.tempRect) {
                        this.tempRect.remove();
                        this.tempRect = null;
                    }

                    this.isDrawing = false;
                    this.$refs.printAreaSvg.style.cursor = 'default';
                },

                deleteArea(index) {
                    const areaToDelete = this.currentImageAreas[index];
                    const globalIndex = this.existingAreas.indexOf(areaToDelete);
                    if (globalIndex > -1) {
                        this.existingAreas.splice(globalIndex, 1);
                    }
                },

                async saveAreas() {
                    if (!this.selectedImageId || this.saving) return;

                    this.saving = true;

                    try {
                        const response = await this.$axios.post('/customization/print-areas', {
                            product_id: this.productId,
                            image_id: this.selectedImageId,
                            areas: this.currentImageAreas
                        });

                        if (response.data.success) {
                            window.location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    } catch (error) {
                        alert('Error saving areas: ' + error.message);
                    } finally {
                        this.saving = false;
                    }
                }
            }
        });
    </script>
@endpushOnce
