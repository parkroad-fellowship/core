# Handoff: Treasurer web workspace (Filament) and workbook import

> **Audience:** the agent who builds the treasurer's web interface on the finance backend that already exists.
> **Primary user:** the treasurer, in the tenant admin panel (`/admin`). The chair and vice chair get read-only access.
> **Rule of thumb:** every treasurer task must be possible in the web panel. The API only exists for the mobile apps.

---

## 0. Context: what exists and what you are building

The treasurer used to run the fellowship's money in two Excel workbooks. They are **not** in git and must never be committed; `/*.xlsx` is in `.gitignore`. One of them holds live credentials in its "Handover" sheet.
- **`PRF Financials 2026.xlsx`**: one cashbook sheet per account (Paybill, M-Pesa, Bank, Cash, M-Shwari), with a Desk/Category column. These roll up into Cash Balances, the Income Statement and a Treasurer Report.
- **`2026 Monthly Missions Financials_Packroad.xlsx`**: one sheet per month, one row per mission, with these columns: disbursed, expenses by category, token of appreciation, surplus to refund, refund done, balance, remarks.

### Backend already built and tested (do not rebuild)
The file names below are the source of truth, so read them before starting.

| Area | Files | What it does |
|---|---|---|
| Chart of accounts | `config/prf/finance.php`, `database/seeders/{FinancialAccount,LedgerCategory,TenantReferenceData}Seeder.php`, `app/Services/Finance/ChartOfAccounts.php` | Default accounts and categories. Seeded when a tenant is provisioned (`ProvisionTenantJob`) and by `prf:tenants:seed-reference-data`. Categories that have a `code` are posted to by the app, so they can be renamed but never deleted (`LedgerCategoryPolicy`). |
| Models | `FinancialAccount`, `LedgerCategory`, `LedgerEntry`, `AccountTransfer`, `ReceiptDelivery`, `FinancialReport`, `LedgerImport` | All tenant-scoped with ULIDs, soft deletes, factories and policies. Permissions are in `config/prf/roles.php` (treasurer and super admin: full; chair and vice chair: view, plus create financial report). |
| Enums | `PRFFinancialAccountType`, `PRFLedgerFlow`, `PRFLedgerCategoryKind`, `PRFLedgerChannel`, `PRFReceiptChannel`, `PRFDeliveryStatus`, `PRFReconciliationStatus`, `PRFFinancialReportType`, `PRFProcessingStatus`, `PRFPledgeInstallmentMethod` (extended) | New enums implement Filament's `HasLabel`/`HasColor`/`HasIcon`, so `->badge()` and `Select::make()->options(Enum::class)` work directly. Helpers come from `App\Enums\Concerns\HasEnumHelpers`. |
| Ledger | `app/Services/Finance/Ledger.php` | `post()` is the only writer of `ledger_entries`. It numbers income receipts (`PRF-2026-000001`), defaults flow and channel, and uses `source_key` for idempotency. It also has `postPayment()` (Paystack gross amount plus fee), `postDisbursement()`, `postToken()` and `postRefund()`. `postRefund()` counts outstanding tokens as income first and only the remainder as a refund. |
| Jobs (call these from Filament) | `app/Jobs/LedgerEntry/{CreateJob,UpdateJob}`, `app/Jobs/AccountTransfer/{CreateJob,UpdateJob}`, `app/Jobs/ReceiptDelivery/{CreateJob,DeliverJob}`, `app/Jobs/FinancialReport/{CreateJob,GenerateJob}`, `app/Jobs/Pledge/RecordInstallmentJob`, `app/Jobs/Requisition/ApproveJob`, `app/Jobs/Refund/CreateJob`, `app/Jobs/AllocationEntry/AddTokenJob`, `app/Jobs/AccountingEvent/UpdateJob` | Input keys are listed in section 2. |
| Auto-posting | `app/Listeners/Payment/PostPaymentToLedger.php`, `app/Listeners/LedgerEntry/SendIncomeReceipt.php` | Paystack success posts the gross amount plus the fee. Receipted income with `send_receipt` emails the PDF and sends an SMS link. |
| Receipts | `app/Services/Finance/ReceiptDocument.php`, `resources/views/prf/finance/receipt-pdf.blade.php`, route `receipts.show` (signed, `/receipts/{tenant}/{ulid}`) | `pdf($entry)` returns the bytes, `url($entry)` the signed link, `whatsAppURL($entry, ?$phone)` the `wa.me` link, and `filename($entry)` the file name. |
| Statements | `app/Services/Finance/FinancialStatements.php` | `cashBalances($from, $to)`, `incomeStatement($from, $to)` and `incomeDistribution($from, $to)`. |
| Accountability | `app/Services/Finance/AccountabilityService.php`, `AccountabilityRow.php` | `forMonth($month, ?$desk)` returns one row per accounting event (missions and PRF Events of every desk). Each row exposes `disbursed`, `expenses[categoryId]`, `transactionCosts`, `tokens`, `toRefund()`, `refunded`, `balance()`, `status()`, `suggestedStatus()` and `remarks()`. `expenseColumns($rows)` gives the expense categories to use as columns. |
| Impact | `app/Services/Finance/ImpactSummaryService.php` | `for($from, $to)` and `yearToDate()` return missions served, students reached, souls by decision, members in discipleship, and income. |
| Excel | `app/Exports/Finance/{CashbookExport,MonthlyAccountabilityExport,IncomeDistributionExport}.php`, plus `Sheets/*` | Styled like the samples, with live formulas. They are generated through `FinancialReport` → `GenerateJob` and stored on the tenant's `local` disk. The download route is `financial-reports.download` (signed). |
| Monthly schedule | `prf:finance:send-monthly-reports` (`routes/console.php`, on the 1st at 06:00) | Generates last month's accountability workbook and impact summary, and emails them to the treasurer and chair desks. |
| API | `routes/api/v1.php`: `financial-accounts`, `ledger-categories`, `ledger-entries`, `account-transfers`, `receipt-deliveries`, `financial-reports` | CRUD only. `AccountingEvent` update accepts `reconciliation_status` and `reconciliation_remarks`; its resource exposes an `accountability` block when `?include=allocationEntries,refunds` is passed. |

