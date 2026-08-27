<?php

namespace App\Livewire\Settings;

use App\Models\BillingEntity;
use App\Models\Setting;
use App\Models\User;
use App\Services\StripeCheckoutService;
use App\Support\Permissions;
use App\Support\ValidationMessages;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Settings')]
class Index extends Component
{
    #[Url]
    public string $tab = 'entities';

    public array $entities = [];

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

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE) || auth()->user()->can(Permissions::USERS_MANAGE), 403);
        $this->loadState();
    }

    public function loadState(): void
    {
        $this->entities = BillingEntity::query()->get()->map(fn (BillingEntity $entity) => [
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
        ])->all();

        $this->users = User::query()->with('roles')->whereNull('client_id')->get()->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()?->name ?? 'staff',
            'is_active' => $user->is_active,
        ])->all();

        $staff = Role::findByName('staff');
        $this->staffPermissions = $staff->permissions->pluck('name')->all();

        $this->reminderRules = BillingEntity::query()->with('reminderRules')->get()->map(fn (BillingEntity $entity) => [
            'entity_id' => $entity->id,
            'name' => $entity->name,
            'rules' => $entity->reminderRules->map(fn ($rule) => [
                'id' => $rule->id,
                'offset_days' => (string) $rule->offset_days,
                'is_active' => $rule->is_active,
            ])->all(),
        ])->all();

        $this->default_vat_rate = (string) Setting::getValue('default_vat_rate', config('billing.default_vat_rate'));
        $this->default_currency = (string) Setting::getValue('default_currency', config('billing.default_currency'));
        $this->default_due_days = (string) Setting::getValue('default_due_days', config('billing.default_due_days'));
        $this->invoice_sent_subject = (string) Setting::getValue('email.invoice_sent.subject', '');
        $this->invoice_sent_body = (string) Setting::getValue('email.invoice_sent.body', '');
        $this->reminder_subject = (string) Setting::getValue('email.reminder.subject', '');
        $this->reminder_body = (string) Setting::getValue('email.reminder.body', '');
    }

    public function saveEntities(): void
    {
        abort_unless(auth()->user()->can(Permissions::SETTINGS_MANAGE), 403);

        $this->validate([
            'entities' => 'required|array|min:1',
            'entities.*.name' => 'required|string|max:255',
            'entities.*.legal_name' => 'required|string|max:255',
            'entities.*.email' => 'required|email|max:255',
            'entities.*.vat_number' => 'nullable|string|max:50',
            'entities.*.vat_registered' => 'boolean',
            'entities.*.invoice_prefix' => 'required|string|max:10',
            'entities.*.default_vat_rate' => 'required|numeric|min:0|max:100',
            'entities.*.default_due_days' => 'required|integer|min:0|max:365',
            'entities.*.default_currency' => ValidationMessages::currencyRule(),
            'entities.*.address_line1' => 'nullable|string|max:255',
            'entities.*.address_line2' => 'nullable|string|max:255',
            'entities.*.city' => 'nullable|string|max:255',
            'entities.*.postcode' => 'nullable|string|max:32',
            'entities.*.country' => 'nullable|string|max:255',
            'entities.*.bank_name' => 'nullable|string|max:255',
            'entities.*.account_name' => 'nullable|string|max:255',
            'entities.*.sort_code' => 'nullable|string|max:32',
            'entities.*.account_number' => 'nullable|string|max:64',
            'entities.*.iban' => 'nullable|string|max:64',
            'entities.*.bic' => 'nullable|string|max:32',
            'entities.*.terms' => 'nullable|string|max:5000',
            'entities.*.is_active' => 'boolean',
        ], [
            'entities.*.name.required' => 'Each entity needs a name.',
            'entities.*.legal_name.required' => 'Each entity needs a legal name.',
            'entities.*.email.required' => 'Each entity needs an email.',
            'entities.*.invoice_prefix.required' => 'Each entity needs an invoice prefix.',
        ]);

        foreach ($this->entities as $row) {
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

            BillingEntity::query()->findOrFail($row['id'])->update([
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
        }

        session()->flash('success', 'Entities saved.');
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

        BillingEntity::query()->create([
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
        $this->loadState();
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

        if (in_array($value, ['entities', 'reminders', 'billing', 'stripe', 'emails'], true) && ! $canSettings) {
            $this->tab = $canUsers ? 'users' : 'entities';
            abort_unless($canSettings || $canUsers, 403);
        }
    }

    public function render(StripeCheckoutService $stripe)
    {
        $canSettings = auth()->user()->can(Permissions::SETTINGS_MANAGE);
        $canUsers = auth()->user()->can(Permissions::USERS_MANAGE);

        if ($this->tab === 'users' && ! $canUsers) {
            $this->tab = $canSettings ? 'entities' : 'users';
        } elseif ($this->tab !== 'users' && ! $canSettings) {
            $this->tab = 'users';
        }

        return view('livewire.settings.index', [
            'permissions' => Permissions::labels(),
            'stripeConfigured' => $stripe->isConfigured(),
            'lastWebhook' => $stripe->lastWebhookAt(),
            'currencies' => config('billing.currencies'),
            'canSettings' => $canSettings,
            'canUsers' => $canUsers,
        ]);
    }
}
