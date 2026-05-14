<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use App\Models\Setting;
use App\Models\User;

class TranslationController extends BaseController
{
    public function getTranslations($locale)
    {
        $path = resource_path("lang/{$locale}.json");

        if (!File::exists($path)) {
            $path = resource_path("lang/en.json");
            $locale = 'en';
        }

        // Always determine direction based on locale
        $direction = in_array($locale, ['ar', 'he']) ? 'right' : 'left';
        $layoutDirection = in_array($locale, ['ar', 'he']) ? 'rtl' : 'ltr';

        // Only store in cookies if in demo mode
        if (config('app.is_demo')) {
            Cookie::queue('app_language', $locale, 60 * 24 * 30); // 30 days
            Cookie::queue('app_direction', $layoutDirection, 60 * 24 * 30);
        }

        // Demo mode handling
        if (config('app.is_demo') !== true) {
            if (auth()->check()) {
                // Update authenticated user's language setting
                auth()->user()->update(['lang' => $locale]);

                // Setting::updateOrCreate(
                //     [
                //         'key' => 'layoutDirection',
                //         'user_id' => auth()->id()
                //     ],
                //     [
                //         'value' => $direction
                //     ]
                // );
                
                // if (in_array($locale, ['ar', 'he'])) {
                //     Setting::updateOrCreate(
                //         [
                //             'key' => 'layoutDirection',
                //             'user_id' => auth()->id()
                //         ],
                //         [
                //             'value' => $direction
                //         ]
                //     );
                // }

            } else {
                // For unauthenticated users on auth pages, use superadmin's language
                $superAdmin = User::where('type', 'superadmin')->first();
                if ($superAdmin && request()->is('login', 'register', 'password/*', 'email/*')) {
                    $locale = $superAdmin->lang ?? 'en';
                    $path = resource_path("lang/{$locale}.json");

                    if (!File::exists($path)) {
                        $path = resource_path("lang/en.json");
                        $locale = 'en';
                    }

                    // Re-determine direction based on superadmin's locale
                    $direction = in_array($locale, ['ar', 'he']) ? 'right' : 'left';
                    $layoutDirection = in_array($locale, ['ar', 'he']) ? 'rtl' : 'ltr';
                }
            }
        }

        $translations = json_decode(File::get($path), true);

        // Add layout direction to the response
        $response = [
            'translations' => $translations,
            'layoutDirection' => $layoutDirection,
            'locale' => $locale
        ];

        return response()->json($response);
    }

    public function getInitialLocale()
    {
        $locale = null;

        // In demo mode, check cookie first
        if (config('app.is_demo')) {
            $cookieLang = Cookie::get('app_language');
            if ($cookieLang) {
                return $cookieLang;
            }
        }

        // For authenticated users, always get from database
        if (auth()->check()) {
            $locale = auth()->user()->lang ?? 'en';
        } elseif (request()->is('login', 'register', 'password/*', 'email/*')) {
            // For auth pages, get from superadmin
            $superAdmin = User::where('type', 'superadmin')->first();
            $locale = $superAdmin->lang ?? 'en';
        } else {
            $locale = 'en';
        }

        // Check if the determined locale is enabled
        $languagesFile = resource_path('lang/language.json');
        if (File::exists($languagesFile)) {
            $languages = json_decode(File::get($languagesFile), true);
            $languageData = collect($languages)->firstWhere('code', $locale);

            // If language is disabled, fallback to English
            if ($languageData && isset($languageData['enabled']) && $languageData['enabled'] === false) {
                $locale = 'en';
            }
        }

        return $locale;
    }
}
