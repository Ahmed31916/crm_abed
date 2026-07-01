<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        // ════════════════════════════════════════════════════════════════════
        // ⚡ NEW: Eager-load plan + creator (parent company for staff users)
        // لكي نقدر نبني licenseInfo بدون queries إضافية في الـ view.
        // ════════════════════════════════════════════════════════════════════
        $user = $request->user()->load(['plan', 'creator']);

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            // ⚡ NEW: licenseInfo — يمرّر بيانات اللايسنس كي لصفحة البروفايل
            'licenseInfo' => $this->buildLicenseInfo($user),
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            // Remove _method from validated data if present
            unset($validated['_method']);

            // Remove avatar from validated data if no file is uploaded
            // This prevents setting avatar to null in the database
            if (!$request->hasFile('avatar')) {
                unset($validated['avatar']);
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                $relativePath = str_replace(url('/storage/media') . '/', '', $request?->user()?->avatar);
                if ($request->user()->avatar && check_file($relativePath)) {
                    delete_file($relativePath);
                }

                $filenameWithExt = $request->file('avatar')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('avatar')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'avatar', $fileNameToStore, 'avatars');
                if ($upload['status'] == true) {
                    $validated['avatar'] = $upload['url'];
                } else {
                    return redirect()->back()
                        ->withErrors(['avatar' => $upload['msg']])
                        ->withInput();
                }
            }

            $request->user()->fill($validated);

            if ($request->user()->isDirty('email')) {
                $request->user()->email_verified_at = null;
            }

            $request->user()->save();

            return to_route('profile')->with('success', __('Profile updated successfully.'));
        } catch (\Exception $e) {
            \Log::error('Profile update failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return back()->withErrors(['avatar' => 'Failed to update profile. Please try again.']);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // =========================================================================
    // =========== BUILD LICENSE INFO ==========================================
    // =========================================================================
    // يبني payload بيانات اللايسنس كي لعرضه في صفحة البروفايل.
    //
    // منطق الإظهار:
    //   - superadmin / admin → show = false (ما عندهم license)
    //   - company / staff → show = true (حتى لو ما في license_key، نعرض
    //     الرسالة "No license key assigned yet" مع زر Choose a Plan)
    //
    // الحقول المعروضة:
    //   - status: active | trial | expired | inactive
    //   - licenseKey: الـ key الفعلي (إن وجد)
    //   - licenseId: UUID من Vital API
    //   - planName: اسم الخطة (من relationship plan)
    //   - issuedTo: company_name || name
    //   - belongsToCompany: اسم الشركة الأم (للـ staff) أو null (للـ company)
    //   - subscribedAt: created_at
    //   - expiresAt: plan_expire_date
    //   - daysRemaining: الفرق بالأيام (موجب = متبقي، سالب = منتهي)
    //   - isTrial + trialExpiresAt
    //   - isActivated: هل hardware_id موجود؟
    //   - isLegacy: هل المستخدم قديم (استورد license من desktop)؟
    // =========================================================================
    private function buildLicenseInfo($user): array
    {
        // superadmin و admin ما عندهم license key
        if ($user->isSuperAdmin() ) {
            return ['show' => false];
        }

        $isStaff = $user->type === 'staff';
        $parentCompany = $isStaff ? $user->creator : null;

        // تحديد الحالة (status)
        $now = now();
        $isExpired = $user->plan_expire_date && $user->plan_expire_date < $now;
        $isTrial = (bool) $user->is_trial;
        $trialExpired = $isTrial && $user->trial_expire_date && $user->trial_expire_date < $now;
        $isActive = $user->hasActivePlan();

        $status = 'inactive';
        if ($isActive) {
            $status = $isTrial ? 'trial' : 'active';
        } elseif ($isExpired || $trialExpired) {
            $status = 'expired';
        }

        // حساب الأيام المتبقية
        $expiryDate = $isTrial ? $user->trial_expire_date : $user->plan_expire_date;
        $daysRemaining = null;
        if ($expiryDate) {
            $daysRemaining = (int) $now->diffInDays($expiryDate, false);
        }

        // اسم الشركة الأم (للـ staff)
        $belongsToCompany = null;
        if ($parentCompany) {
            $belongsToCompany = $parentCompany->company_name ?? $parentCompany->name;
        }

        // رمز العملة
        $currencySymbol = function_exists('getSetting') ? getSetting('currency_symbol', '$') : '$';

        return [
            'show' => true,
            'status' => $status,
            'licenseKey' => $user->license_key,
            'licenseId' => $user->license_id,
            'planName' => $user->plan?->name,
            'planPrice' => $user->plan?->price,
            'planDuration' => $user->plan?->duration,
            'currencySymbol' => $currencySymbol,
            'issuedTo' => $user->company_name ?? $user->name,
            'belongsToCompany' => $belongsToCompany,
            'subscribedAt' => $user->created_at?->format('Y-m-d'),
            'expiresAt' => $user->plan_expire_date?->format('Y-m-d'),
            'daysRemaining' => $daysRemaining,
            'isTrial' => $isTrial,
            'trialExpiresAt' => $user->trial_expire_date?->format('Y-m-d'),
            'isActivated' => !empty($user->hardware_id),
            'hardwareId' => $user->hardware_id,
            'isStaff' => $isStaff,
            'isLegacy' => method_exists($user, 'isLegacyUser') ? $user->isLegacyUser() : false,
        ];
    }
}