### Still to build (your job)
1. The whole Filament treasurer workspace (sections 3–11).
2. **Workbook import.** Only the `LedgerImport` model, migration, policy and permissions exist. You build the parser, job, CLI command and web upload (section 12).
3. A finance guideline file and the guideline refresh (section 13).
4. Tests are **deferred at the user's request**. Section 14 lists what to add later. Do not write tests unless the user asks.

---

## 1. Ground rules (read before writing code)

1. Read `AGENTS.md` (generated from `.ai/guidelines/**`) and follow it. Key points:
   - Filament actions call the **same jobs** as the API. Never call `$record->update()` or `Model::create()` for finance records from Filament. The one exception is purely descriptive fields on models with no side effects; if in doubt, use the job.
   - Permission checks use `userCan(Model::permission('create'))`, never a literal permission string. Policies are auto-discovered, and Filament resources respect them automatically.
   - Acronyms are ALL CAPS in class and method names (`SMSManager`, `generateULID`).
   - Reference API resources by their fully qualified name; never alias them.
   - Money is whole KES (integers). Display it with `->money('KES', divideBy: 1)` or `number_format`.
2. Use Filament **v5** namespaces:
   - Actions: `Filament\Actions\*`
   - Layout: `Filament\Schemas\Components\*` (`Section`, `Grid`, `Tabs`, `Wizard`)
   - Utilities: `Filament\Schemas\Components\Utilities\{Get,Set}`
   - Form fields: `Filament\Forms\Components\*`
   - Infolist entries: `Filament\Infolists\Components\*`
   - Tables: `Filament\Tables\*`
   - Run `search-docs` (Boost MCP) before using an API you're unsure of.
3. Use `php artisan make:filament-resource … --no-interaction` (and `make:filament-page`, `make:filament-widget`, `make:filament-relation-manager`) to scaffold, then edit.
4. Copy the existing style from these files:
   - `app/Filament/Resources/Payments/PaymentResource.php` for sections, helper texts, icons and table columns
   - `app/Filament/Resources/Pledges/*`, a finance resource with a widget and a relation manager
   - `app/Filament/Forms/Schemas/StatusSchema.php` (`enumSelect`, `relationshipSelect`)
5. **Navigation:**
   - Everything goes in the `'Treasurer'` navigation group, which already exists in `TenantPanelProvider`.
   - Sort order:
     1. Financial Stewardship (dashboard)
     2. Cashbook
     3. Accounts
     4. Transfers
     5. Monthly Accountability
     6. Financial Reports
     7. Import Workbook
     8. …then the existing Payments, Pledges, Requisitions and Accounting Events.
   - `LedgerCategory` goes in `MasterDataCluster` (group `'Settings'`), like `ExpenseCategoryResource`.
6. After changing PHP, run `vendor/bin/mago fmt` and `php -l`. Before finishing, run `make stan`: PHPStan uses a baseline, so new code must add **no new errors**. Don't regenerate the baseline.
7. **Don't commit.** The user commits.
8. Generating PDFs uses Gotenberg (`LARAVEL_PDF_DRIVER=gotenberg`). Locally that needs the Gotenberg container, or set `LARAVEL_PDF_DRIVER` to a working driver.

---

## 2. Job and service cheat-sheet (inputs)

All `*_ulid` keys are ULIDs of existing tenant records. Jobs resolve them to ids.

**`LedgerEntry\CreateJob::dispatchSync(array $data): LedgerEntry`**
- `financial_account_ulid` (required)
- `ledger_category_ulid` (required)
- `amount` (int ≥ 1, required)
- `transacted_on` (Y-m-d, defaults to today)
- `flow` (a `PRFLedgerFlow` value). Only needed for TRANSFER categories, and never offer those in forms.
- `channel` (a `PRFLedgerChannel` value; defaults from the account type)
- `counterparty`, `description`, `reference`
- `member_ulid`, `giver_email`, `giver_phone`
- `accounting_event_ulid`
- `pledge_ulid`: turns the receipt into a pledge installment via `RecordInstallmentJob`
- `membership_ulid`: settles the membership's `amount` and `approved`
- `send_receipt` (bool)
- `recorded_by` (user id; pass `Auth::id()`)

Income lines get a `receipt_number`. `send_receipt=true` emails and texts the giver, using whichever contacts were given.

