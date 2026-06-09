@inject ('reviewHelper', 'Webkul\Product\Helpers\Review')
@inject ('productViewHelper', 'Webkul\Product\Helpers\View')

@php
    $avgRatings = $reviewHelper->getAverageRating($product);

    $percentageRatings = $reviewHelper->getPercentageRating($product);

    $customAttributeValues = $productViewHelper->getAdditionalData($product);

    $attributeData = collect($customAttributeValues)->filter(fn ($item) => ! empty($item['value']));
@endphp

<!-- Product ID for customization -->
<div id="customization-product-id" data-id="{{ $product->id ?? 0 }}" class="hidden"></div>

<!-- SEO Meta Content -->
@push('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="description" content="{{ trim($product->meta_description) != "" ? $product->meta_description : \Illuminate\Support\Str::limit(strip_tags($product->description), 120, '') }}"/>

    <meta name="keywords" content="{{ $product->meta_keywords }}"/>

    @if (core()->getConfigData('catalog.rich_snippets.products.enable'))
        <script type="application/ld+json">
            {!! app('Webkul\Product\Helpers\SEO')->getProductJsonLd($product) !!}
        </script>
    @endif

    <?php $productBaseImage = product_image()->getProductBaseImage($product); ?>

    <meta name="twitter:card" content="summary_large_image" />

    <meta name="twitter:title" content="{{ $product->name }}" />

    <meta name="twitter:description" content="{!! htmlspecialchars(trim(strip_tags($product->description))) !!}" />

    <meta name="twitter:image:alt" content="" />

    <meta name="twitter:image" content="{{ $productBaseImage['medium_image_url'] }}" />

    <meta property="og:type" content="og:product" />

    <meta property="og:title" content="{{ $product->name }}" />

    <meta property="og:image" content="{{ $productBaseImage['medium_image_url'] }}" />

    <meta property="og:description" content="{!! htmlspecialchars(trim(strip_tags($product->description))) !!}" />

    <meta property="og:url" content="{{ route('shop.product_or_category.index', $product->url_key) }}" />
@endpush

<!-- Page Layout -->
<x-shop::layouts>
    <!-- Page Title -->
    <x-slot:title>
        {{ trim($product->meta_title) != "" ? $product->meta_title : $product->name }}
    </x-slot>

    {!! view_render_event('bagisto.shop.products.view.before', ['product' => $product]) !!}

    <!-- Breadcrumbs -->
    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        <div class="flex justify-center px-7 max-lg:hidden">
            <x-shop::breadcrumbs
                name="product"
                :entity="$product"
            />
        </div>
    @endif

    <!-- Product Information Vue Component -->
    <v-product>
        <x-shop::shimmer.products.view />
    </v-product>

<!-- Customization Dialog - Outside Vue component for proper z-index -->
<div id="customization-dialog" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto" style="z-index: 99999;">
    <div class="bg-white rounded-lg shadow-xl mx-auto p-4 flex flex-col" style="width: 1200px; height: 700px; margin-top: 100px;">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Custom Design</h2>
            <button id="close-dialog" onclick="closeDialog()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        
        <div class="flex gap-4 flex-1 min-h-0">
            <!-- Left Panel - 4 Tabs -->
            <div style="width: 300px; flex-shrink: 0;" class="border-r pr-4 flex flex-col">
                <div class="flex gap-1 mb-4 justify-between">
                    <button class="tab-btn flex-1 p-2 rounded hover:bg-gray-200 bg-blue-100 text-blue-600" data-tab="product" title="Product">
                        <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 24 24"><path d="M6 2l.2.6L4 6l3 1.5V22h10V7.5L20 6l-2.2-3.4.2-.6H6zm1 4h10l1.5 2H5.5l1.5-2zM7 10v10H5V10h2zm12 0v10h-2V10h2z"/></svg>
                    </button>
                    <button class="tab-btn flex-1 p-2 rounded hover:bg-gray-200 text-gray-500" data-tab="image" title="Image">
                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    <button class="tab-btn flex-1 p-2 rounded hover:bg-gray-200 text-gray-500" data-tab="text" title="Text">
                        <span class="text-lg font-bold">T</span>
                    </button>
                    <button class="tab-btn flex-1 p-2 rounded hover:bg-gray-200 text-gray-500" data-tab="layers" title="Layers">
                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </button>
                </div>
                
                <div id="tab-product" class="tab-content flex-1 overflow-auto">
                    <div id="product-images" class="grid grid-cols-2 gap-2"></div>
                </div>
                
                <div id="tab-image" class="tab-content hidden flex-1">
                    <input type="file" id="image-upload" accept="image/*" class="w-full border rounded p-2 mb-2" />
                    <div id="uploaded-images" class="grid grid-cols-2 gap-2 overflow-auto flex-1"></div>
                </div>
                
                <div id="tab-text" class="tab-content hidden flex-1 flex flex-col gap-3 p-2">
                    <!-- Text Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Input Text</label>
                        <textarea id="text-input" placeholder="Enter text here..." class="w-full border border-gray-300 rounded-lg p-2 h-20 resize-none"></textarea>
                    </div>
                    
                    <!-- Font Size -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Font Size: <span id="font-size-label">24px</span></label>
                        <input type="range" id="font-size" value="24" min="12" max="72" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" />
                    </div>
                    
                    <!-- Color -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Text Color</label>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" onclick="setTextColor('#000000')" class="color-swatch w-8 h-8 rounded border-2 border-gray-300 !bg-black" data-color="#000000" style="background-color: #000000;"></button>
                            <button type="button" onclick="setTextColor('#FFFFFF')" class="color-swatch w-8 h-8 rounded border-2 border-gray-300 !bg-white" data-color="#FFFFFF" style="background-color: #FFFFFF;"></button>
                            <button type="button" onclick="setTextColor('#FF0000')" class="color-swatch w-8 h-8 rounded border-2 border-gray-300 !bg-red-500" data-color="#FF0000" style="background-color: #FF0000;"></button>
                            <button type="button" onclick="setTextColor('#00FF00')" class="color-swatch w-8 h-8 rounded border-2 border-gray-300 !bg-green-500" data-color="#00FF00" style="background-color: #00FF00;"></button>
                            <button type="button" onclick="setTextColor('#0000FF')" class="color-swatch w-8 h-8 rounded border-2 border-gray-300 !bg-blue-500" data-color="#0000FF" style="background-color: #0000FF;"></button>
                            <button type="button" onclick="setTextColor('#FFFF00')" class="color-swatch w-8 h-8 rounded border-2 border-gray-300 !bg-yellow-500" data-color="#FFFF00" style="background-color: #FFFF00;"></button>
                            <button type="button" onclick="setTextColor('#FF00FF')" class="color-swatch w-8 h-8 rounded border-2 border-gray-300 !bg-pink-500" data-color="#FF00FF" style="background-color: #FF00FF;"></button>
                            <button type="button" onclick="setTextColor('#00FFFF')" class="color-swatch w-8 h-8 rounded border-2 border-gray-300 !bg-cyan-500" data-color="#00FFFF" style="background-color: #00FFFF;"></button>
                            <input type="color" id="text-color-custom" value="#000000" class="w-8 h-8 rounded cursor-pointer border-2 border-gray-300" onchange="setTextColor(this.value)" style="padding: 0; background: linear-gradient(135deg, #ff0000, #ff8800, #ffff00, #00ff00, #00ffff, #0000ff, #8800ff, #ff00ff);" />
                        </div>
                    </div>
                    
                    <!-- Font -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Font</label>
                        <select id="text-font" class="w-full border border-gray-300 rounded-lg p-2">
                            <option value="Noto Sans TC, sans-serif">Noto Sans TC (系統預設)</option>
                            <option value="Microsoft JhengHei, sans-serif">微軟正黑體</option>
                            <option value="PingFang TC, sans-serif">蘋果麗黑體</option>
                            <option value="Heiti TC, sans-serif">黑體-繁</option>
                            <option value="Arial, sans-serif">Arial</option>
                            <option value="Times New Roman, serif">Times New Roman</option>
                            <option value="Georgia, serif">Georgia</option>
                        </select>
                    </div>
                    
                    <button onclick="addText()" class="secondary-button mx-auto">Add Text</button>
                </div>

                <div id="tab-layers" class="tab-content hidden flex-1 flex flex-col">
                    <div id="layers-list" class="flex-1 overflow-auto"></div>
                </div>
            </div>
            
            <!-- Center - Design Canvas -->
            <div class="flex flex-col gap-2">
                <div id="design-canvas-wrapper" style="width: 500px; height: 500px;" class="border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 overflow-hidden relative">
                    <div id="design-canvas-inner" class="w-full h-full absolute" style="transform-origin: center center;"></div>
                </div>
                <!-- Zoom controls -->
                <div class="flex justify-center gap-2">
                    <button onclick="zoomOut()" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 text-sm">-</button>
                    <span id="zoom-level" class="px-3 py-1 bg-gray-100 border border-gray-300 rounded text-sm">100%</span>
                    <button onclick="zoomIn()" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 text-sm">+</button>
                    <button onclick="resetZoom()" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 text-sm" title="Reset">⟲</button>
                </div>
            </div>
            
            <!-- Right Panel - Settings with Icons -->
            <div style="width: 300px; flex-shrink: 0;" class="flex flex-col">
                <h3 class="font-semibold mb-2 text-sm">Settings</h3>
                <div class="flex flex-col gap-2">
                    <!-- Maximize - full width -->
                    <div class="col-span-2">
                        <button id="btn-maximize" onclick="maximizeElement()" disabled class="w-full bg-white border border-gray-300 p-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:border-gray-400 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                            
                        </button>
                    </div>
                    
                    <!-- Flip Horizontal & Flip Vertical -->
                    <div class="flex gap-2">
                        <button id="btn-flip-h" onclick="flipHorizontal()" disabled class="flex-1 bg-white border border-gray-300 p-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:border-gray-400 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            
                        </button>
                        <button id="btn-flip-v" onclick="flipVertical()" disabled class="flex-1 bg-white border border-gray-300 p-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:border-gray-400 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16h12M7 16l4-4m-4-4l4 4m-8-8l-4 4m4-4l-4-4m0 12h12"/></svg>
                            
                        </button>
                    </div>
                    
                    <!-- Rotate Left & Rotate Right -->
                    <div class="flex gap-2">
                        <button id="btn-rotate-l" onclick="rotateLeft()" disabled class="flex-1 bg-white border border-gray-300 p-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:border-gray-400 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            
                        </button>
                        <button id="btn-rotate-r" onclick="rotateRight()" disabled class="flex-1 bg-white border border-gray-300 p-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:border-gray-400 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m0 0a8.001 8.001 0 0115.356 2M4.582 9H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            
                        </button>
                    </div>
                    
                    <!-- Move Up & Move Down -->
                    <div class="flex gap-2">
                        <button id="btn-forward" onclick="bringForward()" disabled class="flex-1 bg-white border border-gray-300 p-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:border-gray-400 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            
                        </button>
                        <button id="btn-backward" onclick="sendBackward()" disabled class="flex-1 bg-white border border-gray-300 p-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:border-gray-400 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            
                        </button>
                    </div>
                    
                    <!-- Delete - full width red -->
                    <div class="col-span-2">
                        <button id="btn-delete" onclick="deleteElement()" disabled class="w-full bg-red-50 border border-red-300 p-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-100 hover:border-red-400 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <h3 class="font-semibold mt-4 mb-2 text-sm">Preview</h3>
                <div id="preview" class="flex-1 border rounded bg-gray-50 overflow-hidden flex items-center justify-center">
                    <div id="preview-canvas" style="width: 250px; height: 250px;"></div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end gap-2 mt-4 pt-4 border-t">
            <button id="cancel-btn" onclick="closeDialog()" class="px-4 py-2 border rounded hover:bg-gray-100">Cancel</button>
            <button id="clear-design-btn" onclick="clearSavedDesign()" class="px-4 py-2 border border-orange-300 rounded hover:bg-orange-50 text-orange-600">Clear Design</button>
            <button id="save-customization-btn" onclick="saveCustomization()" class="px-4 py-2 bg-blue-600 border rounded hover:bg-blue-700">Save</button>
        </div>
    </div>
</div>

    <!-- Information Section -->
    <div class="1180:mt-20">
        <div class="max-1180:hidden">
            <x-shop::tabs
                position="center"
                ref="productTabs"
            >
                <!-- Description Tab -->
                {!! view_render_event('bagisto.shop.products.view.description.before', ['product' => $product]) !!}

                <x-shop::tabs.item
                    id="descritpion-tab"
                    class="container mt-[60px] !p-0"
                    :title="trans('shop::app.products.view.description')"
                    :is-selected="true"
                >
                    <div class="container mt-[60px] max-1180:px-5">
                        <p class="text-lg text-zinc-500 max-1180:text-sm">
                            {!! $product->description !!}
                        </p>
                    </div>
                </x-shop::tabs.item>

                {!! view_render_event('bagisto.shop.products.view.description.after', ['product' => $product]) !!}

                <!-- Additional Information Tab -->
                @if(count($attributeData))
                    <x-shop::tabs.item
                        id="information-tab"
                        class="container mt-[60px] !p-0"
                        :title="trans('shop::app.products.view.additional-information')"
                        :is-selected="false"
                    >
                        <div class="container mt-[60px] max-1180:px-5">
                            <div class="mt-8 grid max-w-max grid-cols-[auto_1fr] gap-4">
                                @foreach ($customAttributeValues as $customAttributeValue)
                                    @if (! empty($customAttributeValue['value']))
                                        <div class="grid">
                                            <p class="text-base text-black">
                                                {!! $customAttributeValue['label'] !!}
                                            </p>
                                        </div>

                                        @if ($customAttributeValue['type'] == 'file')
                                            <a
                                                href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                                download="{{ $customAttributeValue['label'] }}"
                                            >
                                                <span class="icon-download text-2xl"></span>
                                            </a>
                                        @elseif ($customAttributeValue['type'] == 'image')
                                            <a
                                                href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                                download="{{ $customAttributeValue['label'] }}"
                                            >
                                                <img
                                                    class="min-h-5 min-w-5 h-5 w-5"
                                                    src="{{ Storage::url($customAttributeValue['value']) }}"
                                                />
                                            </a>
                                        @else
                                            <div class="grid">
                                                <p class="text-base text-zinc-500">
                                                    {!! $customAttributeValue['value'] !!}
                                                </p>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </x-shop::tabs.item>
                @endif

                <!-- Reviews Tab -->
                <x-shop::tabs.item
                    id="review-tab"
                    class="container mt-[60px] !p-0"
                    :title="trans('shop::app.products.view.review')"
                    :is-selected="false"
                >
                    @include('shop::products.view.reviews')
                </x-shop::tabs.item>
            </x-shop::tabs>
        </div>
    </div>

    <!-- Information Section -->
    <div class="container mt-6 grid gap-3 !p-0 max-1180:px-5 1180:hidden">
        <!-- Description Accordion -->
        <x-shop::accordion
            class="max-md:border-none"
            :is-active="true"
        >
            <x-slot:header class="bg-gray-100 max-md:!py-3 max-sm:!py-2">
                <p class="text-base font-medium 1180:hidden">
                    @lang('shop::app.products.view.description')
                </p>
            </x-slot>

            <x-slot:content class="max-sm:px-0">
                <div class="mb-5 text-lg text-zinc-500 max-1180:text-sm max-md:mb-1 max-md:px-4">
                    {!! $product->description !!}
                </div>
            </x-slot>
        </x-shop::accordion>

        <!-- Additional Information Accordion -->
        @if (count($attributeData))
            <x-shop::accordion
                class="max-md:border-none"
                :is-active="false"
            >
                <x-slot:header class="bg-gray-100 max-md:!py-3 max-sm:!py-2">
                    <p class="text-base font-medium 1180:hidden">
                        @lang('shop::app.products.view.additional-information')
                    </p>
                </x-slot>

                <x-slot:content class="max-sm:px-0">
                    <div class="container max-1180:px-5">
                        <div class="grid max-w-max grid-cols-[auto_1fr] gap-4 text-lg text-zinc-500 max-1180:text-sm">
                            @foreach ($customAttributeValues as $customAttributeValue)
                                @if (! empty($customAttributeValue['value']))
                                    <div class="grid">
                                        <p
                                            class="text-base text-black"
                                            v-pre
                                        >
                                            {{ $customAttributeValue['label'] }}
                                        </p>
                                    </div>

                                    @if ($customAttributeValue['type'] == 'file')
                                        <a
                                            href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                            download="{{ $customAttributeValue['label'] }}"
                                        >
                                            <span class="icon-download text-2xl"></span>
                                        </a>
                                    @elseif ($customAttributeValue['type'] == 'image')
                                        <a
                                            href="{{ Storage::url($product[$customAttributeValue['code']]) }}"
                                            download="{{ $customAttributeValue['label'] }}"
                                        >
                                            <img
                                                class="min-h-5 min-w-5 h-5 w-5"
                                                src="{{ Storage::url($customAttributeValue['value']) }}"
                                                alt="Product Image"
                                            />
                                        </a>
                                    @else
                                        <div class="grid">
                                            <p
                                                class="text-base text-zinc-500"
                                                v-pre
                                            >
                                                {{ $customAttributeValue['value'] ?? '-' }}
                                            </p>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                </x-slot>
            </x-shop::accordion>
        @endif

        <!-- Reviews Accordion -->
        <x-shop::accordion
            class="max-md:border-none"
            :is-active="false"
        >
            <x-slot:header
                class="bg-gray-100 max-md:!py-3 max-sm:!py-2"
                id="review-accordian-button"
            >
                <p class="text-base font-medium">
                    @lang('shop::app.products.view.review')
                </p>
            </x-slot>

            <x-slot:content>
                @include('shop::products.view.reviews')
            </x-slot>
        </x-shop::accordion>
    </div>

    <v-product-associations></v-product-associations>

    {!! view_render_event('bagisto.shop.products.view.after', ['product' => $product]) !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-product-template"
        >
            <x-shop::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form
                    ref="formData"
                    @submit="handleSubmit($event, addToCart)"
                >
                    <input
                        type="hidden"
                        name="product_id"
                        value="{{ $product->id }}"
                    >

                    <input
                        type="hidden"
                        name="is_buy_now"
                        v-model="is_buy_now"
                    >

                    <div class="container px-[60px] max-1180:px-0">
                        <div class="mt-12 flex gap-9 max-1180:flex-wrap max-lg:mt-0 max-sm:gap-y-4">
                            <!-- Gallery Blade Inclusion -->
                            @include('shop::products.view.gallery')

                            <!-- Details -->
                            <div class="relative max-w-[590px] max-1180:w-full max-1180:max-w-full max-1180:px-5 max-sm:px-4">
                                {!! view_render_event('bagisto.shop.products.name.before', ['product' => $product]) !!}

                                <div class="flex justify-between gap-4">
                                    <h1 class="break-words text-3xl font-medium max-sm:text-xl" v-pre>
                                        {{ $product->name }}
                                    </h1>

                                    @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                                        <div
                                            class="max-sm:min-h-7 max-sm:min-w-7 flex max-h-[46px] min-h-[46px] min-w-[46px] cursor-pointer items-center justify-center rounded-full border bg-white text-2xl transition-all hover:opacity-[0.8] max-sm:max-h-7 max-sm:text-base"
                                            role="button"
                                            aria-label="@lang('shop::app.products.view.add-to-wishlist')"
                                            tabindex="0"
                                            :class="isWishlist ? 'icon-heart-fill text-red-600' : 'icon-heart'"
                                            @click="addToWishlist"
                                        >
                                        </div>
                                    @endif
                                </div>

                                {!! view_render_event('bagisto.shop.products.name.after', ['product' => $product]) !!}

                                <!-- Rating -->
                                {!! view_render_event('bagisto.shop.products.rating.before', ['product' => $product]) !!}

                                @if ($totalRatings = $reviewHelper->getTotalFeedback($product))
                                    <!-- Scroll To Reviews Section and Activate Reviews Tab -->
                                    <div
                                        class="mt-1 w-max cursor-pointer max-sm:mt-1.5"
                                        role="button"
                                        tabindex="0"
                                        @click="scrollToReview"
                                    >
                                        <x-shop::products.ratings
                                            class="transition-all hover:border-gray-400 max-sm:px-3 max-sm:py-1"
                                            :average="$avgRatings"
                                            :total="$totalRatings"
                                            ::rating="true"
                                        />
                                    </div>
                                @endif

                                {!! view_render_event('bagisto.shop.products.rating.after', ['product' => $product]) !!}

                                <!-- Pricing -->
                                {!! view_render_event('bagisto.shop.products.price.before', ['product' => $product]) !!}

                                <p class="mt-[22px] flex items-center gap-2.5 text-2xl !font-medium max-sm:mt-2 max-sm:gap-x-2.5 max-sm:gap-y-0 max-sm:text-lg">
                                    {!! $product->getTypeInstance()->getPriceHtml() !!}
                                </p>

                                @if (\Webkul\Tax\Facades\Tax::isInclusiveTaxProductPrices())
                                    <span class="text-sm font-normal text-zinc-500 max-sm:text-xs">
                                        (@lang('shop::app.products.view.tax-inclusive'))
                                    </span>
                                @endif

                                @if (count($product->getTypeInstance()->getCustomerGroupPricingOffers()))
                                    <div class="mt-2.5 grid gap-1.5">
                                        @foreach ($product->getTypeInstance()->getCustomerGroupPricingOffers() as $offer)
                                            <p class="text-zinc-500 [&>*]:text-black">
                                                {!! $offer !!}
                                            </p>
                                        @endforeach
                                    </div>
                                @endif

                                {!! view_render_event('bagisto.shop.products.price.after', ['product' => $product]) !!}

                                {!! view_render_event('bagisto.shop.products.short_description.before', ['product' => $product]) !!}

                                <p class="mt-6 text-lg text-zinc-500 max-sm:mt-1.5 max-sm:text-sm">
                                    {!! $product->short_description !!}
                                </p>

                                {!! view_render_event('bagisto.shop.products.short_description.after', ['product' => $product]) !!}

                                @include('shop::products.view.types.simple')

                                @include('shop::products.view.types.configurable')

                                @include('shop::products.view.types.grouped')

                                @include('shop::products.view.types.bundle')

                                @include('shop::products.view.types.downloadable')

                                @include('shop::products.view.types.booking')

                                <!-- Product Actions and Quantity Box -->
                                <div class="mt-8 flex max-w-[470px] gap-4 max-sm:mt-4">

                                    {!! view_render_event('bagisto.shop.products.view.quantity.before', ['product' => $product]) !!}

                                    @if ($product->getTypeInstance()->showQuantityBox())
                                        <x-shop::quantity-changer
                                            name="quantity"
                                            value="1"
                                            class="gap-x-4 rounded-xl px-7 py-4 max-md:py-3 max-sm:gap-x-5 max-sm:rounded-lg max-sm:px-4 max-sm:py-1.5"
                                        />
                                    @endif

                                    {!! view_render_event('bagisto.shop.products.view.quantity.after', ['product' => $product]) !!}

                                    @if (core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                                        <!-- Add To Cart Button -->
                                        {!! view_render_event('bagisto.shop.products.view.add_to_cart.before', ['product' => $product]) !!}

                                        <x-shop::button
                                            type="submit"
                                            class="secondary-button w-full max-w-full max-md:py-3 max-sm:rounded-lg max-sm:py-1.5"
                                            button-type="secondary-button"
                                            :loading="false"
                                            :title="trans('shop::app.products.view.add-to-cart')"
                                            :disabled="! $product->isSaleable(1)"
                                            ::loading="isStoring.addToCart"
                                            ::disabled="isStoring.addToCart"
                                            @click="is_buy_now=0;"
                                        />

                                        {!! view_render_event('bagisto.shop.products.view.add_to_cart.after', ['product' => $product]) !!}
                                    @else
                                        <button
                                            type="button"
                                            class="secondary-button w-full max-w-full max-md:py-3 max-sm:rounded-lg max-sm:py-1.5"
                                            @click="$refs.contactUsModal.open()"
                                        >
                                            @lang('shop::app.components.layouts.footer.contact-us')
                                        </button>
                                    @endif
                                </div>

                                <!-- Buy Now Button -->
                                @if (core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                                    {!! view_render_event('bagisto.shop.products.view.buy_now.before', ['product' => $product]) !!}

                                    @if (core()->getConfigData('catalog.products.storefront.buy_now_button_display'))
                                        <x-shop::button
                                            type="submit"
                                            class="primary-button mt-5 w-full max-w-[470px] max-md:py-3 max-sm:mt-3 max-sm:rounded-lg max-sm:py-1.5"
                                            button-type="primary-button"
                                            :title="trans('shop::app.products.view.buy-now')"
                                            :disabled="! $product->isSaleable(1)"
                                            ::loading="isStoring.buyNow"
                                            @click="is_buy_now=1;"
                                            ::disabled="isStoring.buyNow"
                                        />
                                    @endif

                                    {!! view_render_event('bagisto.shop.products.view.buy_now.after', ['product' => $product]) !!}
                                @endif

                                {!! view_render_event('bagisto.shop.products.view.additional_actions.before', ['product' => $product]) !!}

                                <!-- Share Buttons -->
                                <div class="mt-10 flex gap-9 max-md:mt-4 max-md:flex-wrap max-sm:justify-center max-sm:gap-3">
                                    {!! view_render_event('bagisto.shop.products.view.compare.before', ['product' => $product]) !!}

                                    <div
                                        class="flex cursor-pointer items-center justify-center gap-2.5 max-sm:gap-1.5 max-sm:text-base"
                                        role="button"
                                        tabindex="0"
                                        @click="is_buy_now=0; addToCompare({{ $product->id }})"
                                    >
                                        @if (core()->getConfigData('catalog.products.settings.compare_option'))
                                            <span
                                                class="icon-compare text-2xl"
                                                role="presentation"
                                            ></span>

                                            @lang('shop::app.products.view.compare')
                                        @endif
                                    </div>

                                    {!! view_render_event('bagisto.shop.products.view.compare.after', ['product' => $product]) !!}
                                </div>

                                {!! view_render_event('bagisto.shop.products.view.additional_actions.after', ['product' => $product]) !!}
                            </div>
                        </div>
                    </div>
                </form>
            </x-shop::form>

            <!-- Contact Us Modal -->
            <x-shop::modal ref="contactUsModal">
                <x-slot:header>
                <h2 class="text-lg font-semibold max-md:text-base">
                        @lang('shop::app.products.view.contact-us.title')
                    </h2>
                </x-slot>

                <x-slot:content>
                    <x-shop::form :action="route('shop.home.contact_us.send_mail')">
                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label class="required">
                                @lang('shop::app.products.view.contact-us.name')
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="text"
                                name="name"
                                rules="required"
                                :value="old('name')"
                                :label="trans('shop::app.products.view.contact-us.name')"
                                :placeholder="trans('shop::app.products.view.contact-us.name')"
                                :aria-label="trans('shop::app.products.view.contact-us.name')"
                                aria-required="true"
                            />

                            <x-shop::form.control-group.error control-name="name" />
                        </x-shop::form.control-group>

                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label class="required">
                                @lang('shop::app.products.view.contact-us.email')
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="email"
                                name="email"
                                rules="required|email"
                                :value="old('email')"
                                :label="trans('shop::app.products.view.contact-us.email')"
                                :placeholder="trans('shop::app.products.view.contact-us.email')"
                                :aria-label="trans('shop::app.products.view.contact-us.email')"
                                aria-required="true"
                            />

                            <x-shop::form.control-group.error control-name="email" />
                        </x-shop::form.control-group>

                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label>
                                @lang('shop::app.products.view.contact-us.phone-number')
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="text"
                                name="contact"
                                rules="phone"
                                :value="old('contact')"
                                :label="trans('shop::app.products.view.contact-us.phone-number')"
                                :placeholder="trans('shop::app.products.view.contact-us.phone-number')"
                                :aria-label="trans('shop::app.products.view.contact-us.phone-number')"
                            />

                            <x-shop::form.control-group.error control-name="contact" />
                        </x-shop::form.control-group>

                        <x-shop::form.control-group>
                            <x-shop::form.control-group.label class="required">
                                @lang('shop::app.products.view.contact-us.desc')
                            </x-shop::form.control-group.label>

                            <x-shop::form.control-group.control
                                type="textarea"
                                name="message"
                                rules="required"
                                :label="trans('shop::app.products.view.contact-us.message')"
                                :placeholder="trans('shop::app.products.view.contact-us.describe-here')"
                                :aria-label="trans('shop::app.products.view.contact-us.message')"
                                aria-required="true"
                                rows="6"
                            />

                            <x-shop::form.control-group.error control-name="message" />
                        </x-shop::form.control-group>

                        @if (core()->getConfigData('customer.captcha.credentials.status'))
                            <x-shop::form.control-group class="mt-5">
                                {!! \Webkul\Customer\Facades\Captcha::render() !!}

                                <x-shop::form.control-group.error control-name="recaptcha_token" />
                            </x-shop::form.control-group>
                        @endif

                        <div class="mt-6 flex justify-end">
                            <button
                                type="submit"
                                class="primary-button rounded-2xl px-8 py-3 max-sm:rounded-lg max-sm:px-6 max-sm:py-2"
                            >
                                @lang('shop::app.products.view.contact-us.submit')
                            </button>
                        </div>
                    </x-shop::form>
                </x-slot>
            </x-shop::modal>
        </script>

        <script type="module">
            app.component('v-product', {
                template: '#v-product-template',

                data() {
                    return {
                        isWishlist: false,

                        isCustomer: '{{ auth()->guard('customer')->check() }}',

                        is_buy_now: 0,

                        isStoring: {
                            addToCart: false,

                            buyNow: false,
                        },
                    }
                },

                mounted() {
                    this.checkWishlistStatus();
                },

                methods: {
                    addToCart(params) {
                        const operation = this.is_buy_now ? 'buyNow' : 'addToCart';

                        this.isStoring[operation] = true;

                        let formData = new FormData(this.$refs.formData);

                        this.ensureQuantity(formData);

                        this.$axios.post('{{ route("shop.api.checkout.cart.store") }}', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                }
                            })
                            .then(response => {
                                if (response.data.message) {
                                    this.$emitter.emit('update-mini-cart', response.data.data);

                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                    if (response.data.redirect) {
                                        window.location.href= response.data.redirect;
                                    }
                                } else {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: response.data.data.message });
                                }

                                this.isStoring[operation] = false;
                            })
                            .catch(error => {
                                this.isStoring[operation] = false;

                                this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.message });
                            });
                    },

                    checkWishlistStatus() {
                        if (this.isCustomer) {
                            /**
                             * Fetches the wishlist items for the customer and checks whether the current
                             * product exists in the wishlist. If found, `isWishlist` is set to true;
                             * otherwise, it is set to false.
                             *
                             * This approach is used due to Full Page Cache (FPC) limitations. We cannot
                             * use a replacer here because `product_id` is dynamic, and the replacer
                             * cannot reliably detect it.
                             */
                            this.$axios.get('{{ route('shop.api.customers.account.wishlist.index') }}')
                                .then(response => {
                                    const wishlistItems = response.data.data || [];

                                    this.isWishlist = Boolean(wishlistItems.find(item => item.product.id == "{{ $product->id }}")?.product?.is_wishlist);
                                })
                                .catch(error => {});
                        }
                    },

                    addToWishlist() {
                        if (this.isCustomer) {
                            this.$axios.post('{{ route('shop.api.customers.account.wishlist.store') }}', {
                                    product_id: "{{ $product->id }}"
                                })
                                .then(response => {
                                    this.isWishlist = ! this.isWishlist;

                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                                })
                                .catch(error => {});
                        } else {
                            window.location.href = "{{ route('shop.customer.session.index')}}";
                        }
                    },

                    addToCompare(productId) {
                        /**
                         * This will handle for customers.
                         */
                        if (this.isCustomer) {
                            this.$axios.post('{{ route("shop.api.compare.store") }}', {
                                    'product_id': productId
                                })
                                .then(response => {
                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                                })
                                .catch(error => {
                                    if ([400, 422].includes(error.response.status)) {
                                        this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.data.message });

                                        return;
                                    }

                                    this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message});
                                });

                            return;
                        }

                        /**
                         * This will handle for guests.
                         */
                        let existingItems = this.getStorageValue(this.getCompareItemsStorageKey()) ?? [];

                        if (existingItems.length) {
                            if (! existingItems.includes(productId)) {
                                existingItems.push(productId);

                                this.setStorageValue(this.getCompareItemsStorageKey(), existingItems);

                                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('shop::app.products.view.add-to-compare')" });
                            } else {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('shop::app.products.view.already-in-compare')" });
                            }
                        } else {
                            this.setStorageValue(this.getCompareItemsStorageKey(), [productId]);

                            this.$emitter.emit('add-flash', { type: 'success', message: "@lang('shop::app.products.view.add-to-compare')" });
                        }
                    },

                    updateQty(quantity, id) {
                        this.isLoading = true;

                        let qty = {};

                        qty[id] = quantity;

                        this.$axios.put('{{ route('shop.api.checkout.cart.update') }}', { qty })
                            .then(response => {
                                if (response.data.message) {
                                    this.cart = response.data.data;
                                } else {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: response.data.data.message });
                                }

                                this.isLoading = false;
                            }).catch(error => this.isLoading = false);
                    },

                    getCompareItemsStorageKey() {
                        return 'compare_items';
                    },

                    setStorageValue(key, value) {
                        localStorage.setItem(key, JSON.stringify(value));
                    },

                    getStorageValue(key) {
                        let value = localStorage.getItem(key);

                        if (value) {
                            value = JSON.parse(value);
                        }

                        return value;
                    },

                    scrollToReview() {
                        let accordianElement = document.querySelector('#review-accordian-button');

                        if (accordianElement) {
                            accordianElement.click();

                            accordianElement.scrollIntoView({
                                behavior: 'smooth'
                            });
                        }

                        let tabElement = document.querySelector('#review-tab-button');

                        if (tabElement) {
                            tabElement.click();

                            tabElement.scrollIntoView({
                                behavior: 'smooth'
                            });
                        }
                    },

                    ensureQuantity(formData) {
                        if (! formData.has('quantity')) {
                            formData.append('quantity', 1);
                        }
                    },
                },
            });
        </script>

        <script
            type="text/x-template"
            id="v-product-associations-template"
        >
            <div ref="carouselWrapper">
                <template v-if="isVisible">
                    <!-- Featured Products -->
                    <x-shop::products.carousel
                        :title="trans('shop::app.products.view.related-product-title')"
                        :src="route('shop.api.products.related.index', ['id' => $product->id])"
                    />

                    <!-- Up-sell Products -->
                    <x-shop::products.carousel
                        :title="trans('shop::app.products.view.up-sell-title')"
                        :src="route('shop.api.products.up-sell.index', ['id' => $product->id])"
                    />
                </template>
            </div>
        </script>

        <script type="module">
            app.component('v-product-associations', {
                template: '#v-product-associations-template',

                data() {
                    return {
                        isVisible: false,
                    };
                },

                mounted() {
                    const observer = new IntersectionObserver(
                        (entries) => {
                            entries.forEach((entry) => {
                                if (entry.isIntersecting) {
                                    this.isVisible = true;
                                    observer.unobserve(entry.target); // Stop observing
                                }
                            });
                        },
                        { threshold: 0.1 }
                    );

                    observer.observe(this.$refs.carouselWrapper);
                }
            });
        </script>

        @if (core()->getConfigData('customer.captcha.credentials.status'))
            {!! \Webkul\Customer\Facades\Captcha::renderJS() !!}
        @endif
    @endpushOnce
</x-shop::layouts>
