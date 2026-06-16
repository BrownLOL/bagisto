<v-media-links :errors="errors">
    <x-admin::shimmer.settings.themes.static-content />
</v-media-links>

<!-- Media Links Vue Component -->
@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-media-links-template"
    >
        <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="mb-2.5 flex items-center justify-between gap-x-2.5">
                    <div class="flex flex-col gap-1">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.settings.themes.edit.media-links')
                        </p>

                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                            @lang('admin::app.settings.themes.edit.media-links-description')
                        </p>
                    </div>

                    <div
                        class="flex gap-2.5"
                        v-if="isHtmlEditorActive"
                    >
                        <!-- Hidden Input Filed for upload images -->
                        <label
                            class="secondary-button"
                            for="media_links_image"
                        >
                            @lang('admin::app.settings.themes.edit.add-image-btn')
                        </label>

                        <input 
                            type="file"
                            name="media_links_image"
                            id="media_links_image"
                            class="hidden"
                            accept="image/*"
                            ref="media_links_image"
                            label="Image"
                            @change="storeImage($event)"
                        >
                    </div>
                </div>
                
                <div class="pt-4 text-center text-sm font-medium text-gray-500">
                    <div class="tabs">
                        <div class="mb-4 flex gap-4 border-b-2 pt-2 max-sm:hidden">
                            <!-- HTML Tab Header -->
                            <p @click="switchEditor('v-media-links-html-editor', 1)">
                                <div
                                    class="cursor-pointer px-2.5 pb-3.5 text-base font-medium text-gray-600 transition dark:text-gray-300"
                                    :class="{'-mb-px border-b-2 border-blue-600': inittialEditor == 'v-media-links-html-editor'}"
                                >
                                    @lang('admin::app.settings.themes.edit.html')
                                </div>
                            </p>

                            <!-- CSS Tab Editor -->
                            <p @click="switchEditor('v-media-links-css-editor', 0);">
                                <div
                                    class="cursor-pointer px-2.5 pb-3.5 text-base font-medium text-gray-600 transition dark:text-gray-300"
                                    :class="{'-mb-px border-b-2 border-blue-600': inittialEditor == 'v-media-links-css-editor'}"
                                >
                                    @lang('admin::app.settings.themes.edit.css')
                                </div>
                            </p>

                            <!-- Preview Tab Editor -->
                            <p @click="switchEditor('v-media-links-previewer', 0);">
                                <div
                                    class="cursor-pointer px-2.5 pb-3.5 text-base font-medium text-gray-600 transition dark:text-gray-300"
                                    :class="{'-mb-px border-b-2 border-blue-600': inittialEditor == 'v-media-links-previewer'}"
                                >
                                    @lang('admin::app.settings.themes.edit.preview')
                                </div>
                            </p>
                        </div>
                    </div>
                </div>

                <input
                    type="hidden"
                    name="{{ $currentLocale->code }}[options][html]"
                    v-model="options.html"
                />

                <input
                    type="hidden"
                    name="{{ $currentLocale->code }}[options][css]"
                    v-model="options.css"
                />

                <KeepAlive class="[&>*]:dark:bg-gray-900 [&>*]:dark:!text-white">
                    <component 
                        :is="inittialEditor"
                        ref="editor"
                        @editor-data="editorData"
                        :options="options"
                    >
                    </component>
                </KeepAlive>
            </div>
        </div>
    </script>

    <!-- Html Editor Template -->
    <script
        type="text/x-template"
        id="v-media-links-html-editor-template"
    >
        <div ref="html"></div>
    </script>

    <!-- Css Editor Template -->
    <script
        type="text/x-template"
        id="v-media-links-css-editor-template"
    >
        <div ref="css"></div>
    </script>

    <!-- Media Links Previewer -->
    <script
        type="text/x-template"
        id="v-media-links-previewer-template"
    >
        <div v-html="getPreviewContent()"></div>
    </script>

    <script type="module">
        app.component('v-media-links', {
            template: '#v-media-links-template',

            props: ['errors'],

            data() {
                return {
                    inittialEditor: 'v-media-links-html-editor',

                    options: @json($theme->translate($currentLocale->code)['options'] ?? null),

                    isHtmlEditorActive: true,
                };
            },

            created() {
                if (this.options === null) {
                    this.options = { html: '', css: '' };
                }
                
                if (this.options.html === undefined || this.options.html === null || typeof this.options.html === 'object') {
                    this.options.html = '';
                }
                
                if (this.options.css === undefined || this.options.css === null) {
                    this.options.css = '';
                }
            },

            mounted() {
                this.applydarkColor();
            },

            methods: {
                switchEditor(editor, isActive) {
                    this.inittialEditor = editor;

                    this.isHtmlEditorActive = isActive;

                    this.$nextTick(() => {
                        this.applydarkColor();

                        if (editor == 'v-media-links-previewer') {
                            this.$refs.editor.review = this.options;
                        }
                    });
                },

                editorData(value) {
                    if (value.html) {
                        this.options.html = value.html;
                    } else {
                        this.options.css = value.css;
                    } 
                },

                storeImage($event) {
                    let imageInput = this.$refs.media_links_image;

                    if (imageInput.files == undefined) {
                        return;
                    }

                    const validFiles = Array.from(imageInput.files).every(file => file.type.includes('image/'));

                    if (! validFiles) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: '@lang('admin::app.settings.themes.edit.image-upload-message')'
                        });

                        imageInput.value = '';

                        return;
                    }

                    imageInput.files.forEach((file, index) => {
                        this.$refs.editor.storeImage($event);
                    });
                },

                applydarkColor() {
                    this.$nextTick(() => {
                        const codeMirrorGutters = this.$el.querySelector('.CodeMirror-gutters');

                        if (codeMirrorGutters) {
                            codeMirrorGutters.classList.add('dark:bg-gray-900', 'dark:!text-white');
                        }
                    });
                },
            },
        });
    </script>

    <!-- Html Editor Component -->
    <script type="module">
        app.component('v-media-links-html-editor', {
            template: '#v-media-links-html-editor-template',
            
            data() {
                return {
                    options:{
                        html: `{!! $theme->translate($currentLocale->code)['options']['html'] ?? '' !!}`,
                    },

                    cursorPointer: {},
                };
            },

            created() {
                this.initHtmlEditor();

                this.$emitter.on('change-theme', (theme) => this._html.setOption('theme', (theme === 'dark') ? 'ayu-dark' : 'default'));
            },

            methods: {
                initHtmlEditor() {
                    this.$nextTick(() => {
                        this.options.html = SimplyBeautiful().html(this.options.html);

                        this._html = new CodeMirror(this.$refs.html, {
                            lineNumbers: true,
                            tabSize: 4,
                            lineWrapping: true,
                            lineWiseCopyCut: true,
                            value: this.options.html,
                            mode: 'htmlmixed',
                            theme: document.documentElement.classList.contains('dark') ? 'ayu-dark' : 'default',
                        });

                        this._html.on('changes', (e) => {
                            this.options.html = this._html.getValue();

                            this.cursorPointer = e.getCursor();

                            this.$emit('editorData', this.options);
                        });
                    });
                },

                storeImage($event) {
                    let selectedImage = $event.target.files[0];

                    if (! selectedImage) {
                        return;
                    }

                    const allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

                    if (! allowedImageTypes.includes(selectedImage.type)) {
                        return;
                    }

                    let formData = new FormData();

                    formData.append('{{ $currentLocale->code }}[options][][image]', selectedImage);
                    formData.append('id', '{{ $theme->id }}');
                    formData.append('type', 'media_links');

                    this.$axios.post('{{ route('admin.settings.themes.store') }}', formData)
                        .then((response) => {
                            let editor = this._html.getDoc();

                            let cursorPointer = editor.getCursor();

                            editor.replaceRange(`<img class="lazy" src="" data-src="${response.data}">`, {
                                line: cursorPointer.line, ch: cursorPointer.ch
                            });

                            editor.setCursor({
                                line: cursorPointer.line, ch: cursorPointer.ch + response.data.length
                            });
                        })
                        .catch((error) => {
                            if (error.response.status == 422) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.message });
                            }
                        });
                },
            },
        });
    </script>
    
    <!-- Css Editor Component -->
    <script type="module">
        app.component('v-media-links-css-editor', {
            template: '#v-media-links-css-editor-template',

            data() {
                return {
                    options:{
                        css: `{!! $theme->translate($currentLocale->code)['options']['css'] ?? '' !!}`,
                    }
                };
            },

            created() {
                this.initCssEditor();

                this.$emitter.on('change-theme', (theme) => this._css.setOption('theme', (theme === 'dark') ? 'ayu-dark' : 'default'));
            },

            methods: {
                initCssEditor() {
                    this.$nextTick(() => {
                        this.options.css = SimplyBeautiful().css(this.options.css);

                        this._css = new CodeMirror(this.$refs.css, {
                            lineNumbers: true,
                            lineWrapping: true,
                            tabSize: 4,
                            lineWiseCopyCut: true,
                            value: this.options.css,
                            mode: 'css',
                            theme: document.documentElement.classList.contains('dark') ? 'ayu-dark' : 'default',
                        });

                        this._css.on('changes', () => {
                            this.options.css = this._css.getValue();

                            this.$emit('editorData', this.options);
                        });
                    });
                },
            },
        });
    </script>
    
    <!-- Media Links Previewer -->
    <script type="module">
        app.component('v-media-links-previewer', {
            template: '#v-media-links-previewer-template',

            props: ['options'],

            methods: {
                getPreviewContent() {
                    let html = this.options.html.slice();

                    html = html.replaceAll('src=""', '').replaceAll('data-src', 'src').replaceAll('src="storage/theme/', "src=\"{{ config('app.url') }}/storage/theme/");

                    return html + '<style type=\"text/css\">' +   this.options.css + '</style>';
                },
            },
        });
    </script>

    <!-- Code mirror script CDN -->
    <script
        type="text/javascript"
        src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.4/codemirror.js"
    >
    </script>

    <!-- 
        Html mixed and xml cnd both are dependent 
        Used for html and css theme
    -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.4/mode/xml/xml.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.4/mode/htmlmixed/htmlmixed.js"></script>

    <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.4/mode/css/css.js"></script>

    <!-- Beatutify html and css -->
    <script src="https://cdn.jsdelivr.net/npm/simply-beautiful@latest/dist/index.min.js"></script>
@endPushOnce

@pushOnce('styles')
    <!-- Code mirror style cdn -->
    <link 
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.13.4/codemirror.css"
    >
    </link>

    <!-- Dark theme css -->
    <link rel="stylesheet" href="https://codemirror.net/5/theme/ayu-dark.css">
@endPushOnce