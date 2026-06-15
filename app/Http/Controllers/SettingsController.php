<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserTaxesRequest;
use App\Http\Requests\ProfileUploadRequest;
use App\Http\Requests\SaveUserPayoutAccountRequest;
use App\Http\Requests\UpdateUserFlagSettingsRequest;
use App\Http\Requests\UpdateUserProfileSettingsRequest;
use App\Http\Requests\UpdateUserRatesSettingsRequest;
use App\Http\Requests\UpdateUserSettingsRequest;
use App\Http\Requests\VerifyProfileAssetsRequest;
use App\Model\Attachment;
use App\Model\Country;
use App\Model\CreatorOffer;
use App\Model\ReferralCodeUsage;
use App\Model\ReleaseForm;
use App\Model\Subscription;
use App\Model\Transaction;
use App\Model\User;
use App\Model\UserDevice;
use App\Model\UserGender;
use App\Model\UserPayoutAccount;
use App\Model\UserSpotifyAccount;
use App\Model\UserTax;
use App\Model\UserVerify;
use App\Providers\AiServiceProvider;
use App\Providers\AttachmentServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EmailsServiceProvider;
use App\Providers\GenericHelperServiceProvider;
use App\Providers\ProfileMonetizationServiceProvider;
use App\Services\Ai\AiManager;
use App\Services\Ai\Data\AiImageRequest;
use App\Services\Settings\PaymentTransactionPresenter;
use App\Services\Settings\SubscriptionPresenter;
use App\Services\SpotifyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;
use JavaScript;
use Jenssegers\Agent\Agent;
use Ramsey\Uuid\Uuid;

class SettingsController extends Controller
{
    //TODO: Refactor this; split into multiple smaller controllers

    /**
     * Available settings types.
     * Note*: The values are translated over on view side.
     * @var array
     */
    public $availableSettings = [
        'profile' => ['heading' => 'Update your bio, cover and avatar', 'icon' => 'person'],
        'account' => ['heading' => 'Manage your account settings', 'icon' => 'settings'],
        'wallet' => ['heading' => 'Your payments & wallet', 'icon' => 'wallet'],
        'rates' => ['heading' => 'Prices & Bundles', 'icon' => 'layers'],
        'payments' => ['heading' => 'Your payments & wallet', 'icon' => 'card'],
        'subscriptions' => ['heading' => 'Your active subscriptions', 'icon' => 'people'],
        'referrals' => ['heading' => 'Invite other people to earn more', 'icon' => 'person-add'],
        'notifications' => ['heading' => 'Your email notifications settings', 'icon' => 'notifications'],
        'privacy' => ['heading' => 'Your privacy and safety matters', 'icon' => 'shield'],
        'verify' => ['heading' => 'Get verified and start earning now', 'icon' => 'checkmark'],
        'release-forms' => ['heading' => 'Manage release forms for people featured in your content', 'icon' => 'document-text'],
        'tax-information' => ['heading' => 'Complete your tax details to receive payouts', 'icon' => 'cash'],
    ];

    public function __construct()
    {
        if(getSetting('site.hide_identity_checks')){
            unset($this->availableSettings['verify']);
        }

        if(!getSetting('compliance.enable_release_forms', false)) {
            unset($this->availableSettings['release-forms']);
        }

        if(!getSetting('compliance.tax_info_dac7_enabled')) {
            unset($this->availableSettings['tax-information']);
        }

    }

    /**
     * Check if active route is a valid one, based on setting types.
     *
     * @param $route
     * @return bool
     */
    public function checkIfValidRoute($route)
    {
        if ($route) {
            if (!isset($this->availableSettings[$route])) {
                abort(404);
            }
        }

        return true;
    }

