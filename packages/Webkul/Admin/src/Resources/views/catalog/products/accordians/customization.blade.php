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
                    productId: {{ $product->id ?? 0 }},
                    allImages: {!! json_encode($productImages ?? [], JSON_UNESCAPED_SLASHES) !!},
                    imagesWithAreas: {!! json_encode($imagesWithAreas ?? [], JSON_UNESCAPED_SLASHES) !!},
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
        });

        app.component('v-product-customization', {
            template: `
                <div class="grid grid-cols-2 gap-4">
                    <!-- Thumbnails -->
                    <div class="col-span-1">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Images with Areas</p>
                            <button
                                type="button"
                                class="px-3 py-1 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600"
                                v-on:click="openModal()"
                            >Add Print Area</button>
                        </div>
                        <div class="grid grid-cols-3 gap-2" v-if="imagesWithAreas.length > 0">
                            <div
                                v-for="image in imagesWithAreas"
                                v-bind:key="image.id"
                                class="relative cursor-pointer group"
                                v-on:click="editAreas(image)"
                            >
                                <img
                                    v-bind:src="image.url"
                                    class="w-full h-20 object-cover rounded border"
                                />
                                <span class="absolute -top-1 -right-1 bg-green-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">
                                    @{{ image.areas.length }}
                                </span>
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded transition-all flex items-center justify-center">
                                    <span class="text-white opacity-0 group-hover:opacity-100 text-xs">Edit</span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500">No images with print areas yet.</p>
                    </div>
                </div>

                <!-- Modal -->
                <x-admin::modal ref="printAreaModal">
                    <x-slot:toggle>
                        <!-- Hidden toggle, modal opened programmatically -->
                    </x-slot:toggle>

                    <x-slot:header>
                        <p class="text-lg font-bold">@{{ isEditing ? \'Edit Print Areas\' : \'Add Print Area\' }}</p>
                    </x-slot:header>

                    <x-slot:content>
                        <div class="p-4">
                            <!-- Image selector for new areas -->
                            <div v-if="!isEditing" class="mb-4">
                                <label class="block text-sm font-medium mb-2">Select Image</label>
                                <select
                                    v-model="selectedImageId"
                                    class="w-full border rounded px-3 py-2"
                                    v-on:change="onImageChange"
                                >
                                    <option value="">-- Select --</option>
                                    <option v-for="img in availableImages" v-bind:key="img.id" v-bind:value="img.id">
                                        @{{ img.id }} - @{{ img.path }}
                                    </option>
                                </select>
                            </div>

                            <!-- Image preview with drawing canvas -->
                            <div v-if="previewImage" class="mb-4">
                                <div class="relative inline-block" v-bind:ref="\'imageContainer\'">
                                    <img
                                        v-bind:src="previewImage.url"
                                        class="max-w-full"
                                        v-on:load="onImageLoad"
                                        style="max-height: 300px;"
                                    />
                                    <svg
                                        class="absolute top-0 left-0 w-full h-full pointer-events-none"
                                        v-bind:style="{ height: svgHeight + \'px\' }"
                                    >
                                        <rect
                                            v-for="(area, index) in localAreas"
                                            v-bind:key="\'area-\' + index"
                                            v-bind:x="area.x * scale"
                                            v-bind:y="area.y * scale"
                                            v-bind:width="area.width * scale"
                                            v-bind:height="area.height * scale"
                                            fill="rgba(34, 197, 94, 0.3)"
                                            stroke="green"
                                            stroke-width="2"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <!-- Drawing instructions -->
                            <div v-if="previewImage" class="mb-4 text-sm text-gray-600">
                                <p>Click and drag on the image to draw print areas.</p>
                            </div>

                            <!-- Areas list -->
                            <div v-if="localAreas.length > 0" class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="font-medium">Defined Areas (@{{ localAreas.length }})</p>
                                    <button
                                        type="button"
                                        class="text-xs text-red-500 hover:underline"
                                        v-on:click="deleteAllAreas"
                                    >Delete All</button>
                                </div>
                                <div class="space-y-1">
                                    <div
                                        v-for="(area, index) in localAreas"
                                        v-bind:key="index"
                                        class="flex items-center justify-between bg-gray-100 px-3 py-1 rounded text-sm"
                                    >
                                        <span>Area @{{ index + 1 }}: @{{ Math.round(area.x) }},@{{ Math.round(area.y) }} @{{ Math.round(area.width) }}x@{{ Math.round(area.height) }}</span>
                                        <button
                                            type="button"
                                            class="text-red-500 hover:underline"
                                            v-on:click="deleteArea(index)"
                                        >Delete</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="px-4 py-2 border rounded hover:bg-gray-100"
                                    v-on:click="closeModal"
                                >Cancel</button>
                                <button
                                    v-if="localAreas.length > 0"
                                    type="button"
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-400"
                                    v-bind:disabled="saving"
                                    v-on:click="saveAreas"
                                >@{{ saving ? \'Saving...\' : \'Save \' + localAreas.length + \' Area(s)\' }}</button>
                            </div>
                        </div>
                    </x-slot:content>
                </x-admin::modal>
            `,
            data() {
                return {
                    imagesWithAreas: [],
                    allProductImages: [],
                    isDrawing: false,
                    startX: 0,
                    startY: 0,
                    scale: 1,
                    svgHeight: 300,
                    previewImage: null,
                    selectedImageId: '',
                    localAreas: [],
                    isEditing: false,
                    editingImage: null,
                    saving: false,
                    tempRect: null,
                };
            },
            computed: {
                availableImages() {
                    const usedIds = this.imagesWithAreas.map(img => img.id);
                    return this.allProductImages.filter(img => !usedIds.includes(img.id));
                },
            },
            mounted() {
                // Get data from parent component
                const container = this.\$el.closest(\'.product-edit-form\');
                if (container && window.productImages) {
                    this.allProductImages = window.productImages.map(img => ({
                        id: img.id,
                        url: img.url,
                        path: img.path
                    }));
                }
            },
            methods: {
                openModal() {
                    this.isEditing = false;
                    this.editingImage = null;
                    this.previewImage = null;
                    this.selectedImageId = '';
                    this.localAreas = [];
                    this.\$refs.printAreaModal.open();
                },
                editAreas(image) {
                    this.isEditing = true;
                    this.editingImage = image;
                    this.previewImage = image;
                    this.localAreas = image.areas.map(area => ({
                        x: Number(area.x),
                        y: Number(area.y),
                        width: Number(area.width),
                        height: Number(area.height)
                    }));
                    this.\$refs.printAreaModal.open();
                },
                onImageChange() {
                    const selected = this.allProductImages.find(img => img.id == this.selectedImageId);
                    if (selected) {
                        this.previewImage = selected;
                        this.localAreas = [];
                    }
                },
                onImageLoad(event) {
                    const img = event.target;
                    const maxHeight = 300;
                    this.scale = img.naturalHeight > 0 ? img.clientHeight / img.naturalHeight : 1;
                    this.svgHeight = Math.min(img.clientHeight, maxHeight);
                    this.scale = this.svgHeight / img.naturalHeight;
                },
                startDrawing(event) {
                    const rect = event.target.getBoundingClientRect();
                    this.isDrawing = true;
                    this.startX = event.clientX - rect.left;
                    this.startY = event.clientY - rect.top;
                },
                draw(event) {
                    if (!this.isDrawing) return;
                    const rect = event.target.getBoundingClientRect();
                    const currentX = event.clientX - rect.left;
                    const currentY = event.clientY - rect.top;
                    const x = Math.min(this.startX, currentX) / this.scale;
                    const y = Math.min(this.startY, currentY) / this.scale;
                    const width = Math.abs(currentX - this.startX) / this.scale;
                    const height = Math.abs(currentY - this.startY) / this.scale;
                    this.tempRect = { x, y, width, height };
                },
                stopDrawing(event) {
                    if (!this.isDrawing) return;
                    this.isDrawing = false;
                    if (this.tempRect && this.tempRect.width > 10 && this.tempRect.height > 10) {
                        this.localAreas.push({ ...this.tempRect });
                    }
                    this.tempRect = null;
                },
                deleteArea(index) {
                    this.localAreas.splice(index, 1);
                },
                deleteAllAreas() {
                    this.localAreas = [];
                },
                closeModal() {
                    this.\$refs.printAreaModal.close();
                },
                async saveAreas() {
                    if (this.saving) return;
                    this.saving = true;
                    try {
                        const url = this.isEditing
                            ? \'/admin/catalog/products/print-areas/image/\' + this.editingImage.id
                            : \'/admin/catalog/products/print-areas\';
                        const method = this.isEditing ? \'PUT\' : \'POST\';
                        const body = this.isEditing
                            ? { areas: this.localAreas }
                            : { image_id: this.selectedImageId, areas: this.localAreas };
                        const response = await this.\$axios({
                            method: method,
                            url: url,
                            data: body
                        });
                        if (response.data.success) {
                            window.location.reload();
                        } else {
                            alert(\'Error: \' + response.data.message);
                        }
                    } catch (error) {
                        alert(\'Error saving areas: \' + error.message);
                    } finally {
                        this.saving = false;
                    }
                },
            }
        });
    </script>
@endpushOnce
