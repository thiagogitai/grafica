<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Força HTTPS em produção para evitar ações/formulários HTTP
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        $requestOnly = false;
        $whatsappNumber = '11981818180';
        $socialLinks = [];
        $footerText = '';
        $disablePriceEditor = false;

        if (Schema::hasTable('settings')) {
            $requestOnly = Setting::boolean('request_only', false);
            $socialLinks = json_decode(Setting::get('social_links', '[]'), true);
            if (!is_array($socialLinks)) {
                $socialLinks = [];
            }
            $footerText = Setting::get('footer_text', '');
            $disablePriceEditor = Setting::boolean('disable_price_editor', false);
        }

        $whatsappLink = $whatsappNumber ? 'https://wa.me/' . preg_replace('/\D+/', '', (string) $whatsappNumber) : null;

        View::share('requestOnlyMode', $requestOnly);
        View::share('globalWhatsappNumber', $whatsappNumber);
        View::share('globalWhatsappLink', $whatsappLink);
        View::share('globalSocialLinks', $socialLinks);
        View::share('globalFooterText', $footerText);
        View::share('disablePriceEditor', $disablePriceEditor);
    }
}