    /**
     * Renders the main settings page.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        $this->checkReferralAccess();
        $user = Auth::user();

        $this->filterAvailableSettingsForUser($user);

        if($request->route('type') === 'rates' && !isset($this->availableSettings['rates'])) {
            return redirect()->route('my.settings', ['type' => 'profile']);
        }

        if($request->route('type') === 'subscriptions' && !isset($this->availableSettings['subscriptions'])) {
            return redirect()->route('my.settings', ['type' => 'profile']);
        }

        $this->checkIfValidRoute($request->route('type'));
        $userID = $user->id;
        $data = [];
        switch ($request->route('type')) {
            case 'wallet':
                JavaScript::put([
                    'stripeConfig' => [
                        'stripePublicID' => getSetting('payments.stripe_public_key'),
                    ],
                    'offlinePayments' => [
                        'offline_payments_make_notes_field_mandatory' => (bool) getSetting('payments.offline_payments_make_notes_field_mandatory'),
                        'offline_payments_minimum_attachments_required' => (int) getSetting('payments.offline_payments_minimum_attachments_required'),
                    ],
                    'withdrawalPayoutDetails' => $user->settings['withdrawal_payout_details'] ?? [],
                ]);
                $activeWalletTab = $request->get('active');
                $data['activeTab'] = $activeWalletTab != null ? $activeWalletTab : 'deposit';
                $data['countries'] = Country::query()->where('name', '!=', 'All')->get();
                $data['payoutAccounts'] = UserPayoutAccount::query()
                    ->where('user_id', $userID)
                    ->with('country')
                    ->orderByDesc('is_default')
                    ->latest('id')
                    ->get();
                $data['editingPayoutAccount'] = null;

                if ($request->get('editPayoutAccount')) {
                    $data['editingPayoutAccount'] = $data['payoutAccounts']
                        ->firstWhere('id', (int) $request->get('editPayoutAccount'));
                }
                break;
            case 'subscriptions':

                // Default tab - active subs
                $activeSubsTab = 'subscriptions';
                if($request->get('active')){
                    $activeSubsTab = $request->get('active');
                }

                // Get either active (own) subs or subs paid for
                if($activeSubsTab == 'subscriptions'){
                    $subscriptionsQuery = Subscription::with(['creator', 'subscriber'])->where('sender_user_id', $userID);
                }
                else{
                    $subscriptionsQuery = Subscription::with(['creator', 'subscriber'])->where('recipient_user_id', $userID);
                }
                $subscriptionSummary = $this->getSubscriptionSummary(clone $subscriptionsQuery);
                $subscriptions = $subscriptionsQuery->orderBy('id', 'desc')->paginate(10)->withQueryString();
                $subscribersCount = Subscription::with(['creator'])->where('recipient_user_id', $userID)->orderBy('id', 'desc')->count();
                $subscriptionPresenter = app(SubscriptionPresenter::class);

                $data['subscriptions'] = $subscriptions;
                $data['subscriptionPresentations'] = $subscriptions->getCollection()
                    ->mapWithKeys(function (Subscription $subscription) use ($subscriptionPresenter, $activeSubsTab, $userID) {
                        return [$subscription->id => $subscriptionPresenter->present($subscription, $activeSubsTab, $userID)];
                    })
                    ->all();
                $data['subscriptionSummary'] = $subscriptionSummary;
                $data['subscribersCount'] = $subscribersCount;
                $data['activeSubsTab'] = $activeSubsTab;

                break;
            case 'subscribers':
                $subscribers = Subscription::with(['creator'])->where('recipient_user_id', $userID)->orderBy('id', 'desc')->paginate(2);
                $data['subscribers'] = $subscribers;
                break;
            case 'privacy':
                $devices = UserDevice::where('user_id', $userID)->orderBy('created_at', 'DESC')->get()->map(function ($item) {
                    $agent = new Agent();
                    $agent->setUserAgent($item->agent);
                    $deviceType = 'Desktop';
                    if($agent->isPhone()){
                        $deviceType = 'Mobile';
                    }
                    if($agent->isTablet()){
                        $deviceType = 'Tablet';
                    }
                    $item->setAttribute('device_type', $deviceType);
                    $item->setAttribute('browser', $agent->browser());
                    $item->setAttribute('device', $agent->device());
                    $item->setAttribute('platform', $agent->platform());
                    return $item;
                });
                $data['devices'] = $devices;
                $data['verifiedDevicesCount'] = UserDevice::where('user_id', $userID)->where('verified_at', '<>', null)->count();
                $data['unverifiedDevicesCount'] = UserDevice::where('user_id', $userID)->where('verified_at', null)->count();
                $data['countries'] = Country::all();
                JavaScript::put([
                    'userGeoBlocking' => [
                        'countries' => isset(Auth::user()->settings['geoblocked_countries']) ? json_decode(Auth::user()->settings['geoblocked_countries']) : [],
                        'enabled' => getSetting('security.allow_geo_blocking'),
                    ],
                ]);
                break;
            case 'payments':
                $paymentStatusOptions = $this->getPaymentStatusOptions();
                $paymentTypeOptions = $this->getPaymentTypeOptions();
                $paymentDirectionOptions = $this->getPaymentDirectionOptions();
                $paymentSortOptions = $this->getPaymentSortOptions();

                $paymentFilters = [
                    'status' => $this->normalizeSelectableFilter($request->query('status'), array_keys($paymentStatusOptions)),
                    'type' => $this->normalizeSelectableFilter($request->query('transactionType'), array_keys($paymentTypeOptions)),
                    'direction' => $this->normalizeSelectableFilter($request->query('direction'), array_keys($paymentDirectionOptions)),
                    'sort' => $this->normalizeSelectableFilter($request->query('sort'), array_keys($paymentSortOptions), 'newest'),
                ];

                $paymentsQuery = Transaction::query()
                    ->with(['receiver', 'sender', 'post', 'stream', 'invoice'])
                    ->where(function ($query) use ($userID) {
                        $query->where('sender_user_id', $userID)
                            ->orWhere('recipient_user_id', $userID);
                    });

                $this->applyPaymentFilters($paymentsQuery, $paymentFilters, $userID);
                $paymentSummary = $this->getPaymentSummary(clone $paymentsQuery, $userID);

                $this->applyPaymentSorting($paymentsQuery, $paymentFilters['sort']);

                $payments = $paymentsQuery
                    ->paginate(10)
                    ->withQueryString();
                $paymentPresenter = app(PaymentTransactionPresenter::class);

                $data['payments'] = $payments;
                $data['paymentPresentations'] = $payments->getCollection()
                    ->mapWithKeys(function (Transaction $payment) use ($paymentPresenter, $userID) {
                        return [$payment->id => $paymentPresenter->present($payment, $userID)];
                    })
                    ->all();
                $data['paymentFilters'] = $paymentFilters;
                $data['paymentStatusOptions'] = $paymentStatusOptions;
                $data['paymentTypeOptions'] = $paymentTypeOptions;
                $data['paymentDirectionOptions'] = $paymentDirectionOptions;
                $data['paymentSortOptions'] = $paymentSortOptions;
                $activePaymentFilterCount = collect($paymentFilters)
                    ->filter(function ($value, $key) {
                        if ($key === 'sort') {
                            return $value !== 'newest';
                        }

                        return $value !== 'all';
                    })
                    ->count();

                $data['paymentSummary'] = $paymentSummary;
                $data['activePaymentFilterCount'] = $activePaymentFilterCount;
                $data['hasActivePaymentFilters'] = $activePaymentFilterCount > 0;
                break;
            case null:
            case 'profile':
                JavaScript::put([
                    'bioConfig' => [
                        'allow_profile_bio_markdown' => getSetting('profiles.allow_profile_bio_markdown'),
                        'allow_profile_bio_markdown_links' => getSetting('profiles.allow_hyperlinks'),
                    ],
                ]);
                $data['genders'] = UserGender::all();
                $data['minBirthDate'] = Carbon::now()->subYears(18)->format('Y-m-d');
                $data['countries'] = Country::query()->where('name', '!=', 'All')->get();
                if(getSetting('profiles.social_links_enabled')){
                    $data['profileSocialForm'] = GenericHelperServiceProvider::getProfileSocialFormData();
                }
                // Spotify
                if(getSetting('profiles.spotify_enabled')) {
                    $data['spotifyAccount'] = UserSpotifyAccount::where('user_id', $userID)->first();
                    $data['spotifyAnthem'] = null;
                    if ($data['spotifyAccount'] && $data['spotifyAccount']->anthem_track_id) {
                        try {
                            $spotify = app(SpotifyService::class);
                            $track = $spotify->apiGet($data['spotifyAccount'], '/tracks/'.$data['spotifyAccount']->anthem_track_id);

                            $data['spotifyAnthem'] = [
                                'id' => $track['id'] ?? null,
                                'name' => $track['name'] ?? null,
                                'artist' => data_get($track, 'artists.0.name'),
                                'image' => data_get($track, 'album.images.0.url'),
                                'url' => data_get($track, 'external_urls.spotify'),
                            ];
                        } catch (\Exception $e) {
                            // silently ignore (token expired etc.) - user can re-connect/refresh
                            $data['spotifyAnthem'] = null;
                        }
                    }
                }

                break;
            case 'referrals':
                if(getSetting('payments.referrals_enabled')) {
                    if(empty($user->referral_code)){
                        $user->referral_code = AuthServiceProvider::generateReferralCode(8);
                        $user->save();
                    }
                    $data['referrals'] = ReferralCodeUsage::with(['usedBy'])->where('referral_code', $user->referral_code)->orderBy('id', 'desc')->paginate(6);
                }
                break;
            case 'rates':
                $data['offer'] = $user->offer;
                break;
            case 'verify':
                $request->session()->forget('verifyAssets');
                break;
            case 'release-forms':
                $request->session()->forget('releaseFormAssets');
                $data['releaseForms'] = ReleaseForm::query()
                    ->where('user_id', $userID)
                    ->orderByDesc('id')
                    ->get();
                break;
            case 'tax-information':
                $data['countries'] = Country::query()->where('name', '!=', 'All')->get();
                $data['userTax'] = UserTax::query()->where('user_id', $userID)->orderBy('id', 'desc')->first();
                break;
        }

        return $this->renderSettingView($request->route('type'), $data);
    }

    /**
     * Renders the selected setting type page.
     *
     * @param $route
     * @param array $data
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function renderSettingView($route, $data = [])
    {
        $currentTab = $route ? $route : 'profile';
        $currentSettingTab = $this->availableSettings[$currentTab];
        JavaScript::put(
            [
                'mediaSettings' => [
                    'allowed_file_extensions' => '.'.str_replace(',', ',.', AttachmentServiceProvider::filterExtensions('imagesOnly')),
                    'max_file_upload_size' => (int) getSetting('media.max_file_upload_size'),
                    'manual_payments_file_extensions' => '.'.str_replace(',', ',.', AttachmentServiceProvider::filterExtensions('manualPayments')),
                    'release_forms_file_extensions' => '.'.str_replace(',', ',.', AttachmentServiceProvider::filterExtensions('manualPayments')),
                    'manual_payments_excel_icon' => asset('/img/excel-preview.svg'),
                    'manual_payments_pdf_icon' => asset('/img/pdf-preview.svg'),
                    'initUploader' => (!Auth::user()->verification || (Auth::user()->verification->status !== 'verified' && Auth::user()->verification->status !== 'pending')),
                ],
            ]
        );

        return view('pages.settings', array_merge(
            $data,
            [
                'availableSettings' => $this->availableSettings,
                'currentSettingTab' => $currentSettingTab,
                'activeSettingsTab' => $currentTab,
                'additionalAssets'   => $this->getAdditionalRouteAssets($route),
            ]
        ));
    }

    protected function filterAvailableSettingsForUser(?User $user): void
    {
        if(!ProfileMonetizationServiceProvider::shouldShowRatesForUser($user)) {
            unset($this->availableSettings['rates']);
        }

        if(!ProfileMonetizationServiceProvider::shouldShowSubscriptionsForUser($user)) {
            unset($this->availableSettings['subscriptions']);
        }

        if(!$this->canUseReleaseForms($user)) {
            unset($this->availableSettings['release-forms']);
        }
    }

    protected function canUseReleaseForms(?User $user): bool
    {
        if (!getSetting('compliance.enable_release_forms', false)) {
            return false;
        }

        if (!getSetting('compliance.release_forms_verified_users_only', false)) {
            return true;
        }

        return $user
            && $user->verification
            && $user->verification->status === UserVerify::APPROVED_STATUS;
    }

    protected function getSubscriptionSummary($query): array
    {
        return [
            'total_entries' => (clone $query)->count(),
            'active_entries' => (clone $query)->where('status', Subscription::ACTIVE_STATUS)->count(),
            'ending_soon_entries' => (clone $query)
                ->where('status', Subscription::ACTIVE_STATUS)
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])
                ->count(),
            'active_amount' => (clone $query)
                ->where('status', Subscription::ACTIVE_STATUS)
                ->sum('amount'),
        ];
    }

    protected function getPaymentStatusOptions(): array
    {
        return [
            Transaction::APPROVED_STATUS => __('Approved'),
            Transaction::PENDING_STATUS => __('Pending'),
            Transaction::INITIATED_STATUS => __('Initiated'),
            Transaction::CANCELED_STATUS => __('Canceled'),
            Transaction::REFUNDED_STATUS => __('Refunded'),
            Transaction::PARTIALLY_PAID_STATUS => __('Partially paid'),
            Transaction::DECLINED_STATUS => __('Declined'),
        ];
    }

    protected function getPaymentTypeOptions(): array
    {
        return [
            Transaction::WITHDRAWAL_TYPE => __('Withdrawal'),
            Transaction::DEPOSIT_TYPE => __('Deposit'),
            Transaction::TIP_TYPE => __('Tip'),
            Transaction::CHAT_TIP_TYPE => __('Chat tip'),
            Transaction::POST_UNLOCK => __('Post unlock'),
            Transaction::MESSAGE_UNLOCK => __('Message unlock'),
            Transaction::STREAM_ACCESS => __('Stream access'),
            Transaction::ONE_MONTH_SUBSCRIPTION => __('One-month subscription'),
            Transaction::THREE_MONTHS_SUBSCRIPTION => __('Three-month subscription'),
            Transaction::SIX_MONTHS_SUBSCRIPTION => __('Six-month subscription'),
            Transaction::YEARLY_SUBSCRIPTION => __('Yearly subscription'),
            Transaction::SUBSCRIPTION_RENEWAL => __('Subscription renewal'),
        ];
    }

    protected function getPaymentDirectionOptions(): array
    {
        return [
            'incoming' => __('Incoming'),
            'outgoing' => __('Outgoing'),
            'withdrawals' => __('Withdrawals'),
        ];
    }

    protected function getPaymentSortOptions(): array
    {
        return [
            'newest' => __('Newest first'),
            'oldest' => __('Oldest first'),
            'amount_desc' => __('Highest amount'),
            'amount_asc' => __('Lowest amount'),
        ];
    }

    protected function normalizeSelectableFilter(?string $value, array $allowedValues, string $default = 'all'): string
    {
        if (!$value || !in_array($value, $allowedValues, true)) {
            return $default;
        }

        return $value;
    }

    protected function applyPaymentFilters($query, array $filters, int $userID): void
    {
        if (($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (($filters['type'] ?? 'all') !== 'all') {
            $query->where('type', $filters['type']);
        }

        if (($filters['direction'] ?? 'all') === 'incoming') {
            $query->where('recipient_user_id', $userID)
                ->where(function ($directionQuery) use ($userID) {
                    $directionQuery->whereNull('sender_user_id')
                        ->orWhere('sender_user_id', '!=', $userID);
                });
        }

        if (($filters['direction'] ?? 'all') === 'outgoing') {
            $query->where('sender_user_id', $userID)
                ->where(function ($directionQuery) use ($userID) {
                    $directionQuery->whereNull('recipient_user_id')
                        ->orWhere('recipient_user_id', '!=', $userID);
                });
        }

        if (($filters['direction'] ?? 'all') === 'withdrawals') {
            $query->where('type', Transaction::WITHDRAWAL_TYPE);
        }
    }

    protected function applyPaymentSorting($query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('id', 'asc'),
            'amount_desc' => $query->orderBy('amount', 'desc')->orderBy('id', 'desc'),
            'amount_asc' => $query->orderBy('amount', 'asc')->orderBy('id', 'desc'),
            default => $query->orderBy('id', 'desc'),
        };
    }

    protected function getPaymentSummary($query, int $userID): array
    {
        return [
            'total_entries' => (clone $query)->count(),
            'received_amount' => (clone $query)
                ->where('recipient_user_id', $userID)
                ->where(function ($directionQuery) use ($userID) {
                    $directionQuery->whereNull('sender_user_id')
                        ->orWhere('sender_user_id', '!=', $userID);
                })
                ->sum('amount'),
            'sent_amount' => (clone $query)
                ->where('sender_user_id', $userID)
                ->where(function ($directionQuery) use ($userID) {
                    $directionQuery->whereNull('recipient_user_id')
                        ->orWhere('recipient_user_id', '!=', $userID);
                })
                ->sum('amount'),
            'withdrawal_amount' => (clone $query)
                ->where('type', Transaction::WITHDRAWAL_TYPE)
                ->sum('amount'),
        ];
    }

    /**
     * Custom method for saving profile settings.
     *
     * @param UpdateUserProfileSettingsRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveProfile(UpdateUserProfileSettingsRequest $request)
    {
        $validator = $this->validateUsername($request->get('username'));
        if($validator->fails()){
            return back()->withErrors($validator);
        }

        $user = Auth::user();

        // Ensure settings is an array
        $settings = is_array($user->settings)
            ? $user->settings
            : (array) json_decode($user->settings ?? '[]', true);

        // Build & save connections
        $socialLinks = $request->input('social_links', []);
        if (!is_array($socialLinks)) {
            $socialLinks = [];
        }

        $ai = (array) $request->input('ai', []);

        $traits = $ai['traits'] ?? [];
        if (!is_array($traits)) $traits = [];

        // normalize
        $traits = array_map(static function ($v) {
            $v = trim((string) $v);
            $v = preg_replace('/\s+/', ' ', $v);             // collapse whitespace
            $v = preg_replace('/[^a-zA-Z0-9 _\-]/', '', $v); // strip weird chars (important)
            $v = trim($v);
            return mb_substr($v, 0, 24);
        }, $traits);

        // remove empties, unique, cap 5
        $traits = array_values(array_unique(array_filter($traits, static fn ($v) => $v !== '')));
        $traits = array_slice($traits, 0, 5);

        $settings['ai'] = array_merge(
            [
                'tone' => 'neutral',
                'length' => 'short',
                'share_profile' => false,
                'traits' => [],
            ],
            array_filter([
                'tone' => $ai['tone'] ?? null,
                'length' => $ai['length'] ?? null,
                'share_profile' => (bool) ($ai['share_profile'] ?? false),
                'traits' => $traits,
            ], static fn ($v) => $v !== null)
        );

        $settings['connections'] = $this->buildConnectionsFromSocialLinks($socialLinks);

        // Save profile fields
        $user->update([
            'name' => $request->get('name'),
            'username' => $request->get('username'),
            'bio' => $request->get('bio'),
            'location' => $request->get('location'),
            'website' => $request->get('website'),
            'birthdate' => $request->get('birthdate'),
            'gender_id' => $request->get('gender'),
            'gender_pronoun' => $request->get('pronoun'),
            'country_id' => $request->get('country'),
            'settings' => $settings,
        ]);

        return back()->with('success', __('Settings saved'));
    }

    private function validateUsername($username) {
        $routes = [];

        // You need to iterate over the RouteCollection you receive here
        // to be able to get the paths and add them to the routes list
        foreach (Route::getRoutes()->getRoutes() as $route)
        {
            $routes[] = $route->uri;
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['username' => $username],
            ['username' => 'not_in:'.implode(',', $routes)],
            ['username.*' => __('The selected username is invalid.')]
        );

        return $validator;
    }

    /**
     * Custom method for saving user rates.
     *
     * @param UpdateUserRatesSettingsRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveRates(Request $request)
    {
        $user = Auth::user();
        if (!ProfileMonetizationServiceProvider::shouldShowRatesForUser($user)) {
            return back()->withErrors([
                'profile_access_price' => __('Subscription rates cannot be changed while free profiles only mode is enabled.'),
            ]);
        }

        if ($request->get('is_offer')) {
            $offerExpireDate = $request->get('profile_access_offer_date');
            $currentOffer = CreatorOffer::where('user_id', Auth::user()->id)->first();
            $data = [
                'expires_at' => $offerExpireDate,
            ];

            if ($currentOffer) {
                if ($user->profile_access_price != $request->get('profile_access_price')) {
                    $data['old_profile_access_price'] = $user->profile_access_price;
                }

                if ($user->profile_access_price_6_months != $request->get('profile_access_price_6_months')) {
                    $data['old_profile_access_price_6_months'] = $user->profile_access_price_6_months;
                }

                if ($user->profile_access_price_12_months != $request->get('profile_access_price_12_months')) {
                    $data['old_profile_access_price_12_months'] = $user->profile_access_price_12_months;
                }

                if ($user->profile_access_price_3_months != $request->get('profile_access_price_3_months')) {
                    $data['old_profile_access_price_3_months'] = $user->profile_access_price_3_months;
                }
                $currentOffer->update($data);
            } else {
                $data = [
                    'expires_at' => $offerExpireDate,
                    'old_profile_access_price' => $user->profile_access_price,
                    'old_profile_access_price_6_months' => $user->profile_access_price_6_months,
                    'old_profile_access_price_12_months' => $user->profile_access_price_12_months,
                    'old_profile_access_price_3_months' => $user->profile_access_price_3_months,
                ];
                $data['user_id'] = $user->id;

                CreatorOffer::create($data);
            }
        } else {
            $currentOffer = CreatorOffer::where('user_id', Auth::user()->id)->first();
            if ($currentOffer) {
                $currentOffer->delete();
            }
        }

        $rules = UpdateUserRatesSettingsRequest::getRules();
        $trimmedRules = [];
        foreach($rules as $key => $rule){
            if(($request->get($key) != null) || $key == 'profile_access_price'){
                $trimmedRules[$key] = $rule;
            }
        }

        $request->validate($trimmedRules);

        $user->update([
            'profile_access_price' => $request->get('profile_access_price'),
            'profile_access_price_6_months' => $request->get('profile_access_price_6_months'),
            'profile_access_price_12_months' => $request->get('profile_access_price_12_months'),
            'profile_access_price_3_months' => $request->get('profile_access_price_3_months'),
        ]);

        return back()->with('success', __('Settings saved.'));
    }

    /**
     * Saves one user flag at the time
     * Used for on the fly custom BS switches used on privacy & notifications settings
     * !Must whitelist all allowed keys to be updated!
     *
     * @param UpdateUserFlagSettingsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFlagSettings(UpdateUserFlagSettingsRequest $request)
    {
        $user = Auth::user();
        $key = $request->get('key');
        $value = filter_var($request->get('value'), FILTER_VALIDATE_BOOLEAN);
        if (!in_array($key, ['public_profile', 'paid-profile', 'enable_2fa', 'enable_geoblocking', 'open_profile'])) {
            return response()->json(['success' => false, 'message' => __('Settings not saved')]);
        }
        if($key === 'paid-profile'){
            $key = 'paid_profile';
        }

        if ($key === 'paid_profile' && !ProfileMonetizationServiceProvider::canSetPaidProfile($value)) {
            return response()->json(['success' => false, 'message' => __('This profile type is disabled by the current site settings.')]);
        }

        if ($key === 'open_profile' && $value && !ProfileMonetizationServiceProvider::openProfilesAllowed()) {
            return response()->json(['success' => false, 'message' => __('Open profiles are disabled by the current site settings.')]);
        }

        if($key == 'enable_2fa'){
            if($value){
                $userDevices = UserDevice::where('user_id', $user->id)->get();
                if(count($userDevices) == 0){
                    AuthServiceProvider::addNewUserDevice($user->id, true);
                }
            }
        }

        $data = [
            $key => $value,
        ];

        $user->update($data);

        return response()->json(['success' => true, 'message' => __('Settings saved')]);
    }

    /**
     * Custom method for saving account (password) settings.
     *
     * @param UpdateUserSettingsRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAccount(UpdateUserSettingsRequest $request)
    {
        Auth::user()->update(['password'=>Hash::make($request->input('confirm_password'))]);

        return back()->with('success', __('Settings saved.'));
    }

    /**
     * Method used for injecting additional assets into any desired setting type page.
     *
     * @param $settingRoute
     * @return array
     */
    public function getAdditionalRouteAssets($settingRoute)
    {
        $additionalAssets = ['js' => [], 'css' => []];
        switch ($settingRoute) {
            case 'account':
                $additionalAssets['js'][] = '/js/pages/settings/account.js';
                break;
            case 'wallet':
                $additionalAssets['js'][] = '/libs/@selectize/selectize/dist/js/selectize.min.js';
                $additionalAssets['css'][] = '/libs/@selectize/selectize/dist/css/selectize.css';
                $additionalAssets['css'][] = '/libs/@selectize/selectize/dist/css/selectize.bootstrap4.css';
                $additionalAssets['css'][] = '/css/pages/settings/deposit.css';
                $additionalAssets['js'][] = '/js/pages/settings/deposit.js';
                $additionalAssets['js'][] = '/js/pages/settings/withdrawal.js';
                $additionalAssets['css'][] = '/libs/dropzone/dist/dropzone.css';
                $additionalAssets['js'][] = '/libs/dropzone/dist/dropzone.js';
                $additionalAssets['js'][] = '/js/FileUpload.js';
                break;
            case 'profile':
            case null:
                $additionalAssets['css'][] = '/libs/dropzone/dist/dropzone.css';
                $additionalAssets['js'][] = '/libs/dropzone/dist/dropzone.js';
                $additionalAssets['js'][] = '/js/pages/settings/profile.js';
                if(getSetting('profiles.spotify_enabled')){
                    $additionalAssets['js'][] = '/js/pages/settings/spotify.js';
                }
                $additionalAssets['js'][] = '/libs/@selectize/selectize/dist/js/selectize.min.js';
                $additionalAssets['css'][] = '/libs/@selectize/selectize/dist/css/selectize.css';
                $additionalAssets['css'][] = '/libs/@selectize/selectize/dist/css/selectize.bootstrap4.css';
                break;
            case 'privacy':
                $additionalAssets['js'][] = '/js/pages/settings/privacy.js';
                $additionalAssets['js'][] = '/js/pages/settings/notifications.js';
                $additionalAssets['js'][] = '/libs/@selectize/selectize/dist/js/selectize.min.js';
                $additionalAssets['css'][] = '/libs/@selectize/selectize/dist/css/selectize.css';
                $additionalAssets['css'][] = '/libs/@selectize/selectize/dist/css/selectize.bootstrap4.css';
                break;
            case 'notifications':
                $additionalAssets['js'][] = '/js/pages/settings/notifications.js';
                break;
            case 'subscriptions':
                $additionalAssets['js'][] = '/js/pages/settings/subscriptions.js';
                break;
            case 'verify':
                $additionalAssets['css'][] = '/libs/dropzone/dist/dropzone.css';
                $additionalAssets['js'][] = '/libs/dropzone/dist/dropzone.js';
                $additionalAssets['js'][] = '/js/pages/settings/verify.js';
                $additionalAssets['js'][] = '/js/FileUpload.js';
                break;
            case 'release-forms':
                $additionalAssets['css'][] = '/libs/dropzone/dist/dropzone.css';
                $additionalAssets['js'][] = '/libs/dropzone/dist/dropzone.js';
                $additionalAssets['js'][] = '/js/pages/settings/release-forms.js';
                $additionalAssets['js'][] = '/js/FileUpload.js';
                break;
            case 'rates':
                $additionalAssets['js'][] = '/js/pages/settings/rates.js';
                break;
            case 'referrals':
                $additionalAssets['js'][] = '/js/pages/settings/referrals.js';
                $additionalAssets['css'][] = '/css/pages/referrals.css';
                break;
        }

        return $additionalAssets;
    }

