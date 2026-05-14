<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;

class LanguageController extends Controller
{
    const ALLOWED_LANGUAGES = ['en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'zh', 'ar', 'hi', 'ko', 'th', 'vi', 'tr', 'pl', 'nl', 'da', 'sv', 'no', 'fi', 'cs', 'sk', 'hu', 'ro', 'bg', 'hr', 'sl', 'et', 'lv', 'lt', 'mt', 'ga', 'cy', 'eu', 'ca', 'gl', 'pt-BR', 'zh-CN', 'zh-TW', 'he'];
    // Show the manage language Inertia page
    public function managePage(Request $request, $lang = null)
    {
        $langListPath = resource_path('lang/language.json');
        $languages = [];
        if (File::exists($langListPath)) {
            $languages = json_decode(File::get($langListPath), true);
        }
        $defaultLang = 'en';
        $selectedLang = $defaultLang;
        if ($lang && collect($languages)->pluck('code')->contains($lang)) {
            $selectedLang = $lang;
        }
        $defaultData = [];
        if (File::exists(resource_path("lang/{$selectedLang}.json"))) {
            $defaultData = json_decode(File::get(resource_path("lang/{$selectedLang}.json")), true);
        }

        // Get available languages with flags and enabled status
        $availableLanguages = collect($languages)
            ->map(function ($lang) {
                return [
                    'code' => $lang['code'],
                    'name' => $lang['name'],
                    'countryCode' => $lang['countryCode'],
                    'flag' => $this->getCountryFlag($lang['countryCode']),
                    'enabled' => $lang['enabled'] ?? true
                ];
            })->values()->toArray();

        // Get current language status
        $currentLangData = collect($languages)->firstWhere('code', $selectedLang);
        $isCurrentLanguageEnabled = $currentLangData['enabled'] ?? true;

        return Inertia::render('manage-language', [
            'languages' => $languages,
            'defaultLang' => $selectedLang,
            'defaultData' => $defaultData,
            'availableLanguages' => $availableLanguages,
            'isCurrentLanguageEnabled' => $isCurrentLanguageEnabled,
        ]);
    }

    // Load a language file
    public function load(Request $request)
    {
        $langListPath = resource_path('lang/language.json');
        $languages = collect();
        if (File::exists($langListPath)) {
            $languages = collect(json_decode(File::get($langListPath), true));
        }
        $lang = $request->get('lang', 'en');
        if (!$languages->pluck('code')->contains($lang)) {
            return response()->json(['error' => __('Language not found')], 404);
        }
        $langPath = resource_path("lang/{$lang}.json");
        if (!File::exists($langPath)) {
            return response()->json(['error' => __('Language file not found')], 404);
        }
        $data = json_decode(File::get($langPath), true);
        return response()->json(['data' => $data]);
    }

    // Save a language file
    public function save(Request $request)
    {
        try {
            $langListPath = resource_path('lang/language.json');
            $languages = collect();
            if (File::exists($langListPath)) {
                $languages = collect(json_decode(File::get($langListPath), true));
            }
            $lang = $request->get('lang');
            $data = $request->get('data');
            if (!$lang || !is_array($data) || !$languages->pluck('code')->contains($lang)) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => __('Invalid request')], 400);
                }
                return redirect()->back()->with('error', __('Invalid request'));
            }
            $langPath = resource_path("lang/{$lang}.json");
            if (!File::exists($langPath)) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => __('Language file not found')], 404);
                }
                return redirect()->back()->with('error', __('Language file not found'));
            }
            File::put($langPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($request->expectsJson()) {
                return response()->json(['success' => __('Language updated successfully')]);
            }
            return redirect()->back()->with('success', __('Language updated successfully'));
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => __('Failed to update language file: ') . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', __('Failed to update language file: ') . $e->getMessage());
        }
    }

    public function createLanguage(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'countryCode' => 'required|string|size:2'
        ], [
            'code.required' => __('Language code is required.'),
            'code.string' => __('Language code must be a valid string.'),
            'code.max' => __('Language code must not exceed 10 characters.'),
            'name.required' => __('Language name is required.'),
            'name.string' => __('Language name must be a valid string.'),
            'name.max' => __('Language name must not exceed 255 characters.'),
            'countryCode.required' => __('Country code is required.'),
            'countryCode.string' => __('Country code must be a valid string.'),
            'countryCode.size' => __('Country code must be exactly 2 characters.'),
        ]);

        try {
            // Check if language already exists in language.json
            $languagesFile = resource_path('lang/language.json');

            if (!is_writable($languagesFile)) {
                return response()->json(['error' => __('Language file is not writable. Please check file permissions.')], 500);
            }

            $languages = json_decode(File::get($languagesFile), true);

            $existingLanguage = collect($languages)->firstWhere('code', $request->code);
            if ($existingLanguage) {
                return response()->json(['error' => __('The language code already exists')], 422);
            }

            $languages[] = [
                'code' => $request->code,
                'name' => $request->name,
                'countryCode' => strtoupper($request->countryCode),
                'enabled' => true
            ];

            $result = File::put($languagesFile, json_encode($languages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if ($result === false) {
                return response()->json(['error' => __('Failed to write to language file. Please check file permissions.')], 500);
            }

            // Copy en.json to new language
            $enFile = resource_path('lang/en.json');
            $newLangFile = resource_path("lang/{$request->code}.json");
            if (File::exists($enFile)) {
                $enContent = File::get($enFile);
                File::put($newLangFile, $enContent);
            } else {
                // Create empty translation file if en.json doesn't exist
                File::put($newLangFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return response()->json(['success' => true, 'message' => __('The language has been created successfully.')]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create language: ' . $e->getMessage()], 500);
        }
    }

    public function deleteLanguage($languageCode)
    {
        if ($languageCode === 'en') {
            return response()->json(['error' => __('Cannot delete English language')], 422);
        }

        try {
            // Remove from language.json
            $languagesFile = resource_path('lang/language.json');
            $languages = json_decode(File::get($languagesFile), true);
            $languages = array_filter($languages, fn($lang) => $lang['code'] !== $languageCode);
            File::put($languagesFile, json_encode(array_values($languages), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Delete main language file
            $mainLangFile = resource_path("lang/{$languageCode}.json");
            if (File::exists($mainLangFile)) {
                File::delete($mainLangFile);
            }

            // Update all users using this language to English
            // \App\Models\User::where('lang', $languageCode)->update(['lang' => 'en']);

            return response()->json(['success' => true, 'message' => __('The language has been deleted.')]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to delete language: :error', ['error' => $e->getMessage()])], 500);
        }
    }

    public function toggleLanguageStatus($languageCode)
    {
        if ($languageCode === 'en') {
            return response()->json(['error' => __('Cannot disable English language')], 422);
        }

        try {
            $languagesFile = resource_path('lang/language.json');
            $languages = json_decode(File::get($languagesFile), true);

            foreach ($languages as &$language) {
                if ($language['code'] === $languageCode) {
                    $language['enabled'] = !($language['enabled'] ?? true);
                    break;
                }
            }

            File::put($languagesFile, json_encode($languages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // // If disabling language, update all users using this language to English
            // if (isset($language['enabled']) && $language['enabled'] === false) {
            //     \App\Models\User::where('lang', $languageCode)->update(['lang' => 'en']);
            // }

            return response()->json(['success' => true, 'message' => __('The language status updated successfully.')]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to update language status: :error', ['error' => $e->getMessage()])], 500);
        }
    }

    public function changeLanguage(Request $request)
    {
        $languageCode = $request->input('language');

        // RTL languages that should automatically set layoutDirection to 'right'
        $rtlLanguages = ['ar', 'he'];
        $isRtl = in_array($languageCode, $rtlLanguages);

        if (config('app.is_demo')) {
            return redirect()->back()->cookie('app_language', $languageCode, 60 * 24 * 365);
        }

        if ($request->user()) {
            $request->user()->update(['lang' => $languageCode]);

            // Auto-update layoutDirection for languages
            updateSetting('layoutDirection', $isRtl ? 'right' : 'left', $request->user()->id);
        }

        return redirect()->back();
    }

    private function getCountryFlag(string $countryCode): string
    {
        if (strlen($countryCode) !== 2) {
            return '🌐'; // Default flag for invalid codes
        }

        $codePoints = str_split(strtoupper($countryCode));
        $codePoints = array_map(fn($char) => 127397 + ord($char), $codePoints);
        return mb_convert_encoding('&#' . implode(';&#', $codePoints) . ';', 'UTF-8', 'HTML-ENTITIES');
    }
}
