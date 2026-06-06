<v-product-customization></v-product-customization>

@php
    $productImages = $product->images->map(function($img) {
        return [
            'id' => $img->id,
            'path' => $img->path,
            'url' => url('storage/' . $img->path)
        ];
    })->toArray();

    $imagesWithAreas = [];
    foreach ($product->images as $image) {
        if ($image->printAreas->count() > 0) {
            $imagesWithAreas[] = [
                'id' => $image->id,
                'path' => $image->path,
                'url' => url('storage/' . $image->path),
                'areas' => $image->printAreas->map(function($area) {
                    return [
                        'id' => $area->id,
                        'x' => $area->x,
                        'y' => $area->y,
                        'width' => $area->width,
                        'height' => $area->height,
                    ];
                })->toArray(),
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
                            Manage printable areas on product images.
                        </p>
                    </div>

                    <div class="flex items-center gap-x-1">
                        <button
                            type="button"
                            class="secondary-button"
                            @click="openAddDialog"
                        >
                            Add Print Area
                        </button>
                    </div>
                </div>

                <!-- Images with Areas -->
                <div class="p-4 pt-0">
                    <div v-if="imagesWithAreas.length === 0" class="rounded bg-gray-50 py-10 text-center text-sm text-gray-500 dark:bg-gray-800">
                        No print areas defined yet. Click "Add Print Area" to create one.
                    </div>

                    <div v-else class="grid grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-6">
                        <div
                            v-for="imageData in imagesWithAreas"
                            :key="imageData.id"
                            class="relative cursor-pointer rounded border border-gray-200 p-2 transition-all hover:border-blue-400 hover:shadow dark:border-gray-700"
                            @click="openEditDialog(imageData)"
                        >
                            <div class="relative mb-2 h-[80px] w-full overflow-hidden rounded">
                                <img
                                    :src="imageData.url"
                                    :alt="'Image ' + imageData.id"
                                    class="h-full w-full object-cover"
                                >
                                <!-- Area count badge -->
                                <span class="absolute right-1 top-1 rounded-full bg-green-500 px-1.5 py-0.5 text-xs font-bold text-white">
                                    @{{ imageData.areas.length }}
                                </span>
                            </div>
                            <p class="truncate text-center text-xs text-gray-600 dark:text-gray-400">
                                Image @{{ imageData.id }}
                            </p>
                            <p class="text-center text-xs text-gray-400">
                                @{{ imageData.areas.length }} area(s)
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Print Area Modal -->
            <x-admin::modal ref="printAreaModal">
                <!-- Modal Header -->
                <x-slot:header>
                    <p class="text-lg font-semibold text-gray-800 dark:text-white">
                        @{{ isEditMode ? 'Edit Print Areas' : 'Add Print Area' }}
                    </p>
                </x-slot:header>

                <!-- Modal Content -->
                <x-slot:content>
                    <div class="grid gap-4">
                        <!-- Image Selection (only for Add mode) -->
                        <div v-if="!isEditMode">
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
                                    v-for="image in availableImages"
                                    :key="image.id"
                                    :value="image.id"
                                >
                                    @{{ image.id }} - @{{ image.path }}
                                </option>
                            </select>
                        </div>

                        <!-- Image Preview with Drawing -->
                        <div v-if="dialogSelectedImageUrl" class="relative">
                            <p class="mb-2 text-xs font-medium text-gray-500">
                                Click and drag on the image to draw print areas.
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
                                    class="pointer-events absolute top-0 left-0 h-full w-full cursor-crosshair"
                                    @mousedown="startDraw"
                                    @mousemove="updateDraw"
                                    @mouseup="endDraw"
                                >
                                    <!-- Existing areas -->
                                    <rect
                                        v-for="(area, index) in tempAreas"
                                        :key="'area-' + index"
                                        :x="area.x + '%'"
                                        :y="area.y + '%'"
                                        :width="area.width + '%'"
                                        :height="area.height + '%'"
                                        stroke="#28a745"
                                        stroke-width="2"
                                        fill="rgba(40, 167, 69, 0.2)"
                                    />
                                    <!-- Current drawing rectangle -->
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

                        <!-- Areas List -->
                        <div v-if="tempAreas.length > 0" class="rounded border border-gray-200 p-3 dark:border-gray-700">
                            <p class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                                Areas (@{{ tempAreas.length }})
                            </p>
                            <div class="space-y-2">
                                <div
                                    v-for="(area, index) in tempAreas"
                                    :key="index"
                                    class="flex items-center justify-between rounded bg-gray-50 p-2 dark:bg-gray-800"
                                >
                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        Area @{{ index + 1 }}: @{{ Number(area.width).toFixed(1) }}% x @{{ Number(area.height).toFixed(1) }}%
                                    </span>
                                    <button
                                        type="button"
                                        class="text-xs text-red-500 hover:text-red-700"
                                        @click="removeTempArea(index)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot:content>

                <!-- Modal Footer -->
                <x-slot:footer>
                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="cursor-pointer rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            @click="closeDialog"
                        >
                            Cancel
                        </button>

                        <button
                            v-if="isEditMode && originalImageId"
                            type="button"
                            class="cursor-pointer rounded bg-red-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-600"
                            @click="deleteAllAreas"
                        >
                            Delete All
                        </button>

                        <button
                            type="button"
                            class="cursor-pointer rounded bg-blue-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-gray-300"
                            :disabled="tempAreas.length === 0 || saving"
                            @click="saveAreas"
                        >
                            @{{ saving ? 'Saving...' : 'Save' }}
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
                    allImages: {!! json_encode($productImages) !!},
                    imagesWithAreas: {!! json_encode($imagesWithAreas) !!},
                    dialogSelectedImageId: '',
                    dialogSelectedImageUrl: '',
                    originalImageId: null,
                    isEditMode: false,
                    imageLoaded: false,
                    isDrawing: false,
                    tempRect: null,
                    tempAreas: [],
                    drawStartX: 0,
                    drawStartY: 0,
                    saving: false,
                }
            },

            computed: {
                availableImages() {
                    // In add mode, only show images without areas
                    const imagesWithAreaIds = this.imagesWithAreas.map(img => img.id);
                    return this.allImages.filter(img => !imagesWithAreaIds.includes(img.id));
                }
            },

            methods: {
                openAddDialog() {
                    this.isEditMode = false;
                    this.originalImageId = null;
                    this.dialogSelectedImageId = '';
                    this.dialogSelectedImageUrl = '';
                    this.tempAreas = [];
                    this.imageLoaded = false;
                    this.$refs.printAreaModal.open();
                },

                openEditDialog(imageData) {
                    this.isEditMode = true;
                    this.originalImageId = imageData.id;
                    this.dialogSelectedImageId = imageData.id;
                    this.dialogSelectedImageUrl = imageData.url;
                    this.tempAreas = JSON.parse(JSON.stringify(imageData.areas));
                    this.imageLoaded = true;
                    this.$refs.printAreaModal.open();
                },

                closeDialog() {
                    this.$refs.printAreaModal.close();
                    this.dialogSelectedImageId = '';
                    this.dialogSelectedImageUrl = '';
                    this.originalImageId = null;
                    this.isEditMode = false;
                    this.tempRect = null;
                    this.tempAreas = [];
                    this.imageLoaded = false;
                },

                onDialogImageChange() {
                    this.tempAreas = [];
                    const image = this.allImages.find(img => img.id == this.dialogSelectedImageId);
                    this.dialogSelectedImageUrl = image ? image.url : '';
                },

                onDialogImageLoad() {
                    this.imageLoaded = true;
                },

                startDraw(e) {
                    if (!this.dialogSelectedImageUrl || !this.imageLoaded) return;

                    const rect = this.$refs.dialogSvg.getBoundingClientRect();
                    this.drawStartX = ((e.clientX - rect.left) / rect.width) * 100;
                    this.drawStartY = ((e.clientY - rect.top) / rect.height) * 100;
                    this.isDrawing = true;
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
                        this.tempAreas.push({ x, y, width, height });
                    }

                    this.tempRect = null;
                    this.isDrawing = false;
                },

                removeTempArea(index) {
                    this.tempAreas.splice(index, 1);
                },

                async saveAreas() {
                    if (this.tempAreas.length === 0 || !this.dialogSelectedImageId || this.saving) return;

                    this.saving = true;

                    try {
                        const response = await this.$axios.post("{{ route('admin.catalog.products.print-areas.save') }}", {
                            product_id: this.productId,
                            image_id: this.dialogSelectedImageId,
                            areas: this.tempAreas.map((area, index) => ({
                                name: 'Area ' + (index + 1),
                                x: area.x,
                                y: area.y,
                                width: area.width,
                                height: area.height
                            }))
                        });

                        if (response.data.success) {
                            // Refresh the page to get updated data
                            window.location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    } catch (error) {
                        alert('Error saving areas: ' + error.message);
                    } finally {
                        this.saving = false;
                    }
                },

                deleteAllAreas() {
                    this.localAreas = [];
                },
                    }
                }
            }
        });
    </script>
@endpushOnce