**`LedgerEntry\UpdateJob::dispatchSync(array $data, string $ulid)`**
- Accepts the same keys minus `pledge_ulid`, `membership_ulid` and `send_receipt`.
- Warn in the UI before editing auto-posted lines (`source_key !== null`).

**`AccountTransfer\CreateJob::dispatchSync(array $data)`**
- `from_financial_account_ulid`, `to_financial_account_ulid` (they must differ)
- `amount`
- `charge` (int, default 0)
- `transferred_on`
- `reference`, `description`
- `recorded_by`
- The update job re-posts the lines. Deleting the transfer soft-deletes its lines.

**`ReceiptDelivery\CreateJob::dispatchSync(array $data): ReceiptDelivery`**
- `ledger_entry_ulid` (must have a `receipt_number`)
- `channel` (`PRFReceiptChannel` value)
- `recipient` (optional; defaults to the giver's email or phone)
- `requested_by`
- Email and SMS are queued. WhatsApp returns `share_url` immediately with status LINK_READY, so open it in a new tab.
- It throws `InvalidArgumentException` when there's no contact or the line isn't income. Catch it and show a danger notification.

**`FinancialReport\CreateJob::dispatchSync(['type' => PRFFinancialReportType value, 'period_start', 'period_end', 'requested_by'])`**
- Queues `GenerateJob` (long queue), which stores the file and emails the requester.
- `FinancialReport::isReady()`, `downloadName()` and `DISK = 'local'`.

**`Pledge\RecordInstallmentJob::dispatchSync(array $data, ?User $recordedBy)`**
- `pledge_ulid`, `amount`, `fulfilled_on`, `notes`
- `method` (a `PRFPledgeInstallmentMethod::offline()` value)
- `financial_account_ulid`, `reference`, `send_receipt`

**`Requisition\ApproveJob::dispatchSync(string $ulid, array $data, int $approverUserId)`**
- `approval_notes`
- `financial_account_ulid` (optional). When given, the disbursement is posted to the cashbook, with `charge`, `reference` and `paid_on` (all optional).

**`Refund\CreateJob::dispatchSync(array $data)`**
- `accounting_event_ulid`, `amount`, `confirmation_message`
- `financial_account_ulid` (optional; defaults to the Paybill account)

**`AllocationEntry\AddTokenJob::dispatchSync(array $data)`**
- The existing keys, plus `financial_account_ulid` (optional). Pass it only when the token was handed straight to the treasurer; otherwise it's recognised when the refund comes in.

**`AccountingEvent\UpdateJob::dispatchSync(array $data, string $ulid)`**
- `reconciliation_status` (`PRFReconciliationStatus` value)
- `reconciliation_remarks` (required when NEEDS_ATTENTION; enforce this in the form)
- `reconciled_by` (user id)

**Services.** Resolve them with `app(X::class)` inside closures:
- `ChartOfAccounts`: `category($code)`, `expenseFor($desk)`, `refundFor($desk)`, `account($type)`
- `FinancialStatements`
- `AccountabilityService`
- `ReceiptDocument`
- `ImpactSummaryService`

**Scopes:**
- `FinancialAccount::query()->withBalance(?Carbon $asOf)` adds `computed_balance`, which the `balance` accessor reads. Use it in tables to avoid N+1 queries.
- `LedgerEntry::query()->ofKind(PRFLedgerCategoryKind ...$kinds)`
- `LedgerEntry::query()->between($from, $to)`

---

## 3. Step 1: Scaffolding and navigation

1. Create these resources in `app/Filament/Resources/`:
   - `FinancialAccounts/FinancialAccountResource` (List, Create, Edit, View)
   - `LedgerEntries/LedgerEntryResource` (List, View, and the custom create pages from section 5)
   - `AccountTransfers/AccountTransferResource` (List, Create, Edit, View)
   - `FinancialReports/FinancialReportResource` (List only, with a generate header action)
   - `LedgerCategories/LedgerCategoryResource` (List, Create, Edit; `$cluster = MasterDataCluster::class`)
2. Pages:
   - `app/Filament/Pages/MonthlyAccountability.php`
   - `app/Filament/Pages/ImportWorkbook.php`
3. Set `$navigationGroup = 'Treasurer'`, the icons and the sort order from rule 5.
4. **Create and edit pages must call the jobs.** Override `handleRecordCreation(array $data): Model` and `handleRecordUpdate(Model $record, array $data): Model` to map form state onto job input. The form uses `*_id` selects, so convert them to ULIDs, or build the form with ULID-valued selects. Keep one clear pattern, for example:
   ```php
   protected function handleRecordCreation(array $data): Model
   {
       return CreateJob::dispatchSync([...$data, 'recorded_by' => Auth::id()]);
   }
   ```
   with selects whose options are keyed by ULID:
   ```php
   Select::make('financial_account_ulid')
       ->options(fn() => FinancialAccount::query()->active()->pluck('name', 'ulid'))
   ```
5. **Acceptance:** a treasurer sees the Treasurer group with all the entries. A plain member sees none of them (policies). The chair sees them read-only: no create, edit or delete buttons.

## 4. Step 2: Accounts (`FinancialAccountResource`)

- **Form:**
  - name (required, unique per tenant)
  - type (`PRFFinancialAccountType` select)
  - identifier, with the helper text "Paybill/till/account number. Never store PINs or passwords here."
  - description
  - is_active toggle
- **Table:** name, type badge (enum colour and icon), identifier, balance (`->state(fn($record) => $record->balance)` with the `withBalance()` query in `modifyQueryUsing`, money), last entry date (`ledgerEntries` max `transacted_on`), active.
- **Header action "Set opening balance"** (also a row action). Form: account, as-at date, amount (can be negative only if the treasurer really needs an overdrawn opening; otherwise ≥ 0). It calls `LedgerEntry\CreateJob` with `ledger_category_ulid = ChartOfAccounts::openingBalance()->ulid` and `flow = RECEIPT` (or PAYMENT for a negative amount, using the absolute amount).
- **View page (the account's cashbook):**
  - Infolist header: balance, receipts this month, payments this month.
  - `LedgerEntriesRelationManager`, read-only, ordered by `transacted_on`. Columns: date, counterparty, description, receipt no./reference, receipts (amount when flow is RECEIPT), payments (amount when flow is PAYMENT), category, running balance.
  - The running balance can be computed per page with a window function (`SUM(CASE…) OVER (ORDER BY transacted_on, id)`) or left out. **Keep it**, because the treasurer relies on it; implement it with `selectRaw` and the window function.
  - Filters: date range, category, flow.
  - Header action "Download cashbook": creates a `FinancialReport` (CASHBOOK, current year) and notifies "Generating… you'll get an email".
- **Acceptance:** balances match `FinancialStatements::cashBalances()`, and an opening balance moves the account balance.

## 5. Step 3: Cashbook and receipting (`LedgerEntryResource`)

This is the treasurer's most-used screen.

### List (`ListLedgerEntries`)
- **Default sort:** `transacted_on` desc, then `id` desc.
- **Columns:**
  - date
  - receipt number (copyable)
  - account (badge)
  - flow (badge, coloured)
  - category (with the desk as description)
  - channel
  - counterparty (searchable)
  - amount (money, green for receipts and red for payments)
  - reference (toggleable)
  - "auto" icon when `source_key !== null`, with a tooltip saying where it came from (Paystack, requisition, refund, transfer, import)
  - recorded by (toggleable)
- **Filters:** account, category, kind (`PRFLedgerCategoryKind`, via `ofKind`), desk (through the category), flow, channel, date range (default: this month), has receipt, trashed.
- **Summaries:** the amount column gets `Summarizer`s showing total receipts and total payments for the filtered set (`Sum::make()->query(fn($q) => $q->where('flow', …))`).
- **Header actions:**
  - **Receipt income** (primary, green): links to the `ReceiptIncome` page
  - **Record payment**
  - **Transfer** (links to create transfer)
  - **Export**: creates a `FinancialReport` of type CASHBOOK for the filtered date range
- **Row actions:**
  - View
  - Edit: modal calling `UpdateJob`. Show a warning banner when the line is auto-posted.
  - Receipt group, visible only when `receipt_number` is set:
    - **Print / Download PDF**: `->action(fn($record) => response()->streamDownload(fn() => print(app(ReceiptDocument::class)->pdf($record)), app(ReceiptDocument::class)->filename($record)))`
    - **Email receipt**: modal with the email prefilled from `giver_email` → `ReceiptDelivery\CreateJob` (EMAIL)
    - **SMS receipt**: phone prefilled → `ReceiptDelivery\CreateJob` (SMS)
    - **Share on WhatsApp**: phone prefilled → `ReceiptDelivery\CreateJob` (WHATSAPP). Then open `share_url` in a new tab, either `->url()` with `openUrlInNewTab()` computed from `ReceiptDocument::whatsAppURL()` plus recording the delivery, or `$this->js('window.open(...)')`.
    - **Copy receipt link** (`ReceiptDocument::url()`)
  - Delete (soft), restore
- **Bulk actions:** delete, restore.

### Create pages (split by intent; simpler than one form with conditional fields)
1. **`ReceiptIncome` page** (custom create page, or a Wizard):
   - **Step "Money received":**
     - date received (default today, ≤ today)
     - account (select, active accounts)
     - channel (`PRFLedgerChannel`, defaulted live from the account type via `afterStateUpdated` and `PRFLedgerChannel::defaultFor()`)
     - amount (KES)
     - reference (M-Pesa code, bank slip or cheque no.)
   - **Step "Designated for":**
     - category (INCOME categories only; labelled "Designated for", e.g. Missions, Camp, Member Contribution – Give, Member Subscription)
     - optional **pledge** select (active pledges; picking one fills the giver from the pledge)
     - optional **membership** select (unpaid memberships; picking one sets the category to Member Subscription and the amount to `PRFMembershipType::getPrice()`)
     - optional accounting event (for tokens of appreciation)
     - description
   - **Step "Giver":**
     - member picker (optional; fills name, `personal_email` and `phone_number`)
     - giver name (`counterparty`, required if no member)
     - email, phone (`App\Rules\PhoneNumber`)
     - **"Send receipt now" toggle**, default on when a contact exists
   - **Submit:** `LedgerEntry\CreateJob`, then redirect to the View page with a success notification showing the receipt number and buttons for "Print", "Share on WhatsApp" and "Receipt another".
2. **`RecordPayment` page:**
   - date, account, category (EXPENSE and CHARGE kinds, grouped by desk)
   - amount, payee (`counterparty`), reference
   - optional accounting event and requisition
   - description
   - **Tip:** show the transaction charge hint from `Utils::getCharge()` for M-Pesa transfers and let the treasurer post the charge as a second line (a checkbox "Also record M-Pesa charge of KES x").
3. **Refund received:** do **not** create refunds here. Refunds go through `Refund\CreateJob` (see section 7) so the accounting event's deficit stays right. Link to the accounting event from the cashbook instead.

### View page
- Infolist: all fields, the source link (payment, requisition, refund, transfer, import), the receipt block (number, link, QR optional), who recorded it and when.
- `ReceiptDeliveriesRelationManager` (read-only table: channel, recipient, status badge, sent at, error; row action "Resend" → `ReceiptDelivery\CreateJob`).

**Acceptance:**
- Receipting a cash gift with an email gives a receipt number, the email is queued, and the View page shows it.
- WhatsApp opens `wa.me` with the message text.
- Payments never get receipt numbers.
- Refund lines never count as income on the dashboard.

## 6. Step 4: Transfers (`AccountTransferResource`)

- **Form:** from account, to account (must differ; `->different('from_financial_account_ulid')`), amount, charge (with a hint from `Utils::getCharge(PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF, amount)` where relevant), date, reference, description.
- Create and edit go through the jobs.
- **Table:** date, from → to, amount, charge, reference. Filters: account, date.
- **Paystack settlements:** add a header action "Record Paystack settlement" that pre-selects from = Paystack and to = Bank.
- **Acceptance:** both balances move, and income and expense totals don't.

## 7. Step 5: Money flows on existing screens

1. **Requisition approval** gets the disbursement inputs in these places:
   - `app/Filament/Resources/Requisitions/RequisitionResource.php` around line 566 (row approve action) and around line 744 (bulk approve)
   - `app/Filament/Resources/AccountingEvents/RelationManagers/RequisitionsRelationManager.php` around line 696

   Add these fields (optional, in a "Paid out from" section):
   - `financial_account_ulid` (active accounts)
   - `charge` (int, hint from `Utils::getCharge()`)
   - `reference`
   - `paid_on`

   Pass them in `$data` to `ApproveJob`. Collapse the section when the approver isn't the treasurer (the chair often approves). Also add a requisition row action **"Record disbursement"**:
   - Visible when the requisition is approved and has no ledger line with its `requisition_id`.
   - Posts via `app(Ledger::class)->postDisbursement($requisition, $account, $charge, $reference, $paidOn, Auth::id())`.
   - Put that call in a small new job, `app/Jobs/Requisition/RecordDisbursementJob.php`, so the API can reuse it later.
2. **Refunds:**
   - Add `app/Filament/Resources/AccountingEvents/RelationManagers/RefundsRelationManager.php`, and the same as an action on `Missions/RelationManagers/AccountingEventRelationManager.php`.
   - Columns: amount, charge, deficit, confirmation message, account, date.
   - The create action calls `Refund\CreateJob` with `accounting_event_ulid`, `amount`, `confirmation_message` and `financial_account_ulid` (default Paybill).
   - No edit. Delete is allowed (soft), but warn that the cashbook line stays and must be deleted separately. Better: add a `RefundObserver::deleted` that deletes its `ledgerEntry` lines; see `AccountTransferObserver` for the pattern.
3. **Tokens of appreciation:**
   - Wherever tokens are added in Filament (search for `is_token_of_appreciation`/token UI; currently none exists), add an "Add token of appreciation" action on the accounting event.
   - Fields: amount, narration, member, confirmation message, and a toggle **"Handed to the treasurer"**. When on, pick an account.
   - It calls `AddTokenJob` (entry_type CREDIT).
4. **Pledge installments** (`app/Filament/Resources/Pledges/RelationManagers/PledgeInstallmentsRelationManager.php`):
   - The form gains `method` (options `PRFPledgeInstallmentMethod::offline()`), `financial_account_ulid`, `reference` and `send_receipt`.
   - Replace `CreateAction::make()` with `CreateAction::make()->using(fn(array $data) => RecordInstallmentJob::dispatchSync([...$data, 'pledge_ulid' => $this->getOwnerRecord()->ulid], Auth::user()))`.
   - Add a column for the linked receipt number, from the `ledgerEntry` relation.
5. **Memberships** (`app/Filament/Resources/Members/RelationManagers/MembershipsRelationManager.php` and `Memberships/*`):
   - Row action **"Receipt fee"**, visible when the membership isn't approved or `amount` is 0.
   - Opens a modal with the Receipt income fields prefilled: category Member Subscription, amount `type->getPrice()`, giver from the member.
   - It calls `LedgerEntry\CreateJob` with `membership_ulid`.
6. **Payment types** (`PaymentTypes` resource): add a "Booked as" select for `ledger_category_id` (INCOME categories).
7. **Known gap to fix while you're here:**
   - `Missions/RelationManagers/AccountingEventRelationManager.php` edits `allocationEntries` through a relationship `Repeater`, which bypasses `AllocationEntry` jobs and observers.
   - Leave the repeater read-only (`->disabled()`) and add explicit "Add expense" and "Add token" actions that call `AllocationEntry\CreateJob` and `AddTokenJob`.
   - Confirm this with the user before removing edit capability, because missioners' entries come from the mobile app.

## 8. Step 6: Monthly Accountability page

`app/Filament/Pages/MonthlyAccountability.php`: a custom `Page` implementing `HasTable` (`InteractsWithTable`). Visible to users with `AccountingEvent::permission('viewAny')` and to the chair.

- **Header form:**
  - month picker (default: last month)
  - desk select (optional, `PRFResponsibleDesk`)
  - status filter (`PRFReconciliationStatus`)
- **Table query:** `AccountingEvent::query()->whereBetween('due_date', …)->with(['allocationEntries', 'refunds', 'accountingEventable'])`.
- **Columns** (compute each row once with `AccountabilityService::row()` and memoise per record, e.g. a `$rows` array on the page keyed by id):
  - S/No, date, accounting event (link to the event), desk
  - disbursed
  - one column per `expenseColumns()` category, built dynamically
  - transaction costs, token, to refund, refund done
  - **balance** (red when ≠ 0)
  - status badge (`$row->status()`)
  - remarks (`$row->remarks()`)
- **Row actions:**
  - **Mark fully accounted**: `AccountingEvent\UpdateJob` with FULLY_ACCOUNTED and `reconciled_by`.
  - **Needs attention…**: modal with required remarks.
  - **Reset to pending**.
  - **Open requisitions / refunds**.
- **Bulk action:** "Mark fully accounted", which must only apply to rows whose balance is 0; skip the others and report them in the notification.
- **Header actions:**
  - **Export month**: `FinancialReport\CreateJob` with MONTHLY_ACCOUNTABILITY for the selected month.
  - **Export year to date**
- **Stats row** above the table: events, fully accounted, needing attention, total disbursed, total real expenses, total refunds pending.
- **Acceptance:** the Chongoria example (see `tests/Feature/Finance/AccountabilityTest.php`) shows balance 0 and Fully accounted, and an event with only a disbursement shows "Accounting for KES x pending".

## 9. Step 7: Financial Reports (`FinancialReportResource`)

- **List columns:** type, period, status badge (`PRFProcessingStatus`), requested by, completed at, error (tooltip). Poll with `->poll('5s')` while any row is PENDING or PROCESSING.
- **Header action "Generate report":**
  - Form: type (`PRFFinancialReportType`), then period presets (This month, Last month, Year to date, Last year, Custom → two dates).
  - It calls `FinancialReport\CreateJob` with `requested_by = Auth::id()`.
- **Row actions:**
  - **Download**, when `isReady()`: `Storage::disk(FinancialReport::DISK)->download($record->file_path, $record->downloadName())`.
  - **Email to me**: re-sends `FinancialReportReadyNotification` to `Auth::user()`.
  - **Regenerate**: dispatches `GenerateJob`.
  - Delete.
- No create or edit pages.
- **Acceptance:** generating a Cashbook for 2026 produces an xlsx whose sheets are Treasurer Report, one per account, Cash Balances, Income Statement and Income Distribution, and it opens in Excel with formulas intact.

## 10. Step 8: Dashboard (Financial Stewardship)

In `app/Filament/Pages/FinanceDashboard.php`:
1. **New `app/Filament/Widgets/AccountBalancesOverview.php`** (`StatsOverviewWidget`):
   - One stat per active account, with the balance from the `withBalance()` query, a description of the last entry date and a colour from the type.
   - A final **Total** stat.
   - `canView()`: `userCan(FinancialAccount::permission('viewAny'))`, which covers treasurer, chair and vice chair.
   - Put it first on the page.
2. **New `RequisitionsAwaitingDisbursementWidget`** (table widget): approved requisitions with no ledger line. Row action "Record disbursement" (section 7.1).
3. **Rewrite these to read from the ledger** (`FinancialStatements` / `LedgerEntry`) instead of `payments`:
   - `IncomeVsExpenseChart`: monthly income (INCOME receipts) vs expenditure (EXPENSE and CHARGE payments minus REFUND receipts).
   - `PaymentMethodsChart`: income by `channel`.
   - `GiftsDonationsWidget`: income by statement line this year. It currently counts pending and failed Paystack payments, and it filters `Gift::where('is_active', true)`, where `true` is INACTIVE.
4. **Header actions** on the dashboard: Receipt income, Record payment, Transfer, Generate report.
5. **Acceptance:** after receipting KES 1,000 cash, the Cash tile and the total rise by 1,000, and the income chart shows it. Refunds and transfers don't change income.

## 11. Step 9: Chair and vice chair (read-only)

- They already have view permissions and `create financial report` (in `config/prf/roles.php`).
- Verify they can see the dashboard, Accounts, Cashbook, Monthly Accountability and Reports (and generate reports), but not create, edit or delete ledger lines.
- Hide receipt-sending actions for them (`userCan(ReceiptDelivery::permission('create'))`).

## 12. Step 10: Workbook import (backend and web)

The treasurer uploads `PRF Financials 2026.xlsx` **on the deployed server** (dev/staging/prod) because the file can't be committed. So the web upload is the primary path, and the CLI is secondary.

### 12.1 Parser: `app/Services/Finance/WorkbookImporter.php`
- `preview(string $path, int $year): WorkbookPreview`
- `import(LedgerImport $import): array` (the summary)
- **Loading:** `IOFactory::createReaderForFile($path)`, `setReadDataOnly(true)`, `setLoadSheetsOnly([...configured sheet names...])`. **Never load the `Handover` sheet**; it holds credentials.
- **Sheets** come from `config('prf.finance.import.sheets')` (`{year}` replaced): `M-shwari`, `Cash`, `M-pesa {year}`, `bank {year}`, `Paybill {year}`, mapped to an account type. Match names case-insensitively and trimmed.
- **Columns:**
  - The header is row 2.
  - Data starts at row 3.
  - Columns: A Date, B Payment To/Receipt From, C Description, D Receipt No./Reference.
  - E Receipts (or "Debits/Deposits"), F Payments/Charges.
  - Desk/Category: column I on the Cash, M-Pesa, bank and Paybill sheets. The M-shwari sheet has no desk column, so default to Interest or Other Receipts.
  - Detect columns by header text rather than letters, because layouts differ slightly: "Credit/ Deposits" on M-shwari.
  - Use calculated values (`getCalculatedValue()`), and Excel serial dates (`Date::excelToDateTimeObject`).
  - Skip rows with no amount in E and F, and skip TOTAL rows.
- **Row mapping:**
  - Row 3 / "Opening Balance" → OPENING_BALANCE category, RECEIPT (PAYMENT if negative), dated 1 Jan of the year.
  - "Bal c/f from …" rows → treat as opening balance too, if non-zero.
  - Description or category containing "Charges", or desk "Treasurer's Desk" with a "Charges" description → `charge.transaction_costs`.
  - Desk/category `Inter a/c transfers (Receipt|Expense)` → TRANSFER category.
    - Try to **pair** each one with its opposite-sign row on another sheet with the same amount within ±3 days.
    - A pair becomes one `AccountTransfer`, created via its job with an import `source_key`; the job currently sets keys like `transfer:{id}:in`, so make sure import pairing doesn't double-post.
    - An unpaired row becomes a TRANSFER line with an explicit flow.
  - Everything else is mapped by `config('prf.finance.import.category_map')` (**add this**): a map from workbook label (normalised: lowercase, trimmed, single spaces, `'`/`’` unified) to category `code`. Seed it from the workbook's "Index" sheet and the values seen in the data:
    - Income: `member contribution`, `mission contribution`, `appreciation from schools`, `camp contribution`, `member subscription`/`prf membership subscription`, `tithe & offering`/`tithe`/`offering`, `interest on fixed deposit`, `gik - rent & instruments`, `other receipts`
    - Expense: `mission desk`/`mission expenses` → `expense.desk.4`, `prayer desk`/`prayer desk expenses` → `expense.desk.5`, `chairman's desk`/`chairman's desk expenses` → `expense.desk.1`, `organizing secretary's desk`/`organizing secretary's expenses` → `expense.desk.3`, `treasurer's desk` → `expense.desk.8`, `music desk expenses` → `expense.desk.7`, `follow-up desk expenses` → `expense.desk.6`, `vice chairman's epenses` (sic) → `expense.desk.2`, `agm expense(s)` → `expense.agm`, `camp expense` → `expense.camp`, `dinner expense` → `expense.dinner`, `bot expenses - strategic plan` → `expense.bot`, `other expenses` → `expense.other`
    - Refund: `mission refund` → `refund.desk.4`, `prayer desk refund` → `refund.desk.5`, `os refund` → `refund.desk.3`, `chairman refund` → `refund.desk.1`, `treasurer refund` → `refund.desk.8`, `music desk refund` → `refund.desk.7`, `follow-up desk refund` → `refund.desk.6`, `vice chairman refund` → `refund.desk.2`
  - **Receipts vs payments:** a receipt row mapped to an EXPENSE desk category is a **refund** (the workbook books refunds under the desk, e.g. "Isinya Boys … – Refund", desk "Mission Desk"). Map it to that desk's REFUND category, never to income.
  - **Unmapped labels** appear in the preview. The treasurer can map each to an existing category or **create a new category** (name, kind, desk) inline; the choices are stored in `ledger_imports.mapping`.
- **Accounts:** for each configured sheet, use the first active account of that type (`ChartOfAccounts::account()`), or **create it** from the preview if missing. This is how the initial chart comes from the treasurer's own file.
- **Idempotency:** `source_key = 'import:' . sha1("{$sheet}|{$row}|{$date}|{$amount}|{$description}")`. `Ledger::post()` skips keys that already exist, so re-importing the same workbook adds only new rows.
- **Links:** set `ledger_import_id`, `recorded_by = imported_by`, `counterparty = column B`, `description = column C`, `reference = column D`, and channel from the account type. Imported income **does get receipt numbers** (they're receipts), but **never** send receipts for imported rows.

### 12.2 Job and CLI
- **`app/Jobs/LedgerImport/ImportWorkbookJob.php`:**
  - `#[Queue('long')]`, `#[Tries(1)]`, `#[Timeout(580)]`, `ShouldBeUnique` on the ULID.
  - Status goes PROCESSING → COMPLETED/FAILED, with `summary` = counts per sheet, posted/skipped/unmapped.
  - **Delete the uploaded file** in `finally`.
  - Notify the importer (new `LedgerImport/LedgerImportCompletedNotification`, mail only).
- **`app/Console/Commands/Finance/ImportWorkbookCommand.php`:** `prf:finance:import-workbook {path} {--tenant=} {--year=} {--dry-run}`. It prints the preview table and unmapped rows; without `--dry-run` it creates a `LedgerImport` and runs the job synchronously.
- **Prune:** schedule a daily `prf:finance:prune-imports`, which deletes `finance-imports/**` files older than 24h (on the `local` disk, per tenant) and marks stale PENDING imports as FAILED.

### 12.3 Web page: `app/Filament/Pages/ImportWorkbook.php` (Treasurer group, treasurer and super admin only)
1. **Upload step:**
   - `FileUpload` (`->disk('local')->directory('finance-imports')->acceptedFileTypes([xlsx mime])->maxSize(20480)->visibility('private')`)
   - year (default: current year)
   - Create a `LedgerImport` (status PENDING, `file_path`, `original_name`, `year`, `imported_by`).
2. **Preview step:**
   - Rows per sheet and total receipts/payments per sheet (so the treasurer can compare with the workbook's Cash Balances).
   - Accounts to be created.
   - Mapped categories (label → category, with counts).
   - **An unmapped labels table** with an inline select per row ("Map to…" or "Create category…").
   - Opening balances detected.
   - Transfers paired or unpaired.
   - Samples of the first rows per sheet.
3. **Confirm:** saves `mapping` and dispatches `ImportWorkbookJob`. Show "Importing… you'll get an email", plus a list of past imports with status and summary.
4. **Cancel:** deletes the uploaded file and marks the import FAILED ("Cancelled").

**Acceptance:** after importing the 2026 workbook on dev, the dashboard balances per account equal the workbook's Cash Balances closing balances, or differences are explained by rows listed as unmapped/skipped. Re-uploading the same file posts nothing new.

## 13. Step 11: Guidelines and documentation

1. Add `.ai/guidelines/prf/finance.md` covering:
   - The ledger is the single source of truth. Write only through `Ledger::post()` via jobs.
   - Refunds are never income. Transfers are neutral. Paystack is booked gross plus fee.
   - Coded categories are never deleted.
   - Receipts: the `ReceiptDelivery` resource; WhatsApp is a share link only.
   - Reports: create a `FinancialReport`; don't stream exports from controllers.
   - The web panel is the treasurer's primary interface.
2. `.ai/guidelines/prf/testing.md`: document the `fakePDFRendering()` helper (tests/Pest.php), and that `tests/Feature/Finance` gets roles and the chart of accounts from a shared `beforeEach`.
3. Run `php artisan boost:update --no-interaction`.
4. `docs/developer-invite.md`: add "Treasurer" to "What to Explore First" (Cashbook, Receipting, Monthly Accountability, Reports).

## 14. Tests (deferred: add when the user asks)

Existing finance tests to keep green: `tests/Feature/Finance/*`, `tests/Feature/Api/{FinancialAccount,LedgerEntry,AccountTransfer,ReceiptDelivery}Test.php`, `tests/Feature/Tenancy/TenantProvisioningTest.php`.

To add later:
- **Filament** (`Livewire::test`, in the style of `tests/Feature/Filament/Central/*`):
  - each resource renders for the treasurer and is forbidden to a member
  - Receipt income creates a line with a receipt number and deliveries when "send receipt" is on
  - approve with account posts the disbursement
  - the installment relation manager posts a receipt
  - Monthly Accountability renders and "mark fully accounted" updates the event
  - Generate report creates a `FinancialReport`
  - the balances widget is visible to the chair and hidden from a member
- **Import:** a small fixture xlsx with **no credentials**, covering preview counts, the unmapped list, mapping via the preview, idempotent re-import, refund-vs-income classification, and transfer pairing.
- **Workspace recovery:**
  - `FakeWorkspaceDirectory::$created[0]->recoveryEmail/recoveryPhone` are set on provisioning
  - changing a member's phone calls `updateRecovery`
  - `api.members.store` without `phone_number` returns 422

## 15. Verification checklist

1. `vendor/bin/mago fmt`, then `make stan` (no new errors), then `make test` (all green).
2. On dev:
   - Import the 2026 workbook through the web.
   - Compare the balances.
   - Receipt a cash gift and email yourself the receipt.
   - Open the WhatsApp link.
   - Approve a requisition from Paybill and check the cashbook.
   - Record a refund, and check the Monthly Accountability row turns Fully accounted.
   - Generate the Cashbook and Monthly Accountability workbooks and open them in Excel next to the samples.
3. Sign in as the chair: read-only everywhere, and able to generate reports.
4. Sign in as a member: no Treasurer group.

## 16. Known risks and notes

- **Paystack settlement:** the gross amount plus fee sit in the "Paystack" account until the treasurer records the settlement transfer to the bank. Make that action easy to find (section 6).
- **History and double counting:** auto-posting starts at deploy. Historic Paystack payments are **not** back-posted, because the imported bank sheet already contains their settlements.
- **Deleted auto-posted lines** stay deleted, since replays skip their `source_key`. Offer "Restore" rather than re-posting.
- **Queues:** receipts go on the `high` queue, and reports and imports on `long`. The dev server needs a worker running `--queue=high,default,long` (see `.fly/start-queue.sh`).
- **Gotenberg** must be reachable (`GOTENBERG_URL`) on every environment that generates receipts or impact PDFs.
- **Behaviour change already shipped:** `Member/CreateRequest` now requires `phone_number` (normalised to E.164), and member imports skip rows without a valid phone or personal email. The mobile apps must send a phone when creating members.
