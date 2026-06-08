@inject ('reviewHelper', 'Webkul\Product\Helpers\Review')
@inject ('productViewHelper', 'Webkul\Product\Helpers\View')

@php
    $avgRatings = $reviewHelper->getAverageRating($product);

    $percentageRatings = $reviewHelper->getPercentageRating($product);

    $customAttributeValues = $productViewHelper->getAdditionalData($product);

    $attributeData = collect($customAttributeValues)->filter(fn ($item) => ! empty($item['value']));
@endphp

<!-- SEO Meta Content -->
@push('meta')
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
@endPush

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
<div id="customization-dialog" class="fixed inset-0 bg-black bg-opacity-50 z-[99999] hidden overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl mx-auto p-4 flex flex-col" style="width: 1200px; height: 700px; margin-top: 100px;">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Custom Design</h2>
            <button id="close-dialog" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        
        <div class="flex gap-4 flex-1 min-h-0">
            <!-- Left Panel - 3 Tabs -->
            <div style="width: 200px; flex-shrink: 0;" class="border-r pr-4 flex flex-col">
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
                    <div id="product-images" class="flex flex-col gap-2 items-center"></div>
                </div>
                
                <div id="tab-image" class="tab-content hidden flex-1">
                    <input type="file" id="image-upload" accept="image/*" class="w-full border rounded p-2 mb-2" />
                    <div id="uploaded-images" class="flex flex-col gap-2 items-center overflow-auto flex-1"></div>
                </div>
                
                <div id="tab-text" class="tab-content hidden flex-1">
                    <h3 class="font-semibold mb-2 text-sm">Add Text</h3>
                    <input type="text" id="text-input" placeholder="Enter text" class="w-full border rounded p-2 mb-2" />
                    <select id="text-color" class="w-full border rounded p-2 mb-2">
                        <option value="#000000">Black</option>
                        <option value="#FF0000">Red</option>
                        <option value="#0000FF">Blue</option>
                        <option value="#00FF00">Green</option>
                    </select>
                    <input type="number" id="font-size" value="24" min="12" max="72" class="w-full border rounded p-2 mb-2" />
                    <button onclick="addText()" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Add Text</button>
                </div>
                <div id="tab-layers" class="tab-content hidden flex-1 flex flex-col">
                    <h3 class="font-semibold mb-2 text-sm">Layers</h3>
                    <div id="layers-list" class="flex-1 overflow-auto"></div>
                </div>
            </div>
            
            <!-- Center - Design Canvas -->
            <div class="flex-1 flex flex-col min-w-0 items-center justify-center">
                <h3 class="font-semibold mb-2">Design Area</h3>
                <div id="design-canvas" style="width: 500px; height: 500px;" class="border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 flex items-center justify-center overflow-hidden"></div>
            </div>
            
                <!-- Right Panel - Settings -->
                <div style="width: 300px; flex-shrink: 0;" class="flex flex-col gap-2">
                    <h3 class="font-semibold text-sm">Settings</h3>
                    
                    <!-- Maximize - full width -->
                    <button id="btn-maximize" onclick="maximizeElement()" disabled class="w-full bg-gray-200 p-2 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-300 flex items-center justify-center gap-2" title="Fill Area">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        <span>Fill Area</span>
                    </button>
                    
                    <!-- Flip Horizontal -->
                    <button id="btn-flip-h" onclick="flipHorizontal()" disabled class="w-full bg-gray-200 p-2 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-300 flex items-center justify-center gap-2" title="Flip Horizontal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <span>Flip Horizontal</span>
                    </button>
                    
                    <!-- Flip Vertical -->
                    <button id="btn-flip-v" onclick="flipVertical()" disabled class="w-full bg-gray-200 p-2 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-300 flex items-center justify-center gap-2" title="Flip Vertical">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16h12M7 16l4-4m-4-4l4 4m-8-8l-4 4m4-4l-4-4m0 12h12"/></svg>
                        <span>Flip Vertical</span>
                    </button>
                    
                    <!-- Move Up -->
                    <button id="btn-forward" onclick="bringForward()" disabled class="w-full bg-gray-200 p-2 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-300 flex items-center justify-center gap-2" title="Move Up">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        <span>Move Up</span>
                    </button>
                    
                    <!-- Move Down -->
                    <button id="btn-backward" onclick="sendBackward()" disabled class="w-full bg-gray-200 p-2 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-300 flex items-center justify-center gap-2" title="Move Down">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        <span>Move Down</span>
                    </button>
                    
                    <!-- Delete -->
                    <button id="btn-delete" onclick="deleteElement()" disabled class="w-full bg-red-500 text-white p-2 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-600 flex items-center justify-center gap-2" title="Delete">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Delete</span>
                    </button>
                </div>
