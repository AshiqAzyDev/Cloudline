<?php

namespace App\Livewire\Settings;

use App\Models\BillingEntity;
use App\Models\Setting;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\StripeCheckoutService;
use App\Support\Branding;
use App\Support\CurrencyCatalog;
use App\Support\Permissions;
use App\Support\ValidationMessages;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Settings')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url]
    public string $tab = 'entities';

    /** @var array<string, mixed> */
    public array $entityForm = [];

    public ?int $editingEntityId = null;

    public bool $showCreateEntity = false;

    #[Url]
    public string $entitySearch = '';

    #[Url]
    public string $entityStatus = '';

    public array $users = [];

    public array $newUser = ['name' => '', 'email' => '', 'password' => '', 'role' => 'staff'];

    public array $newEntity = [
        'name' => '',
        'legal_name' => '',
        'email' => '',
        'invoice_prefix' => '',
        'vat_number' => '',
        'vat_registered' => true,
        'default_currency' => 'GBP',
    ];

    public array $staffPermissions = [];

    public array $reminderRules = [];

    public string $default_vat_rate = '20';

    public string $default_currency = 'GBP';

    public string $default_due_days = '14';

    public string $invoice_sent_subject = '';

    public string $invoice_sent_body = '';

    public string $reminder_subject = '';

    public string $reminder_body = '';

    /** @var list<array{code: string, name: string, symbol: string, decimals: string, bank_only: bool, fx_rate_to_gbp: string}> */
    public array $currencyRows = [];

    /** @var TemporaryUploadedFile|null */
    public $logoUpload = null;

    /** @var TemporaryUploadedFile|null */
    public $faviconUpload = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE) || auth()->user()->can(Permissions::USERS_MANAGE), 403);
        $this->loadState();
    }

    public function loadState(): void
    {
        $this->users = User::query()->with('roles')->whereNull('client_id')->get()->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()?->name ?? 'staff',
            'is_active' => $user->is_active,
        ])->all();

        $staff = Role::findByName('staff');
        $this->staffPermissions = $staff->permissions->pluck('name')->all();

        $this->default_vat_rate = (string) Setting::getValue('default_vat_rate', config('billing.default_vat_rate'));
        $this->default_currency = (string) Setting::getValue('default_currency', config('billing.default_currency'));
        $this->default_due_days = (string) Setting::getValue('default_due_days', config('billing.default_due_days'));
        $this->invoice_sent_subject = (string) Setting::getValue('email.invoice_sent.subject', '');
        $this->invoice_sent_body = (string) Setting::getValue('email.invoice_sent.body', '');
        $this->reminder_subject = (string) Setting::getValue('email.reminder.subject', '');
        $this->reminder_body = (string) Setting::getValue('email.reminder.body', '');
        $this->currencyRows = CurrencyCatalog::rows();
    }

    public function updatedEntitySearch(): void
    {
        $this->resetPage();
    }

    public function updatedEntityStatus(): void
    {
        $this->resetPage();
    }

    public function saveEntity(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        if ($this->editingEntityId === null) {
            return;
        }

        $this->validate([
            'entityForm.name' => 'required|string|max:255',
            'entityForm.legal_name' => 'required|string|max:255',
            'entityForm.email' => 'required|email|max:255',
            'entityForm.vat_number' => 'nullable|string|max:50',
            'entityForm.vat_registered' => 'boolean',
            'entityForm.invoice_prefix' => 'required|string|max:10',
            'entityForm.default_vat_rate' => 'required|numeric|min:0|max:100',
            'entityForm.default_due_days' => 'required|integer|min:0|max:365',
            'entityForm.default_currency' => ValidationMessages::currencyRule(),
            'entityForm.address_line1' => 'nullable|string|max:255',
            'entityForm.address_line2' => 'nullable|string|max:255',
            'entityForm.city' => 'nullable|string|max:255',
            'entityForm.postcode' => 'nullable|string|max:32',
            'entityForm.country' => 'nullable|string|max:255',
            'entityForm.bank_name' => 'nullable|string|max:255',
            'entityForm.account_name' => 'nullable|string|max:255',
            'entityForm.sort_code' => 'nullable|string|max:32',
            'entityForm.account_number' => 'nullable|string|max:64',
            'entityForm.iban' => 'nullable|string|max:64',
            'entityForm.bic' => 'nullable|string|max:32',
            'entityForm.terms' => 'nullable|string|max:5000',
            'entityForm.is_active' => 'boolean',
        ], [
            'entityForm.name.required' => 'Each entity needs a name.',
            'entityForm.legal_name.required' => 'Each entity needs a legal name.',
            'entityForm.email.required' => 'Each entity needs an email.',
            'entityForm.invoice_prefix.required' => 'Each entity needs an invoice prefix.',
        ]);

        $row = $this->entityForm;
        $structured = [
            'address_line1' => $row['address_line1'] ?: null,
            'address_line2' => $row['address_line2'] ?: null,
            'city' => $row['city'] ?: null,
            'postcode' => $row['postcode'] ?: null,
            'country' => $row['country'] ?: null,
            'bank_name' => $row['bank_name'] ?: null,
            'account_name' => $row['account_name'] ?: null,
            'sort_code' => $row['sort_code'] ?: null,
            'account_number' => $row['account_number'] ?: null,
            'iban' => $row['iban'] ?: null,
            'bic' => $row['bic'] ?: null,
        ];
        $composed = BillingEntity::composeLegacyDetails($structured);

        BillingEntity::query()->findOrFail($this->editingEntityId)->update([
            'name' => $row['name'],
            'legal_name' => $row['legal_name'],
            'email' => $row['email'],
            'vat_number' => $row['vat_number'],
            'vat_registered' => (bool) $row['vat_registered'],
            'invoice_prefix' => $row['invoice_prefix'],
            'default_currency' => $row['default_currency'],
            'default_vat_rate' => $row['default_vat_rate'],
            'default_due_days' => $row['default_due_days'],
            'terms' => $row['terms'],
            'is_active' => (bool) ($row['is_active'] ?? true),
            ...$structured,
            ...$composed,
        ]);

        session()->flash('success', 'Entity saved.');
    }

    public function editEntity(int $id): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $entity = BillingEntity::query()->findOrFail($id);
        $this->entityForm = $this->entityFormFromModel($entity);
        $this->editingEntityId = $entity->id;
        $this->showCreateEntity = false;
        $this->resetErrorBag();
    }

    public function startCreateEntity(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $this->showCreateEntity = true;
        $this->editingEntityId = null;
        $this->entityForm = [];
        $this->resetErrorBag('newEntity');
    }

    public function closeEntityForm(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $this->showCreateEntity = false;
        $this->editingEntityId = null;
        $this->entityForm = [];
        $this->resetErrorBag();
    }

    public function addEntity(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $this->validate([
            'newEntity.name' => 'required|string|max:255',
            'newEntity.legal_name' => 'required|string|max:255',
            'newEntity.email' => 'required|email|max:255',
            'newEntity.invoice_prefix' => 'required|string|max:10|unique:billing_entities,invoice_prefix',
            'newEntity.default_currency' => ValidationMessages::currencyRule(),
            'newEntity.vat_registered' => 'boolean',
        ], [
            'newEntity.name.required' => 'Entity name is required.',
            'newEntity.legal_name.required' => 'Legal name is required.',
            'newEntity.email.required' => 'Email is required.',
            'newEntity.invoice_prefix.required' => 'Invoice prefix is required.',
            'newEntity.invoice_prefix.unique' => 'That invoice prefix is already in use.',
        ]);

        $prefix = strtoupper($this->newEntity['invoice_prefix']);
        $vatRegistered = (bool) $this->newEntity['vat_registered'];

        $entity = BillingEntity::query()->create([
            'name' => $this->newEntity['name'],
            'legal_name' => $this->newEntity['legal_name'],
            'slug' => Str::slug($this->newEntity['name']).'-'.Str::lower(Str::random(4)),
            'email' => $this->newEntity['email'],
            'invoice_prefix' => $prefix,
            'vat_registered' => $vatRegistered,
            'vat_number' => $vatRegistered ? ($this->newEntity['vat_number'] ?: null) : null,
            'default_currency' => $this->newEntity['default_currency'],
            'default_vat_rate' => $vatRegistered ? 20 : 0,
            'default_due_days' => 14,
            'next_invoice_number' => 1,
            'is_active' => true,
            'bank_details' => '',
            'terms' => 'Payment is due within 14 days of the invoice date.',
        ]);

        $this->newEntity = [
            'name' => '',
            'legal_name' => '',
            'email' => '',
            'invoice_prefix' => '',
            'vat_number' => '',
            'vat_registered' => true,
            'default_currency' => 'GBP',
        ];
        $this->showCreateEntity = false;
        $this->entitySearch = '';
        $this->entityStatus = '';
        $this->resetPage();
        $this->entityForm = $this->entityFormFromModel($entity);
        $this->editingEntityId = $entity->id;
        session()->flash('success', 'Entity created.');
    }

    public function saveUsers(): void
    {
        abort_unless(auth()->user()->can(Permissions::USERS_MANAGE), 403);

        $this->validate([
            'users' => 'required|array|min:1',
            'users.*.name' => 'required|string|max:255',
            'users.*.email' => 'required|email|max:255',
            'users.*.role' => ['required', Rule::in(['admin', 'staff'])],
            'users.*.is_active' => 'boolean',
        ], [
            'users.*.name.required' => 'Each user needs a name.',
            'users.*.email.required' => 'Each user needs an email.',
        ]);

        $activeAdmins = collect($this->users)->filter(
            fn ($row) => ($row['role'] ?? '') === 'admin' && ($row['is_active'] ?? false)
        )->count();

        if ($activeAdmins < 1) {
            $this->addError('users', 'At least one active admin is required.');

            return;
        }

        $emails = collect($this->users)->pluck('email')->map(fn ($e) => Str::lower($e));
        if ($emails->count() !== $emails->unique()->count()) {
            $this->addError('users', 'User emails must be unique.');

            return;
        }

        foreach ($this->users as $row) {
            $user = User::query()->findOrFail($row['id']);

            $emailTaken = User::query()
                ->where('email', $row['email'])
                ->where('id', '!=', $user->id)
                ->exists();

            if ($emailTaken) {
                $this->addError('users', "Email {$row['email']} is already in use.");

                return;
            }

            $user->update([
                'name' => $row['name'],
                'email' => $row['email'],
                'is_active' => (bool) $row['is_active'],
            ]);
            $user->syncRoles([$row['role']]);
        }

        session()->flash('success', 'Users saved.');
    }

    public function createUser(): void
    {
        abort_unless(auth()->user()->can(Permissions::USERS_MANAGE), 403);

        $data = $this->validate([
            'newUser.name' => 'required|string|max:255',
            'newUser.email' => 'required|email|unique:users,email',
            'newUser.password' => 'required|min:8',
            'newUser.role' => ['required', Rule::in(['admin', 'staff'])],
        ]);

        $user = User::query()->create([
            'name' => $data['newUser']['name'],
            'email' => $data['newUser']['email'],
            'password' => $data['newUser']['password'],
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole($data['newUser']['role']);
        $this->newUser = ['name' => '', 'email' => '', 'password' => '', 'role' => 'staff'];
        $this->loadState();
        session()->flash('success', 'User created.');
    }

    public function savePermissions(): void
    {
        abort_unless(auth()->user()->can(Permissions::USERS_MANAGE), 403);
        Role::findByName('staff')->syncPermissions($this->staffPermissions);
        session()->flash('success', 'Staff permissions updated.');
    }

    public function saveReminders(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $this->validate([
            'reminderRules' => 'required|array',
            'reminderRules.*.rules' => 'array',
            'reminderRules.*.rules.*.offset_days' => 'required|integer|min:-365|max:365',
            'reminderRules.*.rules.*.is_active' => 'boolean',
        ], [
            'reminderRules.*.rules.*.offset_days.required' => 'Each reminder needs an offset in days.',
        ]);

        foreach ($this->reminderRules as $group) {
            $entity = BillingEntity::query()->findOrFail($group['entity_id']);
            $ids = [];
            foreach ($group['rules'] as $i => $rule) {
                $payload = [
                    'offset_days' => (int) $rule['offset_days'],
                    'is_active' => (bool) $rule['is_active'],
                    'sort_order' => $i,
                ];

                if (! empty($rule['id'])) {
                    $record = $entity->reminderRules()->whereKey($rule['id'])->firstOrFail();
                    $record->update($payload);
                } else {
                    $record = $entity->reminderRules()->create($payload);
                }

                $ids[] = $record->id;
            }
            $entity->reminderRules()->whereNotIn('id', $ids)->delete();
        }

        session()->flash('success', 'Reminder rules saved.');
    }

    public function addRule(int $groupIndex): void
    {
        $this->reminderRules[$groupIndex]['rules'][] = [
            'id' => null,
            'offset_days' => '3',
            'is_active' => true,
        ];
    }

    public function saveDefaults(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $this->validate([
            'default_vat_rate' => 'required|numeric|min:0|max:100',
            'default_currency' => ValidationMessages::currencyRule(),
            'default_due_days' => 'required|integer|min:0|max:365',
        ], [
            'default_vat_rate.required' => 'Default VAT rate is required.',
            'default_due_days.required' => 'Default due days is required.',
        ]);

        Setting::setValue('default_vat_rate', $this->default_vat_rate);
        Setting::setValue('default_currency', $this->default_currency);
        Setting::setValue('default_due_days', $this->default_due_days);
        session()->flash('success', 'Defaults saved.');
    }

    public function addCurrencyRow(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $this->currencyRows[] = [
            'code' => '',
            'name' => '',
            'symbol' => '',
            'decimals' => '2',
            'bank_only' => false,
            'fx_rate_to_gbp' => '',
        ];
    }

    public function removeCurrencyRow(int $index): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        if (count($this->currencyRows) <= 1) {
            $this->addError('currencyRows', 'At least one currency is required.');

            return;
        }

        $code = strtoupper((string) ($this->currencyRows[$index]['code'] ?? ''));

        if ($code !== '' && in_array($code, CurrencyCatalog::inUse(), true)) {
            $this->addError('currencyRows', "Cannot remove {$code} — it is used by invoices, clients, or entities.");

            return;
        }

        unset($this->currencyRows[$index]);
        $this->currencyRows = array_values($this->currencyRows);
    }

    public function saveCurrencies(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $needsApiRates = collect($this->currencyRows)->contains(function (array $row): bool {
            $code = strtoupper((string) ($row['code'] ?? ''));

            return $code !== '' && $code !== 'GBP' && trim((string) ($row['fx_rate_to_gbp'] ?? '')) === '';
        });

        if ($needsApiRates) {
            try {
                app(ExchangeRateService::class)->ensureFresh();
            } catch (\Throwable $e) {
                $this->addError('currencyRows', 'Could not refresh exchange rates: '.$e->getMessage());

                return;
            }
        }

        $this->validate([
            'currencyRows' => 'required|array|min:1',
            'currencyRows.*.code' => 'required|string|size:3|alpha|distinct',
            'currencyRows.*.name' => 'required|string|max:100',
            'currencyRows.*.symbol' => 'required|string|max:8',
            'currencyRows.*.decimals' => 'required|integer|in:0,2',
            'currencyRows.*.bank_only' => 'boolean',
            'currencyRows.*.fx_rate_to_gbp' => 'nullable|numeric|min:0',
        ], [
            'currencyRows.required' => 'Add at least one currency.',
            'currencyRows.*.code.required' => 'Each currency needs a 3-letter code.',
            'currencyRows.*.code.distinct' => 'Currency codes must be unique.',
            'currencyRows.*.name.required' => 'Each currency needs a name.',
            'currencyRows.*.symbol.required' => 'Each currency needs a symbol.',
        ]);

        $codes = collect($this->currencyRows)->pluck('code')->map(fn ($c) => strtoupper((string) $c));
        $inUse = CurrencyCatalog::inUse();
        $removed = array_diff($inUse, $codes->all());

        if ($removed !== []) {
            $this->addError('currencyRows', 'Cannot remove '.implode(', ', $removed).' — still in use on invoices, clients, or entities.');

            return;
        }

        $defaultCurrency = strtoupper((string) Setting::getValue('default_currency', config('billing.default_currency')));
        if (! $codes->contains($defaultCurrency)) {
            Setting::setValue('default_currency', $codes->first());
        }

        foreach ($this->currencyRows as $index => $row) {
            $code = strtoupper((string) ($row['code'] ?? ''));

            if ($code === '' || $code === 'GBP' || trim((string) ($row['fx_rate_to_gbp'] ?? '')) !== '') {
                continue;
            }

            $rate = app(ExchangeRateService::class)->rateToGbp($code);

            if ($rate !== null) {
                $this->currencyRows[$index]['fx_rate_to_gbp'] = rtrim(
                    rtrim(number_format($rate, 8, '.', ''), '0'),
                    '.',
                );
            }
        }

        CurrencyCatalog::saveRows($this->currencyRows);
        $this->currencyRows = CurrencyCatalog::rows();
        session()->flash('success', 'Currencies saved.');
    }

    public function refreshFxRates(ExchangeRateService $exchangeRates): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        if (! $exchangeRates->isConfigured()) {
            $this->addError('currencyRows', 'Add EXCHANGERATE_API_KEY to your .env file first.');

            return;
        }

        try {
            $rates = $exchangeRates->refresh();
        } catch (\Throwable $e) {
            $this->addError('currencyRows', $e->getMessage());

            return;
        }

        foreach ($this->currencyRows as $index => $row) {
            $code = strtoupper((string) ($row['code'] ?? ''));

            if ($code === '' || $code === 'GBP' || trim((string) ($row['fx_rate_to_gbp'] ?? '')) !== '') {
                continue;
            }

            if (isset($rates[$code])) {
                $this->currencyRows[$index]['fx_rate_to_gbp'] = rtrim(
                    rtrim(number_format($rates[$code], 8, '.', ''), '0'),
                    '.',
                );
            }
        }

        session()->flash('success', 'Exchange rates refreshed from API. Review and save currencies to persist manual overrides.');
    }

    public function updatedLogoUpload(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        if (! $this->logoUpload) {
            return;
        }

        $this->persistLogoUpload();
        session()->flash('success', 'Logo saved.');
    }

    public function updatedFaviconUpload(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        if (! $this->faviconUpload) {
            return;
        }

        $this->persistFaviconUpload();
        session()->flash('success', 'Favicon saved.');
    }

    public function saveBranding(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $saved = false;

        if ($this->logoUpload) {
            $this->persistLogoUpload();
            $saved = true;
        }

        if ($this->faviconUpload) {
            $this->persistFaviconUpload();
            $saved = true;
        }

        if (! $saved) {
            $this->addError('logoUpload', 'Choose a logo or favicon file first. Files also save automatically once the upload finishes.');

            return;
        }

        session()->flash('success', 'Branding saved.');
    }

    private function persistLogoUpload(): void
    {
        $this->validateOnly('logoUpload', [
            'logoUpload' => 'required|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ], $this->brandingValidationMessages());

        Branding::setLogoPath($this->logoUpload->store('branding', 'public'));
        $this->logoUpload = null;
    }

    private function persistFaviconUpload(): void
    {
        $this->validateOnly('faviconUpload', [
            'faviconUpload' => 'required|file|mimes:png,ico,svg|max:512',
        ], $this->brandingValidationMessages());

        Branding::setFaviconPath($this->faviconUpload->store('branding', 'public'));
        $this->faviconUpload = null;
    }

    /**
     * @return array<string, string>
     */
    private function brandingValidationMessages(): array
    {
        return [
            'logoUpload.mimes' => 'Logo must be PNG, JPG, SVG, or WebP.',
            'logoUpload.max' => 'Logo must be 2 MB or smaller.',
            'faviconUpload.mimes' => 'Favicon must be PNG, ICO, or SVG.',
            'faviconUpload.max' => 'Favicon must be 512 KB or smaller.',
        ];
    }

    public function removeLogo(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        Branding::removeLogo();
        $this->logoUpload = null;
        session()->flash('success', 'Logo removed.');
    }

    public function removeFavicon(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        Branding::removeFavicon();
        $this->faviconUpload = null;
        session()->flash('success', 'Favicon removed.');
    }

    public function saveEmails(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $this->validate([
            'invoice_sent_subject' => 'required|string|max:255',
            'invoice_sent_body' => 'required|string|max:10000',
            'reminder_subject' => 'required|string|max:255',
            'reminder_body' => 'required|string|max:10000',
        ], [
            'invoice_sent_subject.required' => 'Invoice email subject is required.',
            'invoice_sent_body.required' => 'Invoice email body is required.',
            'reminder_subject.required' => 'Reminder email subject is required.',
            'reminder_body.required' => 'Reminder email body is required.',
        ]);

        Setting::setValue('email.invoice_sent.subject', $this->invoice_sent_subject);
        Setting::setValue('email.invoice_sent.body', $this->invoice_sent_body);
        Setting::setValue('email.reminder.subject', $this->reminder_subject);
        Setting::setValue('email.reminder.body', $this->reminder_body);
        session()->flash('success', 'Email templates saved.');
    }

    public function updatedTab(string $value): void
    {
        $canSettings = auth()->user()->can(Permissions::SETTINGS_MANAGE);
        $canUsers = auth()->user()->can(Permissions::USERS_MANAGE);

        if ($value === 'users' && ! $canUsers) {
            $this->tab = $canSettings ? 'entities' : 'users';
            abort_unless($canUsers || $canSettings, 403);
        }

        if (in_array($value, ['entities', 'reminders', 'billing', 'branding', 'currencies', 'stripe', 'emails'], true) && ! $canSettings) {
            $this->tab = $canUsers ? 'users' : 'entities';
            abort_unless($canSettings || $canUsers, 403);
        }
    }

    public function render(StripeCheckoutService $stripe, ExchangeRateService $exchangeRates)
    {
        $canSettings = auth()->user()->can(Permissions::SETTINGS_MANAGE);
        $canUsers = auth()->user()->can(Permissions::USERS_MANAGE);

        if ($this->tab === 'users' && ! $canUsers) {
            $this->tab = $canSettings ? 'entities' : 'users';
        } elseif ($this->tab !== 'users' && ! $canSettings) {
            $this->tab = 'users';
        }

        if ($this->tab === 'reminders' && $this->reminderRules === []) {
            $this->loadReminderRules();
        }

        return view('livewire.settings.index', [
            'permissions' => Permissions::labels(),
            'stripeConfigured' => $stripe->isConfigured(),
            'lastWebhook' => $stripe->lastWebhookAt(),
            'currencies' => CurrencyCatalog::all(),
            'currenciesInUse' => CurrencyCatalog::inUse(),
            'fxRatesConfigured' => $exchangeRates->isConfigured(),
            'fxRatesUpdatedAt' => $exchangeRates->lastUpdatedAt(),
            'logoUrl' => Branding::logoUrl(),
            'faviconUrl' => Branding::faviconUrl(),
            'canSettings' => $canSettings,
            'canUsers' => $canUsers,
            'entityRows' => $this->entityList(),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, BillingEntity>|null
     */
    private function entityList(): ?LengthAwarePaginator
    {
        if ($this->tab !== 'entities' || $this->showCreateEntity || $this->editingEntityId !== null) {
            return null;
        }

        return BillingEntity::query()
            ->select([
                'id',
                'name',
                'legal_name',
                'email',
                'invoice_prefix',
                'default_currency',
                'vat_registered',
                'is_active',
            ])
            ->when(trim($this->entitySearch) !== '', function ($query) {
                $term = '%'.trim($this->entitySearch).'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('legal_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('invoice_prefix', 'like', $term);
                });
            })
            ->when($this->entityStatus === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->entityStatus === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * @return array<string, mixed>
     */
    private function entityFormFromModel(BillingEntity $entity): array
    {
        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'legal_name' => $entity->legal_name,
            'address_line1' => $entity->address_line1 ?? '',
            'address_line2' => $entity->address_line2 ?? '',
            'city' => $entity->city ?? '',
            'postcode' => $entity->postcode ?? '',
            'country' => $entity->country ?? '',
            'email' => $entity->email,
            'vat_number' => $entity->vat_number,
            'vat_registered' => $entity->vat_registered,
            'invoice_prefix' => $entity->invoice_prefix,
            'default_currency' => $entity->default_currency,
            'default_vat_rate' => (string) $entity->default_vat_rate,
            'default_due_days' => (string) $entity->default_due_days,
            'bank_name' => $entity->bank_name ?? '',
            'account_name' => $entity->account_name ?? '',
            'sort_code' => $entity->sort_code ?? '',
            'account_number' => $entity->account_number ?? '',
            'iban' => $entity->iban ?? '',
            'bic' => $entity->bic ?? '',
            'terms' => $entity->terms,
            'is_active' => $entity->is_active,
        ];
    }

    private function loadReminderRules(): void
    {
        $this->reminderRules = BillingEntity::query()->with('reminderRules')->orderBy('name')->get()->map(fn (BillingEntity $entity) => [
            'entity_id' => $entity->id,
            'name' => $entity->name,
            'rules' => $entity->reminderRules->map(fn ($rule) => [
                'id' => $rule->id,
                'offset_days' => (string) $rule->offset_days,
                'is_active' => $rule->is_active,
            ])->all(),
        ])->all();
    }
}
