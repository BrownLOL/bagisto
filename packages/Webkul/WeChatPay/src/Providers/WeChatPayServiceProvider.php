<?php

namespace Webkul\WeChatPay\Providers;

use Illuminate\Support\ServiceProvider;

class WeChatPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'wechatpay');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'wechatpay');
    }

    /**
     * Merge the wechatpay configuration with the admin panel
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/payment-methods.php', 'payment_methods'
        );
    }
}
