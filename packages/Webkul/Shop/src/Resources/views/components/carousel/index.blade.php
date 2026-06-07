@props(['options'])

@php
    $carouselImages = $options['images'] ?? [];

    $firstImage = data_get($carouselImages, '0.image');

    $firstImageTitle = data_get($carouselImages, '0.title');
@endphp

@if ($firstImage)
    @push('meta')
        <link
            rel="preload"
            as="image"
            href="{{ str_replace('storage', 'cache/small', $firstImage) }}"
            imagesrcset="{{ $firstImage }} 1920w, {{ str_replace('storage', 'cache/large', $firstImage) }} 1280w, {{ str_replace('storage', 'cache/medium', $firstImage) }} 1024w, {{ str_replace('storage', 'cache/small', $firstImage) }} 768w"
            imagesizes="(max-width: 1200px) 100vw, 1200px"
            fetchpriority="high"
        >
    @endpush
@endif

<v-carousel :images="{{ json_encode($carouselImages) }}">
    <div class="overflow-hidden relative" style="max-width:1200px;margin:0 auto;">
        @if ($firstImage)
            <img
                src="{{ $firstImage }}"
                srcset="{{ $firstImage }} 1920w, {{ str_replace('storage', 'cache/large', $firstImage) }} 1280w, {{ str_replace('storage', 'cache/medium', $firstImage) }} 1024w, {{ str_replace('storage', 'cache/small', $firstImage) }} 768w"
                sizes="(max-width: 1200px) 100vw, 1200px"
                class="w-full select-none object-cover"
                style="aspect-ratio:1.6/1;display:block"
                alt="{{ $firstImageTitle ?? trans('shop::app.home.index.image-carousel') }}"
                fetchpriority="high"
                decoding="sync"
            >
        @else
            <div class="shimmer" style="aspect-ratio:1.6/1"></div>
        @endif
        <template v-if="!$firstImage">
            <div class="absolute inset-0" v-for="(image, index) in [[]]" :key="index" v-show="false"></div>
        </template>
    </div>
</v-carousel>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-carousel-template"
    >
        <div class="relative w-full overflow-hidden">
            <!-- Spacer to maintain aspect ratio -->
            <div style="aspect-ratio:1.6/1;width:100%"></div>
            
            <!-- Slides -->
            <div
                v-for="(image, index) in images"
                :key="index"
                v-show="currentIndex === index"
                style="position:absolute;top:0;left:0;width:100%;height:100%;cursor:pointer"
                @click="visitLink(image)"
            >
                <img
                    :src="image.image"
                    :alt="image?.title || 'Carousel Image ' + (index + 1)"
                    class="w-full h-full select-none object-cover"
                    :loading="index === 0 ? 'eager' : 'lazy'"
                    :fetchpriority="index === 0 ? 'high' : 'low'"
                    decoding="async"
                />
            </div>

            <!-- Navigation -->
            <span
                class="icon-arrow-left absolute left-2.5 top-1/2 -translate-y-1/2 hidden w-auto rounded-full bg-black/80 p-3 text-2xl font-bold text-white opacity-30 transition-all md:inline-block"
                :class="{
                    'cursor-not-allowed': direction == 'ltr' && currentIndex == 0,
                    'cursor-pointer hover:opacity-100': direction == 'ltr' ? currentIndex > 0 : currentIndex <= 0
                }"
                role="button"
                aria-label="@lang('shop::components.carousel.previous')"
                tabindex="0"
                v-if="images?.length >= 2"
                @click.stop="navigate('prev')"
            >
            </span>

            <span
                class="icon-arrow-right absolute right-2.5 top-1/2 -translate-y-1/2 hidden w-auto rounded-full bg-black/80 p-3 text-2xl font-bold text-white opacity-30 transition-all md:inline-block"
                :class="{
                    'cursor-not-allowed': direction == 'rtl' && currentIndex == images?.length - 1,
                    'cursor-pointer hover:opacity-100': direction == 'rtl' ? currentIndex < images?.length - 1 : currentIndex < images?.length - 1
                }"
                role="button"
                aria-label="@lang('shop::components.carousel.next')"
                tabindex="0"
                v-if="images?.length >= 2"
                @click.stop="navigate('next')"
            >
            </span>

            <!-- Pagination -->
            <div
                class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2"
                v-if="images?.length >= 2"
            >
                <template v-for="(image, index) in images" :key="index">
                    <span
                        class="h-2 w-2 rounded-full transition-all"
                        :class="currentIndex === index ? 'bg-white w-4' : 'bg-white/50'"
                        role="button"
                        tabindex="0"
                        :aria-label="'Go to slide ' + (index + 1)"
                        @click.stop="goTo(index)"
                        @keydown.enter="goTo(index)"
                        @keydown.space.prevent="goTo(index)"
                    >
                    </span>
                </template>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-carousel', {
            template: '#v-carousel-template',

            props: ['images'],

            data() {
                return {
                    currentIndex: 0,
                    direction: 'ltr',
                    autoplayInterval: null,
                    isHovered: false,
                };
            },

            mounted() {
                this.direction = document.documentElement.dir;
                this.startAutoplay();
            },

            beforeDestroy() {
                this.stopAutoplay();
            },

            methods: {
                navigate(direction) {
                    if (direction === 'next') {
                        if (this.currentIndex < this.images.length - 1) {
                            this.currentIndex++;
                        } else {
                            this.currentIndex = 0;
                        }
                    } else {
                        if (this.currentIndex > 0) {
                            this.currentIndex--;
                        } else {
                            this.currentIndex = this.images.length - 1;
                        }
                    }
                },

                goTo(index) {
                    this.currentIndex = index;
                },

                startAutoplay() {
                    this.stopAutoplay();

                    this.autoplayInterval = setInterval(() => {
                        if (! this.isHovered) {
                            this.navigate('next');
                        }
                    }, 5000);
                },

                stopAutoplay() {
                    if (this.autoplayInterval) {
                        clearInterval(this.autoplayInterval);

                        this.autoplayInterval = null;
                    }
                },

                visitLink(image) {
                    if (image.url) {
                        window.location.href = image.url;
                    }
                },
            },
        });
    </script>
@endpushOnce
