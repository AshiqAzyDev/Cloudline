<?php

namespace Database\Seeders;

use App\Enums\InvoiceEventType;
use App\Enums\InvoiceStatus;
use App\Enums\VatTreatment;
use App\Models\BillingEntity;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@cloudline.test'],
            [
                'name' => 'Ashiq Admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
        $admin->syncRoles(['admin']);

        $staff = User::query()->updateOrCreate(
            ['email' => 'staff@cloudline.test'],
            [
                'name' => 'Sam Staff',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
        $staff->syncRoles(['staff']);

        $cloudTech = BillingEntity::query()->updateOrCreate(
            ['slug' => 'cloud-technologies'],
            [
                'name' => 'Cloud Technologies',
                'legal_name' => 'Cloud Technologies Ltd',
                'address_line1' => '88 Harbourfront Ave',
                'address_line2' => null,
                'city' => 'London',
                'postcode' => 'E14 5AB',
                'country' => 'United Kingdom',
                'address' => "88 Harbourfront Ave\nLondon\nE14 5AB\nUnited Kingdom",
                'email' => 'billing@cloudtech.example',
                'phone' => '+44 20 7946 0100',
                'vat_number' => 'GB123456789',
                'vat_registered' => true,
                'invoice_prefix' => 'CT',
                'next_invoice_number' => 1,
                'numbering_year' => 2026,
                'default_currency' => 'GBP',
                'default_vat_rate' => 20,
                'default_due_days' => 14,
                'bank_name' => 'Starling Bank',
                'account_name' => 'Cloud Technologies Ltd',
                'sort_code' => '04-00-04',
                'account_number' => '12345678',
                'iban' => 'GB29NWBK60161331926819',
                'bic' => null,
                'bank_details' => "Bank: Starling Bank\nAccount name: Cloud Technologies Ltd\nSort code: 04-00-04\nAccount number: 12345678\nIBAN: GB29NWBK60161331926819",
                'invoice_footer' => 'Cloud Technologies Ltd. Registered in England and Wales.',
                'terms' => 'Payment is due within 14 days of the invoice date.',
                'is_active' => true,
            ],
        );

        $cloudMarketing = BillingEntity::query()->updateOrCreate(
            ['slug' => 'cloud-digital-marketing'],
            [
                'name' => 'Cloud Digital Marketing',
                'legal_name' => 'Cloud Digital Marketing Ltd',
                'address_line1' => '88 Harbourfront Ave',
                'address_line2' => null,
                'city' => 'London',
                'postcode' => 'E14 5AB',
                'country' => 'United Kingdom',
                'address' => "88 Harbourfront Ave\nLondon\nE14 5AB\nUnited Kingdom",
                'email' => 'billing@clouddm.example',
                'phone' => '+44 20 7946 0101',
                'vat_number' => null,
                'vat_registered' => false,
                'invoice_prefix' => 'CDM',
                'next_invoice_number' => 1,
                'numbering_year' => 2026,
                'default_currency' => 'GBP',
                'default_vat_rate' => 0,
                'default_due_days' => 14,
                'bank_name' => 'Starling Bank',
                'account_name' => 'Cloud Digital Marketing Ltd',
                'sort_code' => '04-00-04',
                'account_number' => '87654321',
                'iban' => null,
                'bic' => null,
                'bank_details' => "Bank: Starling Bank\nAccount name: Cloud Digital Marketing Ltd\nSort code: 04-00-04\nAccount number: 87654321",
                'invoice_footer' => 'Cloud Digital Marketing Ltd. Registered in England and Wales.',
                'terms' => 'Payment is due within 14 days of the invoice date.',
                'is_active' => true,
            ],
        );

        foreach ([$cloudTech, $cloudMarketing] as $entity) {
            if ($entity->reminderRules()->count() === 0) {
                foreach ([3, 5, 7] as $i => $days) {
                    $entity->reminderRules()->create([
                        'offset_days' => $days,
                        'is_active' => true,
                        'sort_order' => $i,
                    ]);
                }
            }
        }

        $cloudTech->services()->updateOrCreate(
            ['name' => 'Cloud Technologies — infrastructure'],
            ['description' => 'Infrastructure setup and management', 'default_rate_minor' => Money::toMinor(2500, 'GBP'), 'currency' => 'GBP', 'is_active' => true],
        );
        $cloudTech->services()->updateOrCreate(
            ['name' => 'Cloud Technologies — platform migration'],
            ['description' => 'Platform migration', 'default_rate_minor' => Money::toMinor(6100, 'GBP'), 'currency' => 'GBP', 'is_active' => true],
        );
        $cloudMarketing->services()->updateOrCreate(
            ['name' => 'Cloud Digital Marketing — campaign management'],
            ['description' => 'Campaign management', 'default_rate_minor' => Money::toMinor(1800, 'GBP'), 'currency' => 'GBP', 'is_active' => true],
        );
        $cloudMarketing->services()->updateOrCreate(
            ['name' => 'Cloud Digital Marketing — SEO retainer'],
            ['description' => 'SEO retainer', 'default_rate_minor' => Money::toMinor(1980, 'GBP'), 'currency' => 'GBP', 'is_active' => true],
        );

        $clients = [
            ['company' => 'Nimbus Retail Group', 'contact' => 'Priya Shah', 'email' => 'priya@nimbusretail.test', 'address' => '14 Dockside Rd, Vancouver, BC', 'currency' => 'GBP'],
            ['company' => 'Solace Health', 'contact' => 'Dana Ruiz', 'email' => 'dana@solacehealth.test', 'address' => '9 Elm St, Austin, TX', 'currency' => 'GBP'],
            ['company' => 'Fernwood Studio', 'contact' => 'Marcus Lee', 'email' => 'marcus@fernwoodstudio.test', 'address' => '221 Market St, San Francisco, CA', 'currency' => 'GBP'],
            ['company' => 'Marlin Freight Co.', 'contact' => 'Ella Novak', 'email' => 'ella@marlinfreight.test', 'address' => '77 Harbour Ln, Halifax, NS', 'currency' => 'GBP'],
            ['company' => 'Aster & Co.', 'contact' => 'Tobias King', 'email' => 'tobias@asterco.test', 'address' => '5 Birch Ave, Denver, CO', 'currency' => 'GBP'],
            ['company' => 'Mumbai Apps Pvt Ltd', 'contact' => 'Ananya Rao', 'email' => 'ananya@mumbaiapps.test', 'address' => 'Bandra West, Mumbai', 'currency' => 'INR', 'country' => 'IN', 'vat_treatment' => VatTreatment::OutOfScope],
        ];

        $createdClients = [];
        foreach ($clients as $data) {
            $createdClients[] = Client::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'company' => $data['company'],
                    'contact' => $data['contact'],
                    'address' => $data['address'],
                    'country' => $data['country'] ?? 'GB',
                    'default_currency' => $data['currency'],
                    'vat_treatment' => $data['vat_treatment'] ?? VatTreatment::Standard,
                ],
            );
        }

        // Portal user intentionally not seeded in v1 — public pay links only.
        // Keep client role / users.client_id available for a future portal.

        if (Invoice::query()->count() === 0) {
            $samples = [
                [$cloudTech, $createdClients[0], InvoiceStatus::Paid, '2026-08-12', '2026-08-26', 420000, true, 'Cloud Technologies — infrastructure'],
                [$cloudMarketing, $createdClients[1], InvoiceStatus::Sent, '2026-08-10', '2026-08-24', 285000, false, 'Cloud Digital Marketing — campaign management'],
                [$cloudTech, $createdClients[2], InvoiceStatus::Overdue, '2026-07-30', '2026-08-13', 610000, true, 'Cloud Technologies — platform migration'],
                [$cloudMarketing, $createdClients[3], InvoiceStatus::Paid, '2026-07-22', '2026-08-05', 198000, false, 'Cloud Digital Marketing — SEO retainer'],
                [$cloudTech, $createdClients[4], InvoiceStatus::Overdue, '2026-07-15', '2026-07-29', 340000, true, 'Cloud Technologies — support & maintenance'],
                [$cloudTech, $createdClients[5], InvoiceStatus::Sent, '2026-08-18', '2026-09-01', 15000000, false, 'Custom integration work', 'INR'],
            ];

            $counters = [
                $cloudTech->id => 1,
                $cloudMarketing->id => 1,
            ];

            foreach ($samples as $row) {
                [$entity, $client, $status, $issue, $due, $amount, $vat, $desc] = $row;
                $currency = $row[8] ?? 'GBP';
                $vatRate = $vat ? 20 : 0;
                $vatMinor = $vat ? (int) round($amount * 0.2) : 0;
                $total = $amount + $vatMinor;
                $seq = $counters[$entity->id]++;
                $year = 2026;
                $number = sprintf('%s-%d-%04d', $entity->invoice_prefix, $year, $seq);

                $invoice = Invoice::query()->create([
                    'number' => $number,
                    'billing_entity_id' => $entity->id,
                    'client_id' => $client->id,
                    'created_by' => $admin->id,
                    'currency' => $currency,
                    'status' => $status,
                    'issue_date' => $issue,
                    'due_date' => $due,
                    'subtotal_minor' => $amount,
                    'vat_enabled' => $vat,
                    'vat_rate' => $vatRate,
                    'vat_minor' => $vatMinor,
                    'total_minor' => $total,
                    'amount_paid_minor' => $status === InvoiceStatus::Paid ? $total : 0,
                    'vat_treatment' => $client->vat_treatment,
                    'pay_token' => (string) Str::ulid(),
                    'sent_at' => $status === InvoiceStatus::Draft ? null : $issue.' 10:00:00',
                    'paid_at' => $status === InvoiceStatus::Paid ? $due.' 12:00:00' : null,
                    'total_gbp_minor' => $currency === 'GBP' ? $total : null,
                ]);

                $invoice->items()->create([
                    'description' => $desc,
                    'qty' => 1,
                    'unit_price_minor' => $amount,
                    'amount_minor' => $amount,
                    'sort_order' => 0,
                ]);

                $invoice->recordEvent(InvoiceEventType::Created, [], $admin);

                if ($status === InvoiceStatus::Paid) {
                    $invoice->payments()->create([
                        'amount_minor' => $total,
                        'currency' => $currency,
                        'fee_minor' => (int) round($total * 0.015),
                        'net_minor' => $total - (int) round($total * 0.015),
                        'method' => 'stripe',
                        'received_at' => $due.' 12:00:00',
                    ]);
                }
            }

            foreach ([$cloudTech, $cloudMarketing] as $entity) {
                $entity->next_invoice_number = $counters[$entity->id];
                $entity->save();
            }
        }

        Setting::setValue('default_currency', 'GBP');
        Setting::setValue('default_vat_rate', '20');
        Setting::setValue('default_due_days', '14');
        Setting::setValue('email.invoice_sent.subject', 'Invoice {{invoice_number}} from {{entity_name}}');
        Setting::setValue('email.invoice_sent.body', "Hi {{client_name}},\n\nPlease find invoice {{invoice_number}} for {{amount}} attached. You can pay online here:\n{{pay_url}}\n\nDue date: {{due_date}}\n\nThank you,\n{{entity_name}}");
        Setting::setValue('email.reminder.subject', 'Reminder: invoice {{invoice_number}} is due');
        Setting::setValue('email.reminder.body', "Hi {{client_name}},\n\nThis is a reminder that invoice {{invoice_number}} for {{amount}} is still unpaid. Due date: {{due_date}}.\n\nPay online: {{pay_url}}\n\nThank you,\n{{entity_name}}");
    }
}
