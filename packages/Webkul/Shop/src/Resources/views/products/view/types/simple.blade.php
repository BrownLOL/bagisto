{{-- @include('shop::products.view.customizable-options') --}}

@if ($product->type === 'simple')
    @include('shop::products.view.customization-button')
@endif