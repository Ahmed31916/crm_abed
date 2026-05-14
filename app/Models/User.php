<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Lab404\Impersonate\Models\Impersonate;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\PayoutRequest;
use App\Services\MailConfigService;
use Illuminate\Support\Facades\Log;

class User extends BaseAuthenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasRoles, HasFactory, Notifiable, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'type',
        'avatar',
        'lang',
        'delete_status',
        'plan_id',
        'plan_expire_date',
        'requested_plan',
        'plan_is_active',
        'is_enable_login',
        'storage_limit',
        'mode',
        'created_by',
        'referral_code',
        'used_referral_code',
        'google2fa_enable',
        'google2fa_secret',
        'status',
        'is_trial',
        'trial_day',
        'trial_expire_date',
        'active_module',
        'commission_amount',
        'invoice_template',
        'product_limit',
        'slug',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan_expire_date' => 'date',
            'trial_expire_date' => 'date',
            'plan_is_active' => 'integer',
            'is_active' => 'integer',
            'is_enable_login' => 'integer',
            'google2fa_enable' => 'integer',
            'storage_limit' => 'float',
            'product_limit' => 'integer',
        ];
    }

    /**
     * Get the creator ID based on user type
     */
    public function creatorId()
    {
        if ($this->type == 'superadmin' || $this->type == 'super admin' || $this->type == 'admin') {
            return $this->id;
        } elseif ($this->type == 'company') {
            return $this->id;
        } else {
            return $this->created_by;
        }
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->type === 'superadmin' || $this->type === 'super admin';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->type === 'admin';
    }

    // Businesses relationship removed

    /**
     * Get the plan associated with the user.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if user is on free plan
     */
    public function isOnFreePlan()
    {
        return $this->plan && $this->plan->is_default;
    }

    /**
     * Get current plan or default plan
     */
    public function getCurrentPlan()
    {
        if ($this->plan) {
            return $this->plan;
        }

        return Plan::getDefaultPlan();
    }

    /**
     * Check if user has an active plan subscription
     */
    public function hasActivePlan()
    {
        return $this->plan_id &&
            $this->plan_is_active &&
            ($this->plan_expire_date === null || $this->plan_expire_date > now());
    }

    /**
     * Check if user's plan has expired
     */
    public function isPlanExpired()
    {
        return $this->plan_expire_date && $this->plan_expire_date < now();
    }

    /**
     * Check if user's trial has expired
     */
    public function isTrialExpired()
    {
        return $this->is_trial && $this->trial_expire_date && $this->trial_expire_date < now();
    }

    /**
     * Check if user needs to subscribe to a plan
     */
    public function needsPlanSubscription()
    {
        if ($this->isSuperAdmin()) {
            return false;
        }

        if ($this->type !== 'company') {
            return false;
        }

        // Check if user has no plan and no default plan exists
        if (!$this->plan_id) {
            return !Plan::getDefaultPlan();
        }

        // Check if trial is expired
        if ($this->isTrialExpired()) {
            return true;
        }

        // Check if plan is expired (but not on trial)
        if (!$this->is_trial && $this->isPlanExpired()) {
            return true;
        }

        return false;
    }

    public function planOrders()
    {
        return $this->hasMany(PlanOrder::class);
    }

    /**
     * Check if user can be impersonated
     */
    public function canBeImpersonated()
    {
        return $this->type === 'company';
    }

    /**
     * Check if user can impersonate others
     */
    public function canImpersonate()
    {
        return $this->isSuperAdmin();
    }

    /**
     * Get referrals made by this company
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class, 'user_id');
    }

    /**
     * Get payout requests made by this company
     */
    public function payoutRequests()
    {
        return $this->hasMany(PayoutRequest::class, 'company_id');
    }

    /**
     * Get the user who created this user
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get user email template settings
     */
    public function userEmailTemplates()
    {
        return $this->hasMany(UserEmailTemplate::class);
    }

    /**
     * Get user notification template settings
     */
    public function userNotificationTemplates()
    {
        return $this->hasMany(UserNotificationTemplate::class);
    }

    /**
     * Get referral balance for company
     */
    public function getReferralBalance()
    {
        $totalEarned = $this->referrals()->sum('amount');
        $totalRequested = $this->payoutRequests()->whereIn('status', ['pending', 'approved'])->sum('amount');
        return $totalEarned - $totalRequested;
    }

    /**
     * Send the email verification notification with dynamic config.
     */
    public function sendEmailVerificationNotification()
    {
        try {
            MailConfigService::setDynamicConfig();
            parent::sendEmailVerificationNotification();
            return ['success' => true, 'message' => 'Verification email sent successfully'];
        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'user_id' => $this->id,
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Failed to send verification email: ' . $e->getMessage()];
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if ($user->type === 'company' && !$user->referral_code) {
                // Generate referral code after the user is saved to get the ID
                static::created(function ($createdUser) {
                    if (!$createdUser->referral_code) {
                        $createdUser->referral_code = str_pad($createdUser->id, 6, '0', STR_PAD_LEFT);
                        $createdUser->save();
                    }
                });
            }
        });

        static::created(function ($user) {
            // Assign default plan to company users if no default plan exists
            if ($user->type === 'company' && !$user->plan_id) {
                $defaultPlan = Plan::getDefaultPlan();
                if ($defaultPlan) {
                    $user->plan_id = $defaultPlan->id;
                    $user->plan_is_active = 1;
                    $user->save();
                }
            }
        });

        static::creating(function ($user) {
            if (empty($user->slug) && in_array($user->type, ['company', 'superadmin', 'super admin'])) {
                $slug = \Illuminate\Support\Str::slug($user->name);
                $originalSlug = $slug;
                $count = 1;

                while (self::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $count;
                    $count++;
                }

                $user->slug = $slug;
            }
        });

        static::updating(function ($user) {
            if ($user->isDirty('name') && in_array($user->type, ['company', 'superadmin', 'super admin'])) {
                $slug = \Illuminate\Support\Str::slug($user->name);
                $originalSlug = $slug;
                $count = 1;

                while (self::where('slug', $slug)->where('id', '!=', $user->id)->exists()) {
                    $slug = $originalSlug . '-' . $count;
                    $count++;
                }

                $user->slug = $slug;
            }
        });
    }

    public function companyDefaultData($company)
    {
        $roles = [
            'sales-manager' => [
                'label' => 'Sales Manager',
                'description' => 'Sales Manager has access to manage sales operations',
                'permissions' => $this->getSalesManagerPermissions(),
            ]
        ];

        foreach ($roles as $name => $data) {
            $role = Role::firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_by' => $company->id,
                ],
                [
                    'label' => $data['label'],
                    'description' => $data['description'],
                    'created_by' => $company->id,
                ]
            );

            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $role->syncPermissions($permissions);
        }
    }

    public function getSalesManagerPermissions()
    {
        $permissions =
            [
                'manage-dashboard',

                'manage-media',
                'manage-own-media',
                'view-media',
                'create-media',
                'edit-media',

                'manage-leads',
                'view-leads',
                'create-leads',
                'edit-leads',
                'delete-leads',
                'convert-leads',
                'import-leads',
                'export-leads',

                'manage-contacts',
                'view-contacts',
                'create-contacts',
                'edit-contacts',
                'delete-contacts',
                'export-contacts',

                'manage-accounts',
                'view-accounts',
                'create-accounts',
                'edit-accounts',
                'delete-accounts',
                'export-accounts',

                'manage-opportunities',
                'view-opportunities',
                'create-opportunities',
                'edit-opportunities',
                'delete-opportunities',
                'export-opportunities',

                'manage-quotes',
                'view-quotes',
                'create-quotes',
                'edit-quotes',
                'delete-quotes',
                'export-quotes',

                'manage-sales-orders',
                'view-sales-orders',
                'create-sales-orders',
                'edit-sales-orders',
                'delete-sales-orders',
                'export-sales-orders',

                'manage-invoices',
                'view-invoices',
                'create-invoices',
                'edit-invoices',
                'delete-invoices',
                'export-invoices',
                'send-reminder-invoices',

                'manage-delivery-orders',
                'view-delivery-orders',
                'create-delivery-orders',
                'edit-delivery-orders',
                'export-delivery-orders',

                'manage-campaigns',
                'view-campaigns',
                'create-campaigns',
                'edit-campaigns',

                'manage-products',
                'view-products',
                'import-products',
                'export-products',

                'manage-categories',
                'view-categories',

                'manage-brands',
                'view-brands',

                'manage-meetings',
                'view-meetings',
                'create-meetings',
                'edit-meetings',
                'delete-meetings',

                'manage-calls',
                'view-calls',
                'create-calls',
                'edit-calls',
                'delete-calls',

                'manage-reports',
                'view-reports',

                'manage-stream',
                'view-stream',
                'delete-stream',

                'manage-notes',
                'view-notes',
                'create-notes',
                'edit-notes',
                'delete-notes',

                'manage-announcements',
                'view-announcements',
            ];
        return $permissions;
    }

    public function getAvatarAttribute($value)
    {
        return check_file($value) ? get_file($value) : get_file('avatars/avatar.png');
    }

    public function getProductCountAttribute()
    {
        return \App\Models\Product::where('created_by', $this->id)->count();
    }

    /**
     * Check if this company user has reached their product creation limit
     */
    public function hasReachedProductLimit()
    {
        if ($this->isSuperAdmin()) {
            return false;
        }

        $limit = $this->product_limit ?? 10;
        $currentCount = $this->product_count;

        return $currentCount >= $limit;
    }

    /**
     * Get the product limit with default fallback
     */
    public function getProductLimitAttribute($value)
    {
        return $value ?? 10;
    }

    /**
     * Get notifications for this company user
     */
    public function companyNotifications()
    {
        return $this->hasMany(\App\Models\CompanyNotification::class, 'company_id');
    }

    /**
     * Get unread notifications count for this company user
     */
    public function unreadNotificationsCount()
    {
        return $this->companyNotifications()->unread()->count();
    }

    /**
     * Get product overrides created by this company user
     */
    public function productOverrides()
    {
        return $this->hasMany(\App\Models\ProductCompanyOverride::class, 'company_id');
    }

    /**
     * Get the route key for the model.
     * يسمح باستخدام slug في Route Model Binding
     * مثال: route('company.profile', $company) يستخدم slug بدل id
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
