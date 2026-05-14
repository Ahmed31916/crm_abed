<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


class SystemSettingsController extends Controller
{
    /**
     * Update the system settings.
     *
     * Handles system-wide configuration including:
     * - Language and localization settings
     * - Date/time formats and timezone
     * - Email verification requirements
     * - Landing page enable/disable toggle
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'defaultLanguage' => 'required|string',
                'dateFormat' => 'required|string',
                'timeFormat' => 'required|string',
                'calendarStartDay' => 'required|string',
                'defaultTimezone' => 'required|string',
                'emailVerification' => 'boolean',
                'landingPageEnabled' => 'boolean',
                'registrationEnabled' => 'boolean',
                'termsConditionsPage' => 'nullable|url'
            ]);

            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('System settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update system settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the brand settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBrand(Request $request)
    {
        try {
            $validated = $request->validate([
                'settings' => 'required|array',
                'settings.logoDark' => 'nullable|string',
                'settings.logoLight' => 'nullable|string',
                'settings.favicon' => 'nullable|string',
                'settings.titleText' => 'required|string|max:255',
                'settings.footerText' => 'required|string|max:500',
                'settings.themeColor' => 'nullable|string|in:blue,green,purple,orange,red,custom',
                'settings.customColor' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'settings.sidebarVariant' => 'nullable|string|in:inset,floating,minimal',
                'settings.sidebarStyle' => 'nullable|string|in:plain,colored,gradient',
                'settings.layoutDirection' => 'nullable|string|in:left,right',
                'settings.themeMode' => 'nullable|string|in:light,dark,system',
            ],[
                'settings.titleText.required' =>'Title Text is required',
                'settings.footerText.required' =>'Footer Text is required'
            ]);

            $userId = auth()->id();
            foreach ($validated['settings'] as $key => $value) {
                updateSetting($key, $value, $userId);
            }

            return redirect()->back()->with('success', __('Brand settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update brand settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the recaptcha settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRecaptcha(Request $request)
    {
        try {
            $validated = $request->validate([
                'recaptchaEnabled' => 'boolean',
                'recaptchaVersion' => 'required|in:v2,v3',
                'recaptchaSiteKey' => 'required|string',
                'recaptchaSecretKey' => 'required|string',
            ]);

            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('ReCaptcha settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update ReCaptcha settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the chatgpt settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateChatgpt(Request $request)
    {
        try {
            $validated = $request->validate([
                'chatgptKey' => 'required|string',
                'chatgptModel' => 'required|string',
            ]);

            foreach ($validated as $key => $value) {
                updateSetting($key, $value);
            }

            return redirect()->back()->with('success', __('Chat GPT settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Chat GPT settings: :error', ['error' => $e->getMessage()]));
        }
    }



    /**
     * Update the cookie settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCookie(Request $request)
    {
        try {
            $validated = $request->validate([
                'enableLogging' => 'required|boolean',
                'strictlyNecessaryCookies' => 'required|boolean',
                'cookieTitle' => 'required|string|max:255',
                'strictlyCookieTitle' => 'required|string|max:255',
                'cookieDescription' => 'required|string',
                'strictlyCookieDescription' => 'required|string',
                'contactUsDescription' => 'required|string',
                'contactUsUrl' => 'required|url',
            ]);

            foreach ($validated as $key => $value) {
                updateSetting($key, is_bool($value) ? ($value ? '1' : '0') : $value);
            }

            return redirect()->back()->with('success', __('Cookie settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update cookie settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the SEO settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSeo(Request $request)
    {
        try {
            $rules = [
                'metaKeywords' => 'required|string|max:255',
                'metaDescription' => 'required|string|max:160'
            ];

            if ($request->hasFile('metaImage')) {
                $rules['metaImage'] = 'required|file|image|max:5120';
            } else {
                $rules['metaImage'] = 'required|string';
            }

            $validated = $request->validate($rules);

            updateSetting('metaKeywords', $validated['metaKeywords']);
            updateSetting('metaDescription', $validated['metaDescription']);

            if ($request->hasFile('metaImage')) {
                $filenameWithExt = $request->file('metaImage')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('metaImage')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'metaImage', $fileNameToStore, 'seo');
                if ($upload['status'] == true) {
                    updateSetting('metaImage', $upload['url']);
                } else {
                    return redirect()->back()
                        ->withErrors(['metaImage' => $upload['msg']])
                        ->withInput();
                }
            }else{
                updateSetting('metaImage', $validated['metaImage']);
            }

            return redirect()->back()->with('success', __('SEO settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update SEO settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update the Google Calendar settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleCalendar(Request $request)
    {
        try {
            $validated = $request->validate([
                'googleCalendarEnabled' => 'boolean',
                'googleCalendarId' => 'required_if:googleCalendarEnabled,1|nullable|string|max:255',
                'googleCalendarJson' => 'nullable|file|mimes:json|max:2048',
            ],[
                'googleCalendarId.required_if'=>'Google Calendar ID is required'
            ]);

            $userId = createdBy();
            $settings = [
                'googleCalendarEnabled' => $validated['googleCalendarEnabled'] ?? false,
                'googleCalendarId' => $validated['googleCalendarId'] ?? '',
            ];

            $credentialsChanged = false;
            if (isset($settings['googleCalendarId']) && $settings['googleCalendarId'] !== getSetting('googleCalendarId', '', $userId)) {
                $credentialsChanged = true;
            }

            if ($request->hasFile('googleCalendarJson')) {
                $credentialsChanged = true;

                $existingPath = getSetting('googleCalendarJsonPath', null, $userId);
                if ($existingPath && \Storage::disk('public')->exists($existingPath)) {
                    \Storage::disk('public')->delete($existingPath);
                }

                $file = $request->file('googleCalendarJson');
                $path = $file->store('google-calendar', 'public');
                $settings['googleCalendarJsonPath'] = $path;
            }

            if ($credentialsChanged) {
                $settings['is_googlecalendar_sync'] = '0';
            }

            foreach ($settings as $key => $value) {
                updateSetting($key, is_bool($value) ? ($value ? '1' : '0') : $value, $userId);
            }

            return redirect()->back()->with('success', __('Google Calendar settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Google Calendar settings: :error', ['error' => $e->getMessage()]));
        }
    }

    public function syncGoogleCalendar(Request $request)
    {
        try {
            $userId = createdBy();
            $settings = settings($userId);

            if (!($settings['googleCalendarEnabled'] ?? false) || $settings['googleCalendarEnabled'] !== '1') {
                return redirect()->back()->withErrors(['error' => __('Google Calendar integration is not enabled.')]);
            }

            if (empty($settings['googleCalendarId']) || trim($settings['googleCalendarId']) === '') {
                return redirect()->back()->withErrors(['error' => __('Google Calendar ID is not configured.')]);
            }

            if (empty($settings['googleCalendarJsonPath']) || trim($settings['googleCalendarJsonPath']) === '') {
                return redirect()->back()->withErrors(['error' => __('Google Calendar service account JSON is not uploaded.')]);
            }

            $jsonPath = storage_path('app/public/' . $settings['googleCalendarJsonPath']);

            if (!file_exists($jsonPath)) {
                throw new \Exception('Service account JSON file not found.');
            }

            $jsonContent = file_get_contents($jsonPath);
            $credentials = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file format.');
            }

            if (!isset($credentials['type']) || $credentials['type'] !== 'service_account') {
                throw new \Exception('Invalid service account credentials.');
            }

            if (!class_exists('\Google_Client')) {
                throw new \Exception('Google Client library is not installed. Please run: composer require google/apiclient');
            }

            $client = new \Google_Client();
            $client->setAuthConfig($jsonPath);
            $client->addScope(\Google_Service_Calendar::CALENDAR_READONLY);

            $service = new \Google_Service_Calendar($client);

            try {
                $calendar = $service->calendars->get($settings['googleCalendarId']);

                if (!$calendar) {
                    throw new \Exception('Unable to access the specified calendar.');
                }

                updateSetting('is_googlecalendar_sync', '1', $userId);

                return redirect()->back()->with('success', __('Google Calendar connected successfully! Calendar: :name', ['name' => $calendar->getSummary()]));
            } catch (\Google_Service_Exception $calendarException) {
                $errorCode = $calendarException->getCode();
                if ($errorCode === 404) {
                    throw new \Exception('Calendar not found. Please check your Google Calendar ID.');
                } elseif ($errorCode === 403) {
                    throw new \Exception('Access denied. Please ensure the service account has access to this calendar.');
                } else {
                    throw new \Exception('Calendar access error: ' . $calendarException->getMessage());
                }
            }
        } catch (\Exception $e) {
            updateSetting('is_googlecalendar_sync', '0', createdBy());
            return redirect()->back()->withErrors(['error' => __('Failed to sync Google Calendar: :error', ['error' => $e->getMessage()])]);
        }
    }



    /**
     * Update the storage settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStorage(Request $request)
    {
        try {
            $validated = $request->validate([
                'storage_type' => 'required|in:local,aws_s3,wasabi',
                'allowedFileTypes' => 'required|string',
                'maxUploadSize' => 'required|numeric|min:1',
                // AWS S3 fields
                'awsAccessKeyId' => 'nullable|string',
                'awsSecretAccessKey' => 'nullable|string',
                'awsDefaultRegion' => 'nullable|string',
                'awsBucket' => 'nullable|string',
                'awsUrl' => 'nullable|string',
                'awsEndpoint' => 'nullable|string',
                // Wasabi fields
                'wasabiAccessKey' => 'nullable|string',
                'wasabiSecretKey' => 'nullable|string',
                'wasabiRegion' => 'nullable|string',
                'wasabiBucket' => 'nullable|string',
                'wasabiUrl' => 'nullable|string',
                'wasabiRoot' => 'nullable|string',
            ]);

            // Map form fields to setting keys
            $settingsMap = [
                'storage_type' => 'storage_type',
                'allowedFileTypes' => 'storage_file_types',
                'maxUploadSize' => 'storage_max_upload_size',
                'awsAccessKeyId' => 'aws_access_key_id',
                'awsSecretAccessKey' => 'aws_secret_access_key',
                'awsDefaultRegion' => 'aws_default_region',
                'awsBucket' => 'aws_bucket',
                'awsUrl' => 'aws_url',
                'awsEndpoint' => 'aws_endpoint',
                'wasabiAccessKey' => 'wasabi_access_key',
                'wasabiSecretKey' => 'wasabi_secret_key',
                'wasabiRegion' => 'wasabi_region',
                'wasabiBucket' => 'wasabi_bucket',
                'wasabiUrl' => 'wasabi_url',
                'wasabiRoot' => 'wasabi_root',
            ];

            foreach ($validated as $key => $value) {
                if (isset($settingsMap[$key]) && $value !== null) {
                    updateSetting($settingsMap[$key], $value);
                }
            }

            return redirect()->back()->with('success', __('Storage settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update storage settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Get storage settings for API.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStorageSettings()
    {
        $settings = \App\Services\StorageConfigService::getStorageConfig();

        return response()->json([
            'allowed_file_types' => $settings['allowed_file_types'] ?? 'jpg,png,webp,gif',
            'max_file_size_mb' => $settings['max_file_size_mb'] ?? 2
        ]);
    }

    /**
     * Get email notification settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmailNotifications()
    {
        $userId = createdBy();
        $templates = \App\Models\EmailTemplate::select('id', 'name')->get();
        $settings = [];

        foreach ($templates as $template) {
            $userTemplate = \App\Models\UserEmailTemplate::where('user_id', $userId)
                ->where('template_id', $template->id)
                ->first();

            $settings[$template->name] = $userTemplate ? $userTemplate->is_active : false;
        }

        return response()->json($settings);
    }

    /**
     * Get available email notifications.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableEmailNotifications()
    {
        $templates = \App\Models\EmailTemplate::select('id', 'name')->get();
        $notifications = [];

        foreach ($templates as $template) {
            $notifications[] = [
                'name' => $template->name,
                'label' => str_replace(' ', ' ', $template->name)
            ];
        }

        return response()->json($notifications);
    }

    /**
     * Update email notification settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEmailNotifications(Request $request)
    {
        try {
            $userId = createdBy();
            $availableTemplates = \App\Models\EmailTemplate::pluck('name', 'id')->toArray();

            $rules = [];
            foreach ($availableTemplates as $templateId => $templateName) {
                $rules[$templateName] = 'boolean';
            }

            $validated = $request->validate($rules);

            foreach ($availableTemplates as $templateId => $templateName) {
                if (isset($validated[$templateName])) {
                    \App\Models\UserEmailTemplate::updateOrCreate(
                        ['user_id' => $userId, 'template_id' => $templateId],
                        ['is_active' => $validated[$templateName]]
                    );
                }
            }

            return redirect()->back()->with('success', __('Email notification settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update email notification settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Get Twilio notification settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTwilioNotifications()
    {
        $userId = createdBy();
        $templates = \App\Models\NotificationTemplate::where('type', 'twilio')->select('id', 'name')->get();
        $settings = [];

        foreach ($templates as $template) {
            $userTemplate = \App\Models\UserNotificationTemplate::where('user_id', $userId)
                ->where('template_id', $template->id)
                ->first();

            $settings[$template->name] = $userTemplate ? $userTemplate->is_active : false;
        }

        return response()->json($settings);
    }

    /**
     * Get available Twilio notifications.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableTwilioNotifications()
    {
        $templates = \App\Models\NotificationTemplate::where('type', 'twilio')->select('id', 'name')->get();
        $notifications = [];

        foreach ($templates as $template) {
            $notifications[] = [
                'name' => $template->name,
                'label' => str_replace(' ', ' ', $template->name)
            ];
        }

        return response()->json($notifications);
    }

    /**
     * Get Twilio configuration settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTwilioConfig()
    {
        return response()->json([
            'twilio_sid' => getSetting('twilio_sid', ''),
            'twilio_token' => getSetting('twilio_token', ''),
            'twilio_from' => getSetting('twilio_from', '')
        ]);
    }

    /**
     * Update Twilio notification settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTwilioNotifications(Request $request)
    {
        try {
            $userId = createdBy();
            $availableTemplates = \App\Models\NotificationTemplate::where('type', 'twilio')->pluck('name', 'id')->toArray();

            $rules = [
                'twilio_sid' => 'nullable|string',
                'twilio_token' => 'nullable|string',
                'twilio_from' => 'nullable|string'
            ];

            foreach ($availableTemplates as $templateId => $templateName) {
                $rules[$templateName] = 'boolean';
            }

            $validated = $request->validate($rules);

            // Update Twilio configuration
            updateSetting('twilio_sid', $validated['twilio_sid'] ?? '');
            updateSetting('twilio_token', $validated['twilio_token'] ?? '');
            updateSetting('twilio_from', $validated['twilio_from'] ?? '');

            // Update notification settings
            foreach ($availableTemplates as $templateId => $templateName) {
                if (isset($validated[$templateName])) {
                    \App\Models\UserNotificationTemplate::updateOrCreate(
                        ['user_id' => $userId, 'template_id' => $templateId],
                        ['is_active' => $validated[$templateName]]
                    );
                }
            }

            return redirect()->back()->with('success', __('Twilio settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Twilio settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Send test SMS.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendTestSMS(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string'
            ]);

            $twilio = $this->getTwilioConfig()->getData(true);
            $sid = $twilio['twilio_sid'];
            $token = $twilio['twilio_token'];
            $from = $twilio['twilio_from'];

            if (!$sid || !$token || !$from) {
                return redirect()->back()->with('error', __('Twilio credentials are not configured. Please configure Twilio settings first.'));
            }

            $twilio = new \Twilio\Rest\Client($sid, $token);
            $message = __('This is a test SMS from :app_name. Your Twilio configuration is working correctly!', ['app_name' => config('app.name')]);
            $twilio->messages->create($validated['phone'], [
                'from' => $from,
                'body' => $message
            ]);

            return redirect()->back()->with('success', __('Test SMS sent successfully to :phone', ['phone' => $validated['phone']]));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to send test SMS: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Get Slack notification settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSlackNotifications()
    {
        $userId = createdBy();
        $templates = \App\Models\NotificationTemplate::where('type', 'slack')->select('id', 'name')->get();
        $settings = [];

        foreach ($templates as $template) {
            $userTemplate = \App\Models\UserNotificationTemplate::where('user_id', $userId)
                ->where('template_id', $template->id)
                ->first();

            $settings[$template->name] = $userTemplate ? $userTemplate->is_active : false;
        }

        return response()->json($settings);
    }

    /**
     * Get available Slack notifications.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableSlackNotifications()
    {
        $templates = \App\Models\NotificationTemplate::where('type', 'slack')->select('id', 'name')->get();
        $notifications = [];

        foreach ($templates as $template) {
            $notifications[] = [
                'name' => $template->name,
                'label' => str_replace(' ', ' ', $template->name)
            ];
        }

        return response()->json($notifications);
    }

    /**
     * Get Slack configuration settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSlackConfig()
    {
        return response()->json([
            'slack_webhook_url' => getSetting('slack_webhook_url', '')
        ]);
    }

    /**
     * Update Slack notification settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSlackNotifications(Request $request)
    {
        try {
            $userId = createdBy();
            $availableTemplates = \App\Models\NotificationTemplate::where('type', 'slack')->pluck('name', 'id')->toArray();

            $rules = [
                'slack_webhook_url' => 'nullable|string|url'
            ];

            foreach ($availableTemplates as $templateId => $templateName) {
                $rules[$templateName] = 'boolean';
            }

            $validated = $request->validate($rules);

            // Update Slack configuration
            updateSetting('slack_webhook_url', $validated['slack_webhook_url']);

            // Update notification settings
            foreach ($availableTemplates as $templateId => $templateName) {
                if (isset($validated[$templateName])) {
                    \App\Models\UserNotificationTemplate::updateOrCreate(
                        ['user_id' => $userId, 'template_id' => $templateId],
                        ['is_active' => $validated[$templateName]]
                    );
                }
            }

            return redirect()->back()->with('success', __('Slack settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update Slack settings: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Send test Slack message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendTestSlack(Request $request)
    {
        try {
            $webhookUrl = getSetting('slack_webhook_url', '', createdBy());

            if (!$webhookUrl) {
                return redirect()->back()->with('error', __('Slack webhook URL is not configured. Please configure Slack settings first.'));
            }

            $message = __('This is a test message from :app_name. Your Slack configuration is working correctly!', ['app_name' => config('app.name')]);

            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, [
                'text' => $message
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to send Slack message: ' . $response->body());
            }

            return redirect()->back()->with('success', __('Test message sent successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to send test message: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Clear application cache.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache()
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            \Artisan::call('optimize:clear');

            return redirect()->back()->with('success', __('Cache cleared successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to clear cache: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update invoice template setting
     */
    public function updateInvoiceTemplate(\Illuminate\Http\Request $request)
    {
        if (!auth()->user()->hasRole('company') || !auth()->user()->can('manage-invoices-settings')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $validated = $request->validate([
            'invoiceTemplate' => 'required|string',
            'invoiceColor' => 'required|string',
            'invoiceQrEnabled' => 'boolean',
            'invoiceLogoId' => 'nullable|exists:media,id',
        ]);

        $user = auth()->user();
        $invoiceQrEnabled = ($validated['invoiceQrEnabled'] ?? false) ? 'on' : 'off';

        $settingsToSave = [
            'invoiceTemplate' => $validated['invoiceTemplate'],
            'invoiceColor' => $validated['invoiceColor'],
            'invoiceQrEnabled' => $invoiceQrEnabled,
        ];

        if (!empty($validated['invoiceLogoId'])) {
            $settingsToSave['invoiceLogoId'] = $validated['invoiceLogoId'];
        } else {
            $settingsToSave['invoiceLogoId'] = NULL;
        }

        foreach ($settingsToSave as $key => $data) {
            updateSetting($key, $data, $user->creatorId());
        }

        return response()->json(['success' => __('Invoice template updated successfully!')]);
    }

    /**
     * Update quote template setting
     */
    public function updateQuoteTemplate(Request $request)
    {
        if (!auth()->user()->hasRole('company') || !auth()->user()->can('manage-quotes-settings')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $validated = $request->validate([
            'quoteTemplate' => 'required|string',
            'quoteColor' => 'required|string',
            'quoteQrEnabled' => 'boolean',
            'quoteLogoId' => 'nullable|exists:media,id',
        ]);

        $user = auth()->user();
        $quoteQrEnabled = ($validated['quoteQrEnabled'] ?? false) ? 'on' : 'off';

        $settingsToSave = [
            'quoteTemplate' => $validated['quoteTemplate'],
            'quoteColor' => $validated['quoteColor'],
            'quoteQrEnabled' => $quoteQrEnabled,
        ];

        if (!empty($validated['quoteLogoId'])) {
            $settingsToSave['quoteLogoId'] = $validated['quoteLogoId'];
        } else {
            $settingsToSave['quoteLogoId'] = NULL;
        }

        foreach ($settingsToSave as $key => $data) {
            updateSetting($key, $data, $user->creatorId());
        }

        return response()->json(['success' => __('Quote template updated successfully!')]);
    }

    /**
     * Update sales order template setting
     */
    public function updateSalesOrderTemplate(Request $request)
    {
        if (!auth()->user()->hasRole('company') || !auth()->user()->can('manage-sales-orders-settings')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $validated = $request->validate([
            'salesOrderTemplate' => 'required|string',
            'salesOrderColor' => 'required|string',
            'salesOrderQrEnabled' => 'boolean',
            'salesOrderLogoId' => 'nullable|exists:media,id',
        ]);

        $user = auth()->user();
        $salesOrderQrEnabled = ($validated['salesOrderQrEnabled'] ?? false) ? 'on' : 'off';

        $settingsToSave = [
            'salesOrderTemplate' => $validated['salesOrderTemplate'],
            'salesOrderColor' => $validated['salesOrderColor'],
            'salesOrderQrEnabled' => $salesOrderQrEnabled,
        ];

        if (!empty($validated['salesOrderLogoId'])) {
            $settingsToSave['salesOrderLogoId'] = $validated['salesOrderLogoId'];
        } else {
            $settingsToSave['salesOrderLogoId'] = NULL;
        }

        foreach ($settingsToSave as $key => $data) {
            updateSetting($key, $data, $user->creatorId());
        }

        return response()->json(['success' => __('Sales order template updated successfully!')]);
    }
}