    /**
     * Method used for uploading and saving the profile assets ( avatar & cover ).
     *
     * @param ProfileUploadRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfileAsset(ProfileUploadRequest $request)
    {
        $file = $request->file('file');
        $type = $request->route('uploadType');

        try {
            $directory = 'users/'.$type;
            $s3 = Storage::disk(config('filesystems.defaultFilesystemDriver'));
            $fileId = Uuid::uuid4()->getHex();
            $filePath = $directory.'/'.$fileId.'.jpg';

            $img = Image::make($file);
            if ($type == 'cover') {
                $coverWidth = 599;
                $coverHeight = 180;
                if(getSetting('media.users_covers_size')){
                    $coverSizes = explode('x', getSetting('media.users_covers_size'));
                    $coverWidth = (int)$coverSizes[0];
                    if(isset($coverSizes[1])){
                        $coverHeight = (int)$coverSizes[1];
                    }
                }
                $img->fit($coverWidth, $coverHeight)->orientate();
                $data = ['cover' => $filePath];
            } else {
                $avatarWidth = 96;
                $avatarHeight = 96;
                if(getSetting('media.users_avatars_size')){
                    $sizes = explode('x', getSetting('media.users_avatars_size'));
                    $avatarWidth = (int)$sizes[0];
                    if(isset($sizes[1])){
                        $avatarHeight = (int)$sizes[1];
                    }
                }
                $img->fit($avatarWidth, $avatarHeight)->orientate();
                $data = ['avatar' => $filePath];
            }
            // Resizing the asset
            $img->encode('jpg', 100);
            // Saving to user db
            Auth()->user()->update($data);
            // Saving to disk
            $s3->put($filePath, $img, 'public');
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => ['file'=>$exception->getMessage()]]);
        }

        $assetPath = GenericHelperServiceProvider::getStorageAvatarPath($filePath);
        if($type == 'cover'){
            $assetPath = GenericHelperServiceProvider::getStorageCoverPath($filePath);
        }
        return response()->json(['success' => true, 'assetSrc' => $assetPath]);
    }

    /**
     * Method used for deleting profile asset from db & storage disk.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeProfileAsset(Request $request)
    {
        $type = $request->route('assetType');
        $data = ['avatar' => ''];
        if ($type == 'cover') {
            $data = ['cover' => ''];
        }
        Auth::user()->update($data);

        return response()->json(['success' => true, 'message' => ucfirst($type).' '.__("removed successfully").'.', 'data' => [
            'avatar' => Auth::user()->avatar,
            'cover' => Auth::user()->cover,
        ]]);
    }

    /**
     * General method for saving user fields, they must be valid and fillable fields.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'key'   => ['required', Rule::in([
                    'notification_email_new_post_created',
                    'notification_email_new_sub',
                    'notification_email_new_message',
                    'notification_email_expiring_subs',
                    'notification_email_renewals',
                    'notification_email_new_tip',
                    'notification_email_new_comment',
                    'geoblocked_countries',
                    'notification_email_new_ppv_unlock',
                    'notification_email_creator_went_live',
                    'notification_push_enabled',
                    'notification_toast_enabled',
                ])],
                'value' => ['nullable'],
            ]);

            $user = $request->user();

            $settings = is_array($user->settings)
                ? $user->settings
                : (array) json_decode($user->settings ?? '[]', true);

            $value = $validated['value'];

            if ($validated['key'] === 'notification_push_enabled') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            }

            $settings[$validated['key']] = $value;

            $user->forceFill([
                'settings' => $settings,
            ])->save();

            return response()->json([
                'success' => true,
                'message' => __('Settings saved'),
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => __('Settings not saved'),
                'error'   => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Method used for uploading ID check files.
     *
     * @param VerifyProfileAssetsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyUpload(VerifyProfileAssetsRequest $request)
    {
        $file = $request->file('file');
        try {
            $attachment = AttachmentServiceProvider::createAttachment($file, 'users/verifications');
            if ($request->session()->get('verifyAssets')) {
                $data = json_decode($request->session()->get('verifyAssets'));
                $data[] = $attachment->filename;
                session(['verifyAssets' => json_encode($data)]);
            } else {
                $data = [$attachment->filename];
                session(['verifyAssets' => json_encode($data)]);
            }
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => [$exception->getMessage()]], 500);
        }
        return response()->json([
            'success' => true,
            'attachmentID' => $attachment->id,
            'path' => Storage::url($attachment->filename),
            'type' => AttachmentServiceProvider::getAttachmentType($attachment->type),
            'thumbnail' => AttachmentServiceProvider::getThumbnailPathForAttachmentByResolution($attachment, 150, 150),
        ]);
    }

    /**
     * Delete ID checks assets.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVerifyAsset(Request $request)
    {
        try {
            // Get the asset source from the request
            $assetSrc = $request->get('assetSrc'); // This is the attachment ID

            // Retrieve the 'verifyAssets' session data
            $data = $request->session()->get('verifyAssets');

            if (is_string($data)) {
                $data = json_decode($data, true);
            }

            if (!is_array($data)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['file' => 'Session data is invalid.'],
                ]);
            }

            // Initialize variables
            $foundPath = null;
            $newData = [];

            // Iterate over the session data to find the asset
            foreach ($data as $path) {
                // Use strpos to check if $assetSrc is part of $path
                if (strpos($path, $assetSrc) !== false) {
                    // Found the asset
                    $foundPath = $path;
                    // Skip adding it to newData to remove it
                    continue;
                }
                $newData[] = $path;
            }

            if ($foundPath === null) {
                // Asset not found
                return response()->json([
                    'success' => false,
                    'errors' => ['file' => 'Asset not found in session data.'],
                ]);
            }

            // Update the session data
            $request->session()->put('verifyAssets', json_encode($newData));

            // Delete the Attachment record using the ID
            $file = Attachment::where('user_id', Auth::id())
                ->where('id', $assetSrc)
                ->first();

            if ($file) {
                $file->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'errors' => ['file' => $exception->getMessage()],
            ]);
        }
    }

    /**
     * Send ID check to admin for approval.
     * TODO: Fix the bug when session-ed assets would get hidden | maybe draft them.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveVerifyRequest(Request $request)
    {

        if (getSetting('compliance.enforce_tos_check_on_id_verify')){
            if (!$request->get('terms')) {
                return back()->with('error', __('Please confirm the terms and conditions checkbox'));
            }
        }

        if (getSetting('compliance.enforce_media_agreement_on_id_verify')){
            if (!$request->get('media_checkbox')) {
                return back()->with('error', __('Please confirm the media agreement checkbox.'));
            }
        }

        if ($request->session()->get('verifyAssets')) {
            if (!Auth::user()->verification) {
                UserVerify::create([
                    'user_id' => Auth::user()->id,
                    'files' => $request->session()->get('verifyAssets'),
                ]);
            } else {
                Auth::user()->verification->update(
                    [
                        'user_id' => Auth::user()->id,
                        'files' => $request->session()->get('verifyAssets'),
                        'status' => 'pending',
                    ]
                );
            }

            // Sending out admin email
            $adminEmails = User::where('role_id', 1)->select(['email', 'name'])->get();
            foreach ($adminEmails as $user) {
                EmailsServiceProvider::sendGenericEmail(
                    [
                        'email' => $user->email,
                        'subject' => __('Action required | New identity check'),
                        'title' => __('Hello, :name,', ['name' => $user->name]),
                        'content' => __('There is a new identity check on :siteName that requires your attention.', ['siteName' => getSetting('site.name')]),
                        'button' => [
                            'text' => __('Go to admin'),
                            'url' => url()->route('filament.admin.pages.dashboard'),
                        ],
                    ]
                );
            }

            $request->session()->forget('verifyAssets');

            return back()->with('success', __('Request sent. You will be notified once your verification is processed.'));
        } else {
            return back()->with('error', __('Please attach photos with the front and back sides of your ID.'));
        }
    }

    public function releaseFormUpload(VerifyProfileAssetsRequest $request)
    {
        abort_unless($this->canUseReleaseForms(Auth::user()), 404);

        $file = $request->file('file');

        try {
            $attachment = AttachmentServiceProvider::createAttachment($file, 'users/release-forms');
            $data = $request->session()->get('releaseFormAssets');
            $data = is_string($data) ? json_decode($data, true) : [];
            $data = is_array($data) ? $data : [];
            $data[] = $attachment->filename;

            $request->session()->put('releaseFormAssets', json_encode($data));
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => [$exception->getMessage()]], 500);
        }

        return response()->json([
            'success' => true,
            'attachmentID' => $attachment->id,
            'path' => Storage::url($attachment->filename),
            'type' => AttachmentServiceProvider::getAttachmentType($attachment->type),
            'thumbnail' => AttachmentServiceProvider::getThumbnailPathForAttachmentByResolution($attachment, 150, 150),
        ]);
    }

    public function deleteReleaseFormAsset(Request $request)
    {
        abort_unless($this->canUseReleaseForms(Auth::user()), 404);

        try {
            $assetSrc = $request->get('assetSrc');
            $data = $request->session()->get('releaseFormAssets');
            $data = is_string($data) ? json_decode($data, true) : [];

            if (!is_array($data)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['file' => 'Session data is invalid.'],
                ]);
            }

            $foundPath = null;
            $newData = [];

            foreach ($data as $path) {
                if (strpos($path, $assetSrc) !== false) {
                    $foundPath = $path;
                    continue;
                }

                $newData[] = $path;
            }

            if ($foundPath === null) {
                return response()->json([
                    'success' => false,
                    'errors' => ['file' => 'Asset not found in session data.'],
                ]);
            }

            $request->session()->put('releaseFormAssets', json_encode($newData));

            $file = Attachment::where('user_id', Auth::id())
                ->where('id', $assetSrc)
                ->first();

            if ($file) {
                $file->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'errors' => ['file' => $exception->getMessage()],
            ]);
        }
    }

    public function saveReleaseForm(Request $request)
    {
        abort_unless($this->canUseReleaseForms(Auth::user()), 404);

        $request->validate([
            'title' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $files = $request->session()->get('releaseFormAssets');
        $files = is_string($files) ? json_decode($files, true) : [];

        if (!is_array($files) || empty(array_filter($files))) {
            return back()->with('error', __('Please attach at least one release form file.'));
        }

        ReleaseForm::create([
            'user_id' => Auth::id(),
            'title' => $request->get('title'),
            'notes' => $request->get('notes'),
            'files' => array_values(array_filter($files)),
            'status' => ReleaseForm::PENDING_STATUS,
        ]);

        $adminEmails = User::where('role_id', 1)->select(['email', 'name'])->get();
        foreach ($adminEmails as $user) {
            EmailsServiceProvider::sendGenericEmail(
                [
                    'email' => $user->email,
                    'subject' => __('Action required | New release form'),
                    'title' => __('Hello, :name,', ['name' => $user->name]),
                    'content' => __('There is a new release form on :siteName that requires your attention.', ['siteName' => getSetting('site.name')]),
                    'button' => [
                        'text' => __('Go to admin'),
                        'url' => url()->route('filament.admin.pages.dashboard'),
                    ],
                ]
            );
        }

        $request->session()->forget('releaseFormAssets');

        return back()->with('success', __('Release form submitted. You will be notified once it is reviewed.'));
    }

    public function deleteReleaseForm(ReleaseForm $releaseForm)
    {
        abort_unless($this->canUseReleaseForms(Auth::user()), 404);
        abort_unless((int) $releaseForm->user_id === (int) Auth::id(), 403);

        $releaseForm->delete();

        return back()->with('success', __('Release form deleted.'));
    }

    public static function getCountries() {
        try {
            $countries = Country::all();
            return response()->json(['success' => true, 'data' => $countries]);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'message' => __('Could not fetch countries list.'), 'error' => $exception->getMessage()]);
        }
    }

    protected function checkReferralAccess() {
        $user = Auth::user();
        if(!getSetting('payments.referrals_enabled')){
            unset($this->availableSettings['referrals']);
        }
        if(getSetting('payments.referrals_disable_for_non_verified') && ($user->role_id !== 1)){
            if(!GenericHelperServiceProvider::isUserVerified($user)){
                unset($this->availableSettings['referrals']);
            }
        }
    }

    /**
     * Custom method for saving user tax information.
     *
     * @param AddUserTaxesRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addUserTaxInformation(AddUserTaxesRequest $request)
    {
        $user = Auth::user();
        $existingUserTax = UserTax::query()->where('user_id', $user->id)->orderBy('id', 'desc')->first();
        if($existingUserTax) {
            $existingUserTax->update([
                'legal_name' => $request->get('legalName'),
                'primary_address' => $request->get('primaryAddress'),
                'issuing_country_id' => $request->get('issuingCountry'),
                'tax_type' => $request->get('taxType'),
                'date_of_birth' => $request->get('dateOfBirth'),
                'tax_identification_number' => $request->get('taxIdentificationNumber'),
                'vat_number' => $request->get('vatNumber'),
            ]);
        } else {
            UserTax::create([
                'legal_name' => $request->get('legalName'),
                'primary_address' => $request->get('primaryAddress'),
                'issuing_country_id' => $request->get('issuingCountry'),
                'tax_type' => $request->get('taxType'),
                'date_of_birth' => $request->get('dateOfBirth'),
                'tax_identification_number' => $request->get('taxIdentificationNumber'),
                'vat_number' => $request->get('vatNumber'),
                'user_id' => $user->id,
            ]);
        }

        return back()->with('success', __('Tax information saved.'));
    }

    public function savePayoutAccount(SaveUserPayoutAccountRequest $request)
    {
        $user = Auth::user();
        $payoutAccountMode = $request->input('payout_account_mode') === 'edit' ? 'edit' : 'create';
        $rawPayoutAccountId = $request->input('payout_account_id');
        $payoutAccountId = $payoutAccountMode === 'edit' && filled($rawPayoutAccountId) ? (int) $rawPayoutAccountId : null;

        $payoutAccount = null;

        if ($payoutAccountId) {
            $payoutAccount = UserPayoutAccount::query()
                ->where('user_id', $user->id)
                ->where('id', $payoutAccountId)
                ->first();
        }

        if ($payoutAccountId && !$payoutAccount) {
            return back()->with('error', __('The selected payout account could not be found.'));
        }

        $data = [
            'label' => $request->get('label'),
            'method_key' => UserPayoutAccount::BANK_TRANSFER,
            'account_holder_name' => $request->get('accountHolderName'),
            'iban' => strtoupper(str_replace(' ', '', (string) $request->get('iban'))),
            'swift_bic' => $request->get('swiftBic'),
            'bank_name' => $request->get('bankName'),
            'bank_address' => $request->get('bankAddress'),
            'country_id' => $request->get('countryId'),
            'is_active' => true,
        ];

        if ($payoutAccount) {
            $payoutAccount->update($data);
        } else {
            $payoutAccount = UserPayoutAccount::create($data + [
                'user_id' => $user->id,
            ]);
        }

        $shouldBeDefault = $request->boolean('isDefault')
            || !UserPayoutAccount::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $payoutAccount->id)
                ->where('is_default', true)
                ->exists();

        if ($shouldBeDefault) {
            UserPayoutAccount::query()
                ->where('user_id', $user->id)
                ->update(['is_default' => false]);

            $payoutAccount->update(['is_default' => true]);
        }

        return redirect()
            ->route('my.settings', ['type' => 'wallet', 'active' => 'withdraw'])
            ->with('success', $payoutAccountId ? __('Payout account updated.') : __('Payout account saved.'));
    }

    public function deletePayoutAccount(UserPayoutAccount $payoutAccount)
    {
        $user = Auth::user();

        abort_unless($payoutAccount->user_id === $user->id, 404);

        $wasDefault = $payoutAccount->is_default;
        $payoutAccount->delete();

        if ($wasDefault) {
            $replacement = UserPayoutAccount::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            if ($replacement) {
                $replacement->update(['is_default' => true]);
            }
        }

        return redirect()
            ->route('my.settings', ['type' => 'wallet', 'active' => 'withdraw'])
            ->with('success', __('Payout account deleted.'));
    }

    private function buildConnectionsFromSocialLinks(array $socialLinks): array
    {
        // 1) Catalog from code
        $catalog = config('social_networks', []);

        // 2) Allowed keys from settings (empty => all defaults)
        $enabledKeys = getSetting('profiles.allowed_social_network_keys', []);
        if (empty($enabledKeys)) {
            $enabledKeys = array_keys($catalog);
        }

        // 3) Allowed platforms (key => meta)
        $allowed = array_intersect_key($catalog, array_flip($enabledKeys));
        $allowedKeys = array_keys($allowed);

        $connections = [];
        $seenPlatforms = [];

        foreach ($socialLinks as $row) {
            $platform = trim((string)($row['platform'] ?? ''));
            $value = trim((string)($row['value'] ?? ''));

            if ($platform === '' && $value === '') {
                continue; // empty row
            }

            // Must have both
            if ($platform === '' || $value === '') {
                continue; // ignore partial rows (frontend tries to prevent these anyway)
            }

            // Must be allowed by admin settings
            if (!in_array($platform, $allowedKeys, true)) {
                continue;
            }

            // Prevent duplicates (take the first valid occurrence)
            if (isset($seenPlatforms[$platform])) {
                continue;
            }

            // Normalize URL: prepend https:// if missing
            if (!preg_match('#^https?://#i', $value)) {
                $value = 'https://'.$value;
            }

            // Validate URL
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                continue;
            }

            $seenPlatforms[$platform] = true;

            $connections[$platform] = [
                'url' => $value,
            ];
        }

        return $connections;
    }

    public function generateProfileAsset(Request $request, AiManager $aiManager)
    {
        abort_unless(getSetting('ai.images_enabled'), 404);

        $type = $request->route('assetType');
        abort_unless(in_array($type, ['avatar', 'cover'], true), 404);

        $user = $request->user();

        $locale = data_get($user, 'locale')
            ?? data_get($user, 'settings.locale')
            ?? app()->getLocale();

        app()->setLocale($locale);

        $siteName = getSetting('site.name');
        $promptKey = $type === 'avatar' ? 'ai.images.avatar' : 'ai.images.cover';

        $prompt = __($promptKey, ['siteName' => $siteName]);

        $prompt = $this->augmentImagePromptForUser($prompt, $user, $type);

        $size = $type === 'cover' ? '1792x1024' : '1024x1024';
        $provider = (string) getSetting('ai.image_provider');
        $model = (string) getSetting('ai.image_model');

        $this->logPromptPublic(
            'image',
            $provider,
            $model,
            $prompt,
            $user->id,
            ['assetType' => $type, 'size' => $size]
        );

        try {
            $response = $aiManager->imageProvider($provider)->generateImage(
                new AiImageRequest(
                    prompt: $prompt,
                    model: $model,
                    size: $size,
                ),
                $user
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $b64 = $response->base64;

        if (!$b64) {
            return response()->json([
                'success' => false,
                'message' => 'Image generation failed',
            ], 500);
        }

        $bytes = base64_decode($b64);

        try {
            $directory = 'users/'.($type === 'cover' ? 'cover' : 'avatar');
            $disk = Storage::disk(config('filesystems.defaultFilesystemDriver'));
            $fileId = Uuid::uuid4()->getHex();
            $filePath = $directory.'/'.$fileId.'.jpg';

            $img = Image::make($bytes);

            if ($type === 'cover') {
                $coverWidth = 599;
                $coverHeight = 180;

                if (getSetting('media.users_covers_size')) {
                    $coverSizes = explode('x', getSetting('media.users_covers_size'));
                    $coverWidth = (int) $coverSizes[0];
                    $coverHeight = isset($coverSizes[1]) ? (int) $coverSizes[1] : $coverHeight;
                }

                $img->fit($coverWidth, $coverHeight)->encode('jpg', 100);
                $user->update(['cover' => $filePath]);
                $assetSrc = GenericHelperServiceProvider::getStorageCoverPath($filePath);
            } else {
                $avatarWidth = 96;
                $avatarHeight = 96;

                if (getSetting('media.users_avatars_size')) {
                    $sizes = explode('x', getSetting('media.users_avatars_size'));
                    $avatarWidth = (int) $sizes[0];
                    $avatarHeight = isset($sizes[1]) ? (int) $sizes[1] : $avatarHeight;
                }

                $img->fit($avatarWidth, $avatarHeight)->encode('jpg', 100);
                $user->update(['avatar' => $filePath]);
                $assetSrc = GenericHelperServiceProvider::getStorageAvatarPath($filePath);
            }

            $disk->put($filePath, (string) $img, 'public');

            return response()->json([
                'success' => true,
                'assetSrc' => $assetSrc,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function augmentImagePromptForUser(string $prompt, User $user, string $type): string
    {
        return $prompt;
    }

    protected function logPromptPublic(
        string $kind,
        string $provider,
        string $model,
        string $prompt,
        int $userId,
        array $meta = []
    ): void {
        \Log::channel('ai')->debug('AI prompt', [
            'kind' => $kind,
            'provider' => $provider,
            'model' => $model,
            'user_id' => $userId,
            'meta' => $meta,
            'prompt_len' => mb_strlen($prompt),
            'prompt_sha256' => hash('sha256', $prompt),
            'prompt_preview' => mb_substr($prompt, 0, 300),
        ]);
    }

    public function spotifyIndex(Request $request)
    {
        // just reuse your existing settings renderer
        $request->route()->setParameter('type', 'spotify');
        return $this->index($request);
    }

    public function spotifyRedirect(Request $request, SpotifyService $spotify)
    {
        $state = Str::random(40);
        $request->session()->put('spotify_oauth_state', $state);

        return redirect()->away($spotify->authUrl($state));
    }

    public function spotifyCallback(Request $request, SpotifyService $spotify)
    {
        $state = (string)$request->get('state');
        $saved = (string)$request->session()->pull('spotify_oauth_state');

        if (!$saved || !hash_equals($saved, $state)) {
            return redirect()->route('my.settings', ['type' => 'profile'])
                ->with('error', __('Invalid Spotify state.'));
        }

        $code = (string)$request->get('code');
        if (!$code) {
            return redirect()->route('my.settings', ['type' => 'profile'])
                ->with('error', __('Spotify authorization failed.'));
        }

        $token = $spotify->exchangeCode($code);

        $acc = UserSpotifyAccount::firstOrCreate(['user_id' => Auth::id()]);
        $acc->access_token = $token['access_token'] ?? null;
        $acc->refresh_token = $token['refresh_token'] ?? $acc->refresh_token;
        $acc->expires_at = now()->addSeconds((int)($token['expires_in'] ?? 3600))->subSeconds(30);
        $acc->save();

        // Fetch profile
        $me = $spotify->apiGet($acc, '/me');
        $acc->spotify_id = $me['id'] ?? null;
        $acc->display_name = $me['display_name'] ?? null;
        $acc->avatar = data_get($me, 'images.0.url');
        $acc->save();

        // Snapshot top artists on connect
        $this->spotifyRefreshSnapshot($request, $spotify);

        return redirect()->route('my.settings', ['type' => 'profile'])
            ->with('success', __('Spotify connected.'));
    }

    public function spotifyDisconnect(Request $request)
    {
        UserSpotifyAccount::where('user_id', Auth::id())->delete();

        return response()->json(['success' => true, 'message' => __('Disconnected.')]);
    }

    public function spotifyRefreshSnapshot(Request $request, SpotifyService $spotify)
    {
        if (!getSetting('profiles.spotify_enabled')) {
            return response()->json(['success' => false, 'message' => __('Spotify is disabled')], 403);
        }

        $acc = UserSpotifyAccount::where('user_id', Auth::id())->firstOrFail();

        $want = (int) (getSetting('profiles.spotify_top_artists_limit') ?: 7);
        $want = max(1, min($want, 20));

        $ranges = getSetting('profiles.spotify_top_artists_ranges');
        $ranges = is_array($ranges) ? $ranges : [];

        $allowed = ['short_term', 'medium_term', 'long_term'];
        $ranges = array_values(array_intersect($ranges, $allowed));
        if (empty($ranges)) {
            $ranges = $allowed;
        }

        // Build a larger candidate pool, then randomize
        $pool = [];
        $seen = [];

        // how many pages to sample per range (1 = fast; 2 = more variety)
        $pagesPerRange = 2;
        $pageSize = 50; // Spotify max

        foreach ($ranges as $range) {
            for ($p = 0; $p < $pagesPerRange; $p++) {
                // random offset within the first 50-ish results
                // (Spotify top-items supports offset; max list length isn't exposed, but offset 0/20/30 works fine)
                $offsetCandidates = [0, 10, 20, 30];
                $offset = $offsetCandidates[array_rand($offsetCandidates)];

                $res = $spotify->apiGet($acc, '/me/top/artists', [
                    'limit' => $pageSize,
                    'offset' => $offset,
                    'time_range' => $range,
                ]);

                foreach (($res['items'] ?? []) as $a) {
                    $id = $a['id'] ?? null;
                    if (!$id || isset($seen[$id])) continue;

                    $seen[$id] = true;

                    $pool[] = [
                        'id' => $id,
                        'name' => $a['name'] ?? null,
                        'url' => data_get($a, 'external_urls.spotify'),
                        'image' => data_get($a, 'images.0.url'),
                    ];
                }
            }
        }

        // Randomize and pick $want
        if (!empty($pool)) {
            shuffle($pool);
        }
        $out = array_slice($pool, 0, $want);

        $acc->top_artists = $out;
        $acc->save();

        return response()->json(['success' => true, 'count' => count($out), 'data' => $out]);
    }

    public function spotifySearchTracks(Request $request, SpotifyService $spotify)
    {
        $q = trim((string)$request->get('q', ''));
        if ($q === '') {
            return response()->json(['success' => true, 'data' => []]);
        }

        $acc = UserSpotifyAccount::where('user_id', Auth::id())->firstOrFail();

        $res = $spotify->apiGet($acc, '/search', [
            'q' => $q,
            'type' => 'track',
            'limit' => 8,
        ]);

        $tracks = collect(data_get($res, 'tracks.items', []))->map(function ($t) {
            return [
                'id' => $t['id'] ?? null,
                'name' => $t['name'] ?? null,
                'artist' => data_get($t, 'artists.0.name'),
                'image' => data_get($t, 'album.images.0.url'),
                'url' => data_get($t, 'external_urls.spotify'),
            ];
        })->values()->all();

        return response()->json(['success' => true, 'data' => $tracks]);
    }

    public function spotifySetAnthem(Request $request, SpotifyService $spotify)
    {
        $request->validate([
            'track_id' => 'required|string',
        ]);

        $acc = UserSpotifyAccount::where('user_id', Auth::id())->firstOrFail();

        // validate track exists (optional but nice)
        $track = $spotify->apiGet($acc, '/tracks/'.$request->track_id);

        $acc->anthem_track_id = $track['id'] ?? $request->track_id;
        $acc->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $track['id'] ?? null,
                'name' => $track['name'] ?? null,
                'artist' => data_get($track, 'artists.0.name'),
                'image' => data_get($track, 'album.images.0.url'),
                'url' => data_get($track, 'external_urls.spotify'),
            ],
        ]);
    }
}
