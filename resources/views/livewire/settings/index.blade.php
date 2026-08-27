<div class="page">
    <div class="page-title mb-3">Settings</div>
    <div class="mb-3 flex flex-wrap gap-1.5">
        @foreach ([
            'entities' => ['Entities', $canSettings],
            'users' => ['Users', $canUsers],
            'reminders' => ['Reminders', $canSettings],
            'billing' => ['Defaults', $canSettings],
            'stripe' => ['Stripe', $canSettings],
            'emails' => ['Email templates', $canSettings],
        ] as $key => [$label, $allowed])
            @continue(! $allowed)
            <button wire:click="$set('tab', '{{ $key }}')" class="nav-tab {{ $tab === $key ? 'nav-tab-active' : '' }}">{{ $label }}</button>
        @endforeach
    </div>

    <x-error-summary />

    @if ($tab === 'entities')
        @foreach ($entities as $i => $entity)
            <div class="card card-pad mb-3" wire:key="entity-{{ $entity['id'] }}">
                <div class="section-label">{{ $entity['name'] }}</div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="field-label">Name</label><input wire:model="entities.{{ $i }}.name" class="field"></div>
                    <div><label class="field-label">Legal name</label><input wire:model="entities.{{ $i }}.legal_name" class="field"></div>
                    <div><label class="field-label">Email</label><input wire:model="entities.{{ $i }}.email" class="field"></div>
                    <div><label class="field-label">VAT number</label><input wire:model="entities.{{ $i }}.vat_number" class="field"></div>
                    <div><label class="field-label">Prefix</label><input wire:model="entities.{{ $i }}.invoice_prefix" class="field"></div>
                    <div><label class="field-label">Default VAT %</label><input wire:model="entities.{{ $i }}.default_vat_rate" class="field"></div>
                    <div class="col-span-2 flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="entities.{{ $i }}.vat_registered"> VAT registered</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="entities.{{ $i }}.is_active"> Active</label>
                    </div>
                </div>

                <div class="mt-4 border-t border-line pt-3">
                    <div class="section-label">Address</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2"><label class="field-label">Address line 1</label><input wire:model="entities.{{ $i }}.address_line1" class="field" placeholder="Street address"></div>
                        <div class="col-span-2"><label class="field-label">Address line 2</label><input wire:model="entities.{{ $i }}.address_line2" class="field" placeholder="Building, floor (optional)"></div>
                        <div><label class="field-label">City</label><input wire:model="entities.{{ $i }}.city" class="field"></div>
                        <div><label class="field-label">Postcode</label><input wire:model="entities.{{ $i }}.postcode" class="field"></div>
                        <div class="col-span-2"><label class="field-label">Country</label><input wire:model="entities.{{ $i }}.country" class="field" placeholder="United Kingdom"></div>
                    </div>
                </div>

                <div class="mt-4 border-t border-line pt-3">
                    <div class="section-label">Bank details</div>
                    <p class="mb-2 text-[11.5px] text-subtle">Shown on the invoice PDF and payment page for bank transfers.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="field-label">Bank name</label><input wire:model="entities.{{ $i }}.bank_name" class="field"></div>
                        <div><label class="field-label">Account name</label><input wire:model="entities.{{ $i }}.account_name" class="field"></div>
                        <div><label class="field-label">Sort code</label><input wire:model="entities.{{ $i }}.sort_code" class="field" placeholder="00-00-00"></div>
                        <div><label class="field-label">Account number</label><input wire:model="entities.{{ $i }}.account_number" class="field"></div>
                        <div><label class="field-label">IBAN</label><input wire:model="entities.{{ $i }}.iban" class="field"></div>
                        <div><label class="field-label">BIC / SWIFT</label><input wire:model="entities.{{ $i }}.bic" class="field"></div>
                    </div>
                </div>

                <div class="mt-4 border-t border-line pt-3">
                    <label class="field-label">Terms</label>
                    <textarea wire:model="entities.{{ $i }}.terms" rows="2" class="field !min-h-0"></textarea>
                </div>
            </div>
        @endforeach
        <button wire:click="saveEntities" class="btn btn-primary">Save entities</button>

        <div class="card card-pad mt-3">
            <div class="section-label">Add entity</div>
            <p class="mb-3 text-[12.5px] text-subtle">Use this for future brands (e.g. BMGA, Chords &amp; Keys). Each entity gets its own invoice prefix and VAT defaults.</p>
            <div class="grid grid-cols-2 gap-3">
                <input wire:model="newEntity.name" placeholder="Name" class="field">
                <input wire:model="newEntity.legal_name" placeholder="Legal name" class="field">
                <input wire:model="newEntity.email" placeholder="Email" class="field">
                <input wire:model="newEntity.invoice_prefix" placeholder="Prefix (e.g. BMGA)" class="field">
                <input wire:model="newEntity.vat_number" placeholder="VAT number (optional)" class="field">
                <select wire:model="newEntity.default_currency" class="field">
                    @foreach ($currencies as $code => $meta)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
                <label class="col-span-2 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="newEntity.vat_registered"> VAT registered (new invoices default to Include VAT on)</label>
            </div>
            @error('newEntity.invoice_prefix') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('newEntity.name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <button wire:click="addEntity" class="btn btn-primary mt-3">Create entity</button>
        </div>
    @endif

    @if ($tab === 'users')
        <div class="card card-pad mb-3">
            <div class="section-label">Team</div>
            @foreach ($users as $i => $user)
                <div class="mb-3 grid grid-cols-[1fr_1fr_120px_80px] gap-2" wire:key="user-{{ $user['id'] }}">
                    <input wire:model="users.{{ $i }}.name" class="field">
                    <input wire:model="users.{{ $i }}.email" class="field">
                    <select wire:model="users.{{ $i }}.role" class="field">
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                    <label class="flex items-center gap-1 text-sm"><input type="checkbox" wire:model="users.{{ $i }}.is_active"> Active</label>
                </div>
            @endforeach
            @error('users') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <button wire:click="saveUsers" class="btn btn-primary">Save users</button>
        </div>
        <div class="card card-pad mb-3">
            <div class="section-label">Invite team member</div>
            <div class="grid grid-cols-2 gap-3">
                <input wire:model="newUser.name" placeholder="Name" class="field">
                <input wire:model="newUser.email" placeholder="Email" class="field">
                <input type="password" wire:model="newUser.password" placeholder="Password" class="field">
                <select wire:model="newUser.role" class="field">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            @error('newUser.email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <button wire:click="createUser" class="btn btn-primary mt-3">Create user</button>
        </div>
        <div class="card card-pad">
            <div class="section-label">Staff permissions</div>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($permissions as $name => $label)
                    @if ($name !== 'portal.view')
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="staffPermissions" value="{{ $name }}"> {{ $label }}
                        </label>
                    @endif
                @endforeach
            </div>
            <button wire:click="savePermissions" class="btn btn-primary mt-4">Save permissions</button>
        </div>
    @endif

    @if ($tab === 'reminders')
        @foreach ($reminderRules as $g => $group)
            <div class="card card-pad mb-3">
                <div class="section-label">{{ $group['name'] }}</div>
                @foreach ($group['rules'] as $r => $rule)
                    <div class="mb-2 flex items-center gap-3">
                        <span class="text-sm">Send reminder on day</span>
                        <input wire:model="reminderRules.{{ $g }}.rules.{{ $r }}.offset_days" type="number" class="field w-20">
                        <span class="text-sm text-muted">relative to due date</span>
                        <label class="flex items-center gap-1 text-sm"><input type="checkbox" wire:model="reminderRules.{{ $g }}.rules.{{ $r }}.is_active"> On</label>
                    </div>
                @endforeach
                <button wire:click="addRule({{ $g }})" class="btn btn-ghost mt-2">+ Add rule</button>
            </div>
        @endforeach
        <button wire:click="saveReminders" class="btn btn-primary">Save reminder rules</button>
        <p class="mt-3 text-[12.5px] text-subtle">Reminders stop automatically once an invoice is paid.</p>
    @endif

    @if ($tab === 'billing')
        <div class="card card-pad">
            <div class="section-label">Default VAT</div>
            <div class="mb-4 flex items-center gap-2">
                <input wire:model="default_vat_rate" type="number" class="field w-20">
                <span class="text-sm text-label">% — used when VAT is switched on for a new invoice</span>
            </div>
            <div class="mb-4">
                <label class="field-label">Default currency</label>
                <select wire:model="default_currency" class="field max-w-xs">
                    @foreach ($currencies as $code => $meta)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="field-label">Default due days</label>
                <input wire:model="default_due_days" type="number" class="field w-20">
            </div>
            <button wire:click="saveDefaults" class="btn btn-primary">Save defaults</button>
        </div>
    @endif

    @if ($tab === 'stripe')
        <div class="card card-pad">
            <div class="section-label">Stripe</div>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">{{ $stripeConfigured ? 'API keys loaded from .env' : 'Not configured' }}</div>
                    <div class="mt-1 font-mono text-xs text-subtle">{{ config('stripe.key') ? substr(config('stripe.key'), 0, 12).'…' : 'STRIPE_KEY missing' }}</div>
                </div>
                <div class="flex items-center gap-1.5 text-sm">
                    <div class="h-1.5 w-1.5 rounded-full {{ $stripeConfigured ? 'bg-green-600' : 'bg-zinc-400' }}"></div>
                    {{ $stripeConfigured ? 'Connected' : 'Disconnected' }}
                </div>
            </div>
            <p class="mt-4 text-[12.5px] text-subtle">Keys are kept in <code>.env</code> (not stored in the database). Last webhook: {{ $lastWebhook ? \Illuminate\Support\Carbon::parse($lastWebhook)->diffForHumans() : 'never' }}</p>
            <p class="mt-2 text-[12.5px] text-subtle">Invoices are marked Paid automatically the moment a Stripe payment succeeds — no manual reconciliation.</p>
        </div>
    @endif

    @if ($tab === 'emails')
        <div class="card card-pad mb-3">
            <div class="section-label">Invoice sent</div>
            <input wire:model="invoice_sent_subject" class="field mb-3" placeholder="Subject">
            <textarea wire:model="invoice_sent_body" rows="7" class="field"></textarea>
        </div>
        <div class="card card-pad mb-3">
            <div class="section-label">Reminder</div>
            <input wire:model="reminder_subject" class="field mb-3" placeholder="Subject">
            <textarea wire:model="reminder_body" rows="7" class="field"></textarea>
        </div>
        <p class="mb-3 text-xs text-subtle">Placeholders: @{{invoice_number}} @{{client_name}} @{{amount}} @{{pay_url}} @{{due_date}} @{{entity_name}}</p>
        <button wire:click="saveEmails" class="btn btn-primary">Save templates</button>
    @endif
</div>
