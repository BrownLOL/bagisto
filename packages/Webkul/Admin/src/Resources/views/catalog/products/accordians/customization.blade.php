<v-product-customization></v-product-customization>

@php
    $productImages = $product->images->map(function($img) {
        return [
            'id' => $img->id,
            'path' => $img->path,
            'url' => url('storage/' . $img->path)
        ];
    })->toArray();

    $printAreas = [];
    foreach ($product->images as $image) {
        foreach ($image->printAreas as $area) {
            $printAreas[] = [
                'id' => $area->id,
                'image_id' => $area->product_image_id,
                'imageUrl' => url('storage/' . $image->path),
                'name' => $area->name,
                'x' => $area->x,
                'y' => $area->y,
                'width' => $area->width,
                'height' => $area->height,
            ];
        }
    }
@endphp

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

                    <div class="flex items-center gap-x-1">
                        <button
                            type="button"
                            class="secondary-button"
                            @click="showDialog = true"
                        >
                            Add Print Area
                        </button>
                    </div>
                </div>

                <!-- Saved Areas Grid -->
                <div class="p-4 pt-0">
                    <div v-if="allAreas.length === 0" class="rounded bg-gray-50 py-10 text-center text-sm text-gray-500 dark:bg-gray-800">
                        No print areas defined yet. Click "Add Print Area" to create one.
                    </div>

                    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        <div
                            v-for="(area, index) in allAreas"
                            :key="index"
                            class="relative rounded border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <!-- Preview Image with Area Overlay -->
                            <div class="relative mb-2 overflow-hidden rounded">
                                <img
                                    :src="area.imageUrl"
                                    :alt="'Print Area ' + (index + 1)"
                                    class="h-24 w-full object-cover"
                                >
                                <div
                                    class="absolute border-2 border-green-500 bg-green-500/20"
                                    :style="{
                                        left: area.x + '%',
                                        top: area.y + '%',
                                        width: area.width + '%',
                                        height: area.height + '%'
                                    }"
                                ></div>
                            </div>

                            <!-- Info -->
                            <p class="mb-1 truncate text-xs font-medium text-gray-700 dark:text-gray-300">
                                Area @{{ index + 1 }}
                            </p>
                            <p class="mb-2 text-xs text-gray-500">
                                @{{ Number(area.width).toFixed(0) }}% x @{{ Number(area.height).toFixed(0) }}%
                            </p>

                            <!-- Delete Button -->
                            <button
                                type="button"
                                class="w-full cursor-pointer rounded bg-red-50 px-2 py-1 text-xs text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50"
                                @click="deleteArea(index)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Print Area Dialog -->
            <x-admin::modal
                v-if="showDialog"
                @toggle="showDialog = false"
            >
                <!-- Modal Header -->
                <x-slot:header>
                    <p class="text-lg font-semibold text-gray-800 dark:text-white">
                        Add Print Area
                    </p>
                </x-slot:header>

                <!-- Modal Content -->
                <x-slot:content>
                    <div class="grid gap-4">
                        <!-- Image Selection -->
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                Select Image
                            </label>
                            <select
                                v-model="dialogSelectedImageId"
                                class="custom-select w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                                @change="onDialogImageChange"
                            >
                                <option value="">Choose an image...</option>
                                <option
                                    v-for="image in images"
                                    :key="image.id"
                                    :value="image.id"
                                >
                                    @{{ image.id }} - @{{ image.path }}
                                </option>
                            </select>
                        </div>

                        <!-- Image Preview with Drawing -->
                        <div v-if="dialogSelectedImageId" class="relative">
                            <p class="mb-2 text-xs font-medium text-gray-500">
                                Click "Start Drawing" then draw on the image to define the print area.
                            </p>

                            <div class="relative inline-block max-w-full overflow-hidden rounded border border-gray-200">
                                <img
                                    ref="dialogImage"
                                    :src="dialogSelectedImageUrl"
                                    alt="Selected Image"
                                    class="max-w-full"
                                    @load="onDialogImageLoad"
                                >
                                <svg
                                    ref="dialogSvg"
                                    class="pointer-events absolute top-0 left-0 h-full w-full"
                                    @mousedown="startDraw"
                                    @mousemove="updateDraw"
                                    @mouseup="endDraw"
                                >
                                    <rect
                                        v-if="tempRect"
                                        :x="tempRect.x + '%'"
                                        :y="tempRect.y + '%'"
                                        :width="tempRect.width + '%'"
                                        :height="tempRect.height + '%'"
                                        stroke="#0068e1"
                                        stroke-dasharray="5,5"
                                        stroke-width="2"
                                        fill="rgba(0, 104, 225, 0.2)"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Preview Size Info -->
                        <div v-if="tempRect" class="rounded bg-blue-50 p-3 text-sm text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                            Area size: @{{ Number(tempRect.width).toFixed(1) }}% x @{{ Number(tempRect.height).toFixed(1) }}%
                        </div>
                    </div>
                </x-slot:content>

                <!-- Modal Footer -->
                <x-slot:footer>
                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="cursor-pointer rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            @click="showDialog = false"
                        >
                            Cancel
                        </button>

                        <button
                            v-if="!isDrawing"
                            type="button"
                            class="cursor-pointer rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            :disabled="!dialogSelectedImageId || !imageLoaded"
                            :class="{'opacity-50 cursor-not-allowed': !dialogSelectedImageId || !imageLoaded}"
                            @click="startDrawing"
                        >
                            Start Drawing
                        </button>

                        <button
                            v-else
                            type="button"
                            class="cursor-pointer rounded bg-orange-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-600"
                            @click="cancelDrawing"
                        >
                            Cancel Drawing
                        </button>

                        <button
                            type="button"
                            class="cursor-pointer rounded bg-blue-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-gray-300"
                            :disabled="!canSaveArea"
                            @click="saveArea"
                        >
                            Save Area
                        </button>
                    </div>
                </x-slot:footer>
            </x-admin::modal>
        </div>
    </script>

    <script type="module">
        app.component('v-product-customization', {
            template: '#v-product-customization-template',

            data() {
                return {
                    productId: {{ $product->id }},
                    images: {!! json_encode($productImages) !!},
                    allAreas: {!! json_encode($printAreas) !!},
                    showDialog: false,
                    dialogSelectedImageId: '',
                    dialogSelectedImageUrl: '',
                    imageLoaded: false,
                    isDrawing: false,
                    tempRect: null,
                    drawStartX: 0,
                    drawStartY: 0,
                    saving: false,
                }
            },

            computed: {
                canSaveArea() {
                    return this.tempRect && this.dialogSelectedImageId;
                }
            },

            methods: {
                onDialogImageChange() {
                    this.imageLoaded = false;
                    this.tempRect = null;
                    this.isDrawing = false;
                    const image = this.images.find(img => img.id == this.dialogSelectedImageId);
                    this.dialogSelectedImageUrl = image ? image.url : '';
                },

                onDialogImageLoad() {
                    this.imageLoaded = true;
                },

                startDrawing() {
                    if (!this.dialogSelectedImageId || !this.imageLoaded) return;
                    this.isDrawing = true;
                    this.$refs.dialogSvg.style.cursor = 'crosshair';
                },

                cancelDrawing() {
                    this.isDrawing = false;
                    this.tempRect = null;
                    this.$refs.dialogSvg.style.cursor = 'default';
                },

                startDraw(e) {
                    if (!this.isDrawing) return;

                    const rect = this.$refs.dialogSvg.getBoundingClientRect();
                    this.drawStartX = ((e.clientX - rect.left) / rect.width) * 100;
                    this.drawStartY = ((e.clientY - rect.top) / rect.height) * 100;
                },

                updateDraw(e) {
                    if (!this.isDrawing) return;

                    const rect = this.$refs.dialogSvg.getBoundingClientRect();
                    const currentX = ((e.clientX - rect.left) / rect.width) * 100;
                    const currentY = ((e.clientY - rect.top) / rect.height) * 100;

                    const x = Math.min(this.drawStartX, currentX);
                    const y = Math.min(this.drawStartY, currentY);
                    const width = Math.abs(currentX - this.drawStartX);
                    const height = Math.abs(currentY - this.drawStartY);

                    this.tempRect = { x, y, width, height };
                },

                endDraw(e) {
                    if (!this.isDrawing) return;

                    const rect = this.$refs.dialogSvg.getBoundingClientRect();
                    const endX = ((e.clientX - rect.left) / rect.width) * 100;
                    const endY = ((e.clientY - rect.top) / rect.height) * 100;

                    const x = Math.min(this.drawStartX, endX);
                    const y = Math.min(this.drawStartY, endY);
                    const width = Math.abs(endX - this.drawStartX);
                    const height = Math.abs(endY - this.drawStartY);

                    if (width > 1 && height > 1) {
                        this.tempRect = { x, y, width, height };
                    }

                    this.isDrawing = false;
                    this.$refs.dialogSvg.style.cursor = 'default';
                },

                async saveArea() {
                    if (!this.tempRect || !this.dialogSelectedImageId || this.saving) return;

                    this.saving = true;

                    try {
                        const response = await this.$axios.post("{{ route('admin.catalog.products.print-areas.save') }}", {
                            product_id: this.productId,
                            image_id: this.dialogSelectedImageId,
                            areas: [{
                                name: 'Print Area ' + (this.allAreas.length + 1),
                                x: this.tempRect.x,
                                y: this.tempRect.y,
                                width: this.tempRect.width,
                                height: this.tempRect.height
                            }]
                        });

                        if (response.data.success) {
                            const image = this.images.find(img => img.id == this.dialogSelectedImageId);
                            this.allAreas.push({
                                id: null,
                                image_id: this.dialogSelectedImageId,
                                imageUrl: image ? image.url : '',
                                name: 'Print Area ' + (this.allAreas.length + 1),
                                x: this.tempRect.x,
                                y: this.tempRect.y,
                                width: this.tempRect.width,
                                height: this.tempRect.height
                            });

                            this.showDialog = false;
                            this.dialogSelectedImageId = '';
                            this.tempRect = null;
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    } catch (error) {
                        alert('Error saving area: ' + error.message);
                    } finally {
                        this.saving = false;
                    }
                },

                async deleteArea(index) {
                    const area = this.allAreas[index];
                    if (!area.id) {
                        this.allAreas.splice(index, 1);
                        return;
                    }

                    if (!confirm('Are you sure you want to delete this print area?')) {
                        return;
                    }

                    try {
                        const response = await this.$axios.delete("{{ route('admin.catalog.products.print-areas.delete', ':id') }}".replace(':id', area.id));

                        if (response.data.success) {
                            this.allAreas.splice(index, 1);
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    } catch (error) {
                        alert('Error deleting area: ' + error.message);
                    }
                }
            }
        });
    </script>
@endpushOnce
