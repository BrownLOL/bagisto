<v-product-customization></v-product-customization>

@php
    $productImages = $product->images->map(function($img) {
        return [
            'id' => $img->id,
            'path' => $img->path,
            'url' => url('storage/' . $img->path)
        ];
    })->toArray();

    // 直接通过 product_id 查询 print_areas，使用存储的 image_url
    $printAreas = \Webkul\Product\Models\ProductImagePrintArea::where('product_id', $product->id)->where('is_active', true)->get();

    $imagesWithAreas = $printAreas->map(function($area) {
        return [
            'id' => 'record_' . $area->id,
            'image_url' => $area->image_url,
            'areas' => [[
                'id' => $area->id,
                'x' => $area->x,
                'y' => $area->y,
                'width' => $area->width,
                'height' => $area->height,
            ]],
        ];
    })->toArray();
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
                <div v-if="imagesWithAreas.length === 0" class="rounded bg-gray-50 p-4 text-center text-sm text-gray-500 dark:bg-gray-800">
                    No print areas defined yet. Click "Add Print Area" to create one.
                </div>

                <div v-else class="flex flex-wrap gap-1 p-4">
                    <div
                        v-for="(imageData, index) in imagesWithAreas"
                        :key="index"
                        class="group relative grid max-h-[120px] min-w-[120px] justify-items-center overflow-hidden rounded border border-gray-200 transition-all hover:border-gray-400 dark:border-gray-800"
                    >
                        <!-- Image -->
                        <img
                            :src="imageData.image_url || imageData.url"
                            :alt="'Image ' + index"
                            class="h-[120px] w-[120px] object-cover"
                        >
                        
                        <!-- Area count badge -->
                        <span class="absolute left-1 top-1 rounded-full bg-green-500 px-1.5 py-0.5 text-xs font-bold text-white">
                            @{{ imageData.areas.length }}
                        </span>

                        <!-- Overlay with actions -->
                        <div class="invisible absolute bottom-0 top-0 flex w-full flex-col justify-between bg-white p-3 opacity-80 transition-all group-hover:visible dark:bg-gray-900">
                            <!-- Area count -->
                            <p class="text-center text-xs font-semibold text-gray-600 dark:text-gray-300">
                                @{{ imageData.areas.length }} area(s)
                            </p>

                            <!-- Actions -->
                            <div class="flex justify-between">
                                <span
                                    class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl hover:bg-gray-200 dark:hover:bg-gray-800"
                                    @click.stop="deleteAreaImage(index)"
                                ></span>

                                <span
                                    class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl hover:bg-gray-200 dark:hover:bg-gray-800"
                                    @click.stop="openEditDialog(imageData, index)"
                                ></span>
                            </div>
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
                                    v-for="(image, index) in availableImages"
                                    :key="image.id"
                                    :value="image.id"
                                >
                                    Image @{{ index + 1 }}@{{ image.is_new ? ' (new)' : '' }}
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
                            Delete
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
        
        <!-- Hidden input to store customization areas for form submission -->
        <input
            type="hidden"
            name="customization_areas"
            id="customization_areas_input"
            :value="JSON.stringify(imagesWithAreas)"
        />
        </div>
    </script>

    <script type="module">
        // Simple hash function for blob keys
        function simpleHash(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return Math.abs(hash).toString(36);
        }
        
        app.component('v-product-customization', {
            template: '#v-product-customization-template',

            data() {
                return {
                    productId: {{ $product->id ?? 0 }},
                    allImages: {!! json_encode($productImages ?? [], JSON_UNESCAPED_SLASHES) !!},
                    initialProductImages: {!! json_encode($productImages ?? [], JSON_UNESCAPED_SLASHES) !!},
                    imagesWithAreas: {!! json_encode($imagesWithAreas ?? [], JSON_UNESCAPED_SLASHES) !!},
                    dialogSelectedImageId: '',
                    dialogSelectedImageUrl: '',
                    originalImageId: null,
                    editingKey: null, // 当前编辑的记录索引
                    isEditMode: false,
                    imageLoaded: false,
                    isDrawing: false,
                    tempRect: null,
                    tempAreas: [],
                    drawStartX: 0,
                    drawStartY: 0,
                    saving: false,
                    blobFiles: {}, // Store blob files for later upload
                    removedAreas: [], // 已删除的区域（用于过滤图片）
                }
            },

            mounted() {
                console.log('ProductCustomization component mounted');
                console.log('productId:', this.productId);
                console.log('allImages:', this.allImages);
                console.log('imagesWithAreas:', this.imagesWithAreas);
            },

            errorCaptured(error) {
                console.error('ProductCustomization error:', error);
                console.error('Error stack:', error.stack);
                return false;
            },

            computed: {
                availableImages() {
                    // 合并两个来源的图片：新上传的 + 已保存的商品图片
                    const images = [];
                    const seenUrls = new Set();
                    
                    // 先添加已保存的商品图片（PHP传递的）
                    this.initialProductImages.forEach(img => {
                        if (img.url && !seenUrls.has(img.url)) {
                            images.push(img);
                            seenUrls.add(img.url);
                        }
                    });
                    
                    // 再添加新上传的图片（从 window.customizationData）
                    if (window.customizationData && window.customizationData.images) {
                        window.customizationData.images.forEach(img => {
                            if (img.url && !seenUrls.has(img.url)) {
                                images.push(img);
                                seenUrls.add(img.url);
                            }
                        });
                    }
                    
                    // 过滤掉已被 print area 使用的图片（用于已选图片的回退判断）
                    return images.filter(img => 
                        !this.removedAreas.some(ra => ra.imageUrl === img.url)
                    );
                }
            },

            methods: {
                openAddDialog() {
                    // 合并图片列表：新上传的 + 已有的
                    const images = [];
                    const seenUrls = new Set();
                    
                    // 添加新上传的图片（从 window.customizationData）
                    if (window.customizationData && window.customizationData.images) {
                        window.customizationData.images.forEach(img => {
                            if (img.url && !seenUrls.has(img.url)) {
                                images.push(img);
                                seenUrls.add(img.url);
                            }
                        });
                    }
                    
                    // 添加已保存的商品图片（避免重复）
                    this.initialProductImages.forEach(img => {
                        if (img.url && !seenUrls.has(img.url)) {
                            images.push(img);
                            seenUrls.add(img.url);
                        }
                    });
                    
                    // 如果都没有，回退到初始图片
                    if (images.length === 0) {
                        this.allImages = this.initialProductImages.slice();
                    } else {
                        this.allImages = images;
                    }

                    this.isEditMode = false;
                    this.editingKey = null;
                    this.originalImageId = null;
                    this.dialogSelectedImageId = '';
                    this.dialogSelectedImageUrl = '';
                    this.tempAreas = [];
                    this.imageLoaded = false;
                    
                    // Wait for image uploads to complete
                    this.$nextTick(() => {
                        this.$refs.printAreaModal.open();
                    });
                },

                openEditDialog(imageData, index) {
                    this.isEditMode = true;
                    this.editingKey = index;
                    this.originalImageId = imageData.id;
                    this.dialogSelectedImageUrl = imageData.image_url || imageData.url;
                    this.tempAreas = JSON.parse(JSON.stringify(imageData.areas));
                    this.imageLoaded = true;
                    this.$refs.printAreaModal.open();
                },

                closeDialog() {
                    this.$refs.printAreaModal.close();
                    this.dialogSelectedImageId = '';
                    this.dialogSelectedImageUrl = '';
                    this.originalImageId = null;
                    this.editingKey = null;
                    this.isEditMode = false;
                    this.tempRect = null;
                    this.tempAreas = [];
                    this.imageLoaded = false;
                },

                deleteAreaImage(index) {
                    const record = this.imagesWithAreas[index];
                    if (!record) return;

                    if (!confirm('Delete this print area record?')) {
                        return;
                    }

                    // 如果是已有的数据库记录，调用后端删除
                    if (record.id && !String(record.id).startsWith('new_')) {
                        this.$axios.delete(`/admin/catalog/products/print-areas/${record.id}`)
                            .catch(error => {
                                console.error('Error deleting areas:', error);
                            });
                    }

                    // 从列表中移除
                    this.imagesWithAreas.splice(index, 1);
                },

                onDialogImageChange() {
                    this.tempAreas = [];
                    
                    // 首先从 window.customizationData.images 获取（实时上传的图片）
                    if (window.customizationData && window.customizationData.images.length > 0) {
                        const image = window.customizationData.images.find(img => img.id == this.dialogSelectedImageId);
                        if (image && image.url) {
                            this.dialogSelectedImageUrl = image.url;
                            return;
                        }
                    }
                    
                    // 回退到 allImages（数据库中的图片）
                    const image = this.allImages.find(img => img.id == this.dialogSelectedImageId);
                    if (image && image.url) {
                        this.dialogSelectedImageUrl = image.url;
                        return;
                    }
                    
                    this.dialogSelectedImageUrl = '';
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

                    // 只能有一个区域，画新区域会替换旧区域
                    if (width > 1 && height > 1) {
                        this.tempAreas = [{ x, y, width, height }];
                    }

                    this.tempRect = null;
                    this.isDrawing = false;
                },

                removeTempArea(index) {
                    this.tempAreas.splice(index, 1);
                },

                saveAreas() {
                    if (this.tempAreas.length === 0 || !this.dialogSelectedImageUrl || this.saving) return;

                    this.saving = true;

                    try {
                        let imageUrl = this.dialogSelectedImageUrl;
                        let imageId = this.dialogSelectedImageId;

                        // Create record
                        console.log('DEBUG saveAreas:', {
                            imageUrlType: imageUrl.startsWith('data:') ? 'base64' : 'url',
                            imageUrlLength: imageUrl.length
                        });
                        
                        const record = {
                            temp_id: 'new_' + Date.now(),
                            image_id: imageId,
                            image_url: imageUrl,
                            // 直接存储 base64 数据供后端上传
                            image_base64: imageUrl.startsWith('data:') ? imageUrl : null,
                            areas: this.tempAreas.map((area, index) => ({
                                name: 'Area ' + (index + 1),
                                x: area.x,
                                y: area.y,
                                width: area.width,
                                height: area.height
                            }))
                        };
                        
                        // 标记图片已被使用，防止被删除
                        if (window.customizationData) {
                            if (!window.customizationData.usedUrls) {
                                window.customizationData.usedUrls = [];
                            }
                            if (!window.customizationData.usedUrls.includes(imageUrl)) {
                                window.customizationData.usedUrls.push(imageUrl);
                            }
                        }

                        if (this.isEditMode && this.originalImageId) {
                            // Edit mode: update existing record
                            const editKey = this.editingKey;
                            const index = this.imagesWithAreas.findIndex((_, idx) => idx === editKey);
                            if (index !== -1) {
                                this.imagesWithAreas[index].image_url = imageUrl;
                                this.imagesWithAreas[index].image_id = imageId;
                                this.imagesWithAreas[index].areas = record.areas;
                            }
                        } else {
                            // Add mode: add new record
                            this.imagesWithAreas.push(record);
                        }

                        this.closeDialog();
                    } catch (error) {
                        alert('Error saving areas: ' + error.message);
                    } finally {
                        this.saving = false;
                    }
                },

                deleteAllAreas() {
                    this.tempAreas = [];
                },
            }
        });
    </script>
@endpushOnce
