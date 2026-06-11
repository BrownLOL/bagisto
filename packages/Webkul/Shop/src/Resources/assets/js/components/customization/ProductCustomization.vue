<template>
    <div class="customization-modal" v-if="show" @click.self="closeModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>{{ trans('shop::app.products.customize-title') }}</h3>
                <button class="close-btn" @click="closeModal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="design-area">
                    <div class="canvas-wrapper" ref="canvasWrapper">
                        <img
                            :src="currentImage"
                            class="base-image"
                            ref="baseImage"
                            @load="onImageLoad"
                        />
                        <div
                            v-for="area in printAreas"
                            :key="area.id"
                            class="print-area-mask"
                            :style="getAreaStyle(area)"
                        ></div>
                    </div>
                </div>

                <div class="toolbar">
                    <button class="tool-btn" @click="addText">
                        <span>+T</span> {{ trans('shop::app.products.add-text') }}
                    </button>

                    <label class="tool-btn">
                        <span>+I</span> {{ trans('shop::app.products.add-image') }}
                        <input
                            type="file"
                            accept="image/*"
                            hidden
                            @change="onImageUpload"
                        />
                    </label>

                    <button
                        class="tool-btn preview-btn"
                        @click="showPreview"
                        :disabled="elements.length === 0"
                    >
                        {{ trans('shop::app.products.preview') }}
                    </button>

                    <button
                        class="tool-btn confirm-btn"
                        @click="confirmDesign"
                        :disabled="elements.length === 0"
                    >
                        {{ trans('shop::app.products.confirm') }}
                    </button>
                </div>

                <div class="element-list" v-if="elements.length > 0">
                    <h4>{{ trans('shop::app.products.elements') }}</h4>
                    <div
                        v-for="(el, index) in elements"
                        :key="el.id"
                        class="element-item"
                        :class="{ active: selectedElement?.id === el.id }"
                        @click="selectElement(el)"
                    >
                        <span>{{ el.type === 'text' ? el.text : 'Image ' + index }}</span>
                        <button @click.stop="deleteElement(el.id)">X</button>
                    </div>
                </div>
            </div>

            <!-- Preview Modal -->
            <div class="preview-overlay" v-if="showPreviewModal" @click="showPreviewModal = false">
                <div class="preview-content">
                    <img :src="previewImage" v-if="previewImage" />
                    <button @click="showPreviewModal = false">{{ trans('shop::app.products.close') }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProductCustomization',

    props: {
        show: {
            type: Boolean,
            default: false,
        },
        productId: {
            type: [Number, String],
            required: true,
        },
        printAreas: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            elements: [],
            selectedElement: null,
            currentImage: null,
            baseWidth: 0,
            baseHeight: 0,
            showPreviewModal: false,
            previewImage: null,
            elementCounter: 0,
        };
    },

    mounted() {
        this.loadFirstImage();
    },

    watch: {
        show(val) {
            if (val) {
                this.loadFirstImage();
            }
        },
    },

    methods: {
        loadFirstImage() {
            // 获取商品的第一张图片
            const productImages = window.productImages || [];
            if (productImages.length > 0) {
                this.currentImage = productImages[0].url;
            }
        },

        onImageLoad() {
            if (this.$refs.baseImage) {
                this.baseWidth = this.$refs.baseImage.offsetWidth;
                this.baseHeight = this.$refs.baseImage.offsetHeight;
            }
        },

        getAreaStyle(area) {
            if (!area.print_area) return {};
            const printArea = typeof area.print_area === 'string' ? JSON.parse(area.print_area) : area.print_area;
            return {
                left: printArea.x + '%',
                top: printArea.y + '%',
                width: printArea.width + '%',
                height: printArea.height + '%',
            };
        },

        addText() {
            const text = prompt(this.trans('shop::app.products.enter-text') || 'Enter text:');
            if (text) {
                this.elements.push({
                    id: ++this.elementCounter,
                    type: 'text',
                    text: text,
                    x: 50,
                    y: 50,
                    scaleX: 1,
                    scaleY: 1,
                    rotation: 0,
                });
            }
        },

        onImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.elements.push({
                        id: ++this.elementCounter,
                        type: 'image',
                        src: e.target.result,
                        x: 50,
                        y: 50,
                        scaleX: 0.5,
                        scaleY: 0.5,
                        rotation: 0,
                    });
                };
                reader.readAsDataURL(file);
            }
        },

        selectElement(el) {
            this.selectedElement = el;
        },

        deleteElement(id) {
            this.elements = this.elements.filter(el => el.id !== id);
            if (this.selectedElement?.id === id) {
                this.selectedElement = null;
            }
        },

        async showPreview() {
            // 生成预览图
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            img.crossOrigin = 'anonymous';

            await new Promise((resolve) => {
                img.onload = resolve;
                img.src = this.currentImage;
            });

            canvas.width = img.width;
            canvas.height = img.height;
            ctx.drawImage(img, 0, 0);

            // 绘制元素
            for (const el of this.elements) {
                const x = (el.x / 100) * canvas.width;
                const y = (el.y / 100) * canvas.height;

                if (el.type === 'text') {
                    ctx.save();
                    ctx.translate(x, y);
                    ctx.rotate((el.rotation * Math.PI) / 180);
                    ctx.font = '24px Arial';
                    ctx.fillStyle = '#000';
                    ctx.fillText(el.text, 0, 0);
                    ctx.restore();
                } else if (el.type === 'image') {
                    const elImg = new Image();
                    elImg.crossOrigin = 'anonymous';
                    await new Promise((resolve) => {
                        elImg.onload = resolve;
                        elImg.src = el.src;
                    });
                    ctx.save();
                    ctx.translate(x, y);
                    ctx.rotate((el.rotation * Math.PI) / 180);
                    ctx.drawImage(
                        elImg,
                        -elImg.width * el.scaleX / 2,
                        -elImg.height * el.scaleY / 2,
                        elImg.width * el.scaleX,
                        elImg.height * el.scaleY
                    );
                    ctx.restore();
                }
            }

            this.previewImage = canvas.toDataURL('image/png');
            this.showPreviewModal = true;
        },

        async confirmDesign() {
            // 如果还没有生成预览图，先生成
            if (!this.previewImage) {
                await this.generatePreviewImage();
            }

            // 保存预览图到 localStorage，以便后续添加到购物车时使用
            const designData = {
                elements: this.elements,
                printAreas: this.printAreas,
                previewImage: this.previewImage,
                timestamp: Date.now(),
            };
            localStorage.setItem(`customization_${this.productId}`, JSON.stringify(designData));

            // 触发确认事件，传递设计数据
            this.$emit('confirm', {
                elements: this.elements,
                printAreas: this.printAreas,
                previewImage: this.previewImage,
            });
            this.closeModal();
        },

        async generatePreviewImage() {
            return new Promise(async (resolve) => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const img = new Image();
                img.crossOrigin = 'anonymous';

                await new Promise((res) => {
                    img.onload = res;
                    img.src = this.currentImage;
                });

                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);

                // 绘制元素
                for (const el of this.elements) {
                    const x = (el.x / 100) * canvas.width;
                    const y = (el.y / 100) * canvas.height;

                    if (el.type === 'text') {
                        ctx.save();
                        ctx.translate(x, y);
                        ctx.rotate((el.rotation * Math.PI) / 180);
                        ctx.font = '24px Arial';
                        ctx.fillStyle = '#000';
                        ctx.fillText(el.text, 0, 0);
                        ctx.restore();
                    } else if (el.type === 'image') {
                        const elImg = new Image();
                        elImg.crossOrigin = 'anonymous';
                        await new Promise((res) => {
                            elImg.onload = res;
                            elImg.src = el.content;
                        });
                        ctx.save();
                        ctx.translate(x, y);
                        ctx.rotate((el.rotation * Math.PI) / 180);
                        ctx.drawImage(
                            elImg,
                            -elImg.width * el.scaleX / 2,
                            -elImg.height * el.scaleY / 2,
                            elImg.width * el.scaleX,
                            elImg.height * el.scaleY
                        );
                        ctx.restore();
                    }
                }

                this.previewImage = canvas.toDataURL('image/png');
                resolve();
            });
        },

        closeModal() {
            this.$emit('close');
        },

        trans(key) {
            return window.translations?.[key] || key;
        },
    },
};
</script>

