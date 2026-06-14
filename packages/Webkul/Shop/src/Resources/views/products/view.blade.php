@inject ('reviewHelper', 'Webkul\Product\Helpers\Review')
@inject ('productViewHelper', 'Webkul\Product\Helpers\View')

@php
    $avgRatings = $reviewHelper->getAverageRating($product);

    $percentageRatings = $reviewHelper->getPercentageRating($product);

    $customAttributeValues = $productViewHelper->getAdditionalData($product);

    $attributeData = collect($customAttributeValues)->filter(fn ($item) => ! empty($item['value']));
@endphp

<!-- Customization functionality - included after v-product -->
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

    <?php 
        $productBaseImage = product_image()->getProductBaseImage($product);
        // Get original storage URL instead of cache URL (cache might not exist)
        $productBaseImagePath = $product?->images?->first()?->path ?? null;
        $productBaseStorageUrl = $productBaseImagePath ? url('storage/' . $productBaseImagePath) : null;
    ?>
    
    <script>
        // Store product base image URL for preview generation
        // Use storage URL (original image) instead of cache URL (might not exist)
        window.productBaseImageUrl = "{{ $productBaseStorageUrl ?? $productBaseImage['medium_image_url'] }}";
    </script>
    
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

<!-- Customization Dialog -->
<div id="customization-dialog" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto" style="z-index: 99999;" onclick="if(event.target===this)closeDialog()">
    <div class="bg-white rounded-lg shadow-xl mx-auto p-4 flex flex-col" style="width: 1200px; height: 700px; margin-top: 100px;">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Custom Design</h2>
            <button id="close-dialog" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        
        <div class="flex flex-1 gap-4 overflow-hidden">
            <!-- Left sidebar: Print areas list -->
            <div class="w-64 border-r pr-4 overflow-y-auto">
                <h3 class="font-semibold mb-3">Print Areas</h3>
                <div id="print-areas-list"></div>
            </div>
            
            <!-- Main area: Canvas -->
            <div class="flex-1 flex flex-col">
                <!-- Top: Background images selection -->
                <div id="product-images-div" class="flex gap-2 mb-3 flex-wrap"></div>
                
                <!-- Canvas area -->
                <div id="canvas-area" class="flex-1 relative bg-gray-100 rounded overflow-hidden">
                    <div id="design-canvas" class="absolute inset-0 flex items-center justify-center" style="min-height: 400px;"></div>
                </div>
                
                <!-- Layers panel -->
                <div id="layers-panel" class="mt-3 border-t pt-3">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-semibold">Layers</h4>
                        <button id="add-text-btn" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">Add Text</button>
                    </div>
                    <div id="layers-list" class="max-h-32 overflow-y-auto"></div>
                </div>
            </div>
            
            <!-- Right sidebar: Controls -->
            <div class="w-64 border-l pl-4">
                <h3 class="font-semibold mb-3">Controls</h3>
                
                <div id="element-controls" class="space-y-3 hidden">
                    <div>
                        <label class="block text-sm font-medium">Position X (%)</label>
                        <input type="number" id="elem-x" class="w-full border rounded px-2 py-1" min="0" max="100" step="1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Position Y (%)</label>
                        <input type="number" id="elem-y" class="w-full border rounded px-2 py-1" min="0" max="100" step="1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Rotation (deg)</label>
                        <input type="range" id="elem-rotation" class="w-full" min="-180" max="180" value="0">
                        <span id="rotation-value">0</span>°
                    </div>
                </div>
                
                <div class="mt-4">
                    <p class="text-sm text-gray-600 mb-2">Upload your design or text</p>
                    <label class="block bg-green-500 text-white text-center py-2 px-4 rounded cursor-pointer hover:bg-green-600">
                        Upload Image
                        <input type="file" id="image-upload" accept="image/*" class="hidden">
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Bottom actions -->
        <div class="flex justify-end gap-3 mt-4 pt-4 border-t">
            <button id="cancel-btn" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">Cancel</button>
            <button id="save-design-btn" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Save Design</button>
            <button id="add-to-cart-btn" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">Add to Cart</button>
        </div>
    </div>
</div>

@include('shop::products.view.customization-button')
</x-shop::layouts>
