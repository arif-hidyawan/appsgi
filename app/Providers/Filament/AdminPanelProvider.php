<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Enums\MaxWidth;
//use Filapanel\ClassicTheme\ClassicThemePlugin;
use Illuminate\Support\Facades\Blade; 
use Filament\View\PanelsRenderHook;


// Pastikan nama class sesuai dengan file yang ada
//use App\Filament\Widgets\CustomDashboardChart; 

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            
            // --- UPDATE LOGO DI SINI ---
            ->brandLogo(asset('images/logo.png')) // Pastikan file ada di public/images/logo.png
            ->brandLogoHeight('2rem') // Sesuaikan tinggi logo (misal: 3rem atau 40px)
            ->favicon(asset('images/favicon.png'))
            // ---------------------------

            // --- TOMBOL HIDE SIDEBAR ---
            ->sidebarCollapsibleOnDesktop()
            // ---------------------------

            ->maxContentWidth(MaxWidth::Full)
            ->colors([
                'primary' => '#025DAA',
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@include("filament.admin-custom-style")')
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => Blade::render('
                    <footer class="py-4 text-center text-xs text-gray-500 dark:text-gray-400">
                        Copyright &copy; {{ date("Y") }} <strong>Saputra Group Indonesia</strong>. All Rights Reserved. <br>
                        Development by <strong>PT Akselerasi Teknologi Integrasi</strong>.
                    </footer>
                ')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            //->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            //->widgets([
                //CustomDashboardChart::class,
            //])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            //->plugin(ClassicThemePlugin::make())
            ->authMiddleware([
                Authenticate::class,
            
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}