<style scoped>
.customization-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-container {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.modal-header h3 {
    margin: 0;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
}

.design-area {
    margin-bottom: 20px;
}

.canvas-wrapper {
    position: relative;
    display: inline-block;
    max-width: 100%;
}

.base-image {
    max-width: 100%;
    display: block;
}

.print-area-mask {
    position: absolute;
    border: 2px dashed #ff6b00;
    background: rgba(255, 107, 0, 0.1);
    pointer-events: none;
}

.toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.tool-btn {
    padding: 10px 20px;
    border: 1px solid #ddd;
    background: #f5f5f5;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.tool-btn:hover {
    background: #eee;
}

.tool-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.preview-btn {
    background: #4caf50;
    color: white;
    border-color: #4caf50;
}

.confirm-btn {
    background: #2196f3;
    color: white;
    border-color: #2196f3;
}

.element-list {
    border-top: 1px solid #eee;
    padding-top: 15px;
}

.element-list h4 {
    margin: 0 0 10px 0;
}

.element-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f9f9f9;
    border-radius: 4px;
    margin-bottom: 5px;
    cursor: pointer;
}

.element-item.active {
    background: #e3f2fd;
}

.element-item button {
    background: #f44336;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 2px 8px;
    cursor: pointer;
}

.preview-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.preview-content {
    text-align: center;
}

.preview-content img {
    max-width: 90%;
    max-height: 80vh;
    border: 2px solid white;
}

.preview-content button {
    margin-top: 20px;
    padding: 10px 30px;
    background: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
</style>
