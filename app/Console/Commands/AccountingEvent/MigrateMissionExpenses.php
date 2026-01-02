<?php

namespace App\Console\Commands\AccountingEvent;

use App\Enums\PRFEntryType;
use App\Enums\PRFMorphType;
use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionExpense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateMissionExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-mission-expenses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate mission expenses to the new accounting event structure';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting migration of mission expenses to accounting events...');

        // All amounts are currently approved by the chairperson
        $approver = Member::whereEmail(Utils::getDeskEmails(PRFResponsibleDesk::CHAIRPERSON)[0])->firstOrFail();
        $missionDesk = Member::whereEmail(Utils::getDeskEmails(PRFResponsibleDesk::MISSIONS_DESK)[0])->firstOrFail();

        MissionExpense::query()
            ->with(['mission.school', 'mission.missionType', 'expenses.receipts'])
            ->whereHas('mission')
            ->chunk(1, function ($missionExpenses) use ($approver, $missionDesk) {
                foreach ($missionExpenses as $missionExpense) {
                    $this->info('Migrating Mission Expense ULID: '.$missionExpense->ulid);
                    $mission = $missionExpense->mission;

                    // Create accounting event entry if not already created
                    $accountingEvent = AccountingEvent::firstOrCreate(
                        [
                            'accounting_eventable_id' => $missionExpense->mission_id,
                            'accounting_eventable_type' => PRFMorphType::MISSION,
                        ],
                        [
                            'accounting_eventable_id' => $mission->id,
                            'accounting_eventable_type' => PRFMorphType::MISSION,
                            'name' => sprintf('%s: %s - %s', $mission->start_date->format('d-m-Y'), $mission->school->name, $mission->missionType->name),
                            'due_date' => $mission->start_date->subDays(1),
                            'responsible_desk' => PRFResponsibleDesk::MISSIONS_DESK,
                        ]
                    );

                    // Credit the amount required for the mission
                    DB::transaction(function () use ($accountingEvent, $approver, $missionExpense, $missionDesk) {
                        AllocationEntry::where('accounting_event_id', $accountingEvent->id)->forceDelete();

                        // Load initial amount received for the mission
                        AllocationEntry::create([
                            'accounting_event_id' => $accountingEvent->id,
                            'member_id' => $approver->id,
                            'entry_type' => PRFEntryType::CREDIT,
                            'unit_cost' => $missionExpense->amount_received,
                            'quantity' => 1,
                            'amount' => $missionExpense->amount_received,
                            'charge' => 0,
                            'narration' => 'Credit for mission expense migration',
                        ]);

                        // Debit each expense item
                        foreach ($missionExpense->expenses as $expense) {
                            $allocationEntry = AllocationEntry::create([
                                'accounting_event_id' => $accountingEvent->id,
                                'expense_category_id' => $expense->expense_category_id,
                                'charge_type' => $expense->charge_type,
                                'member_id' => $expense->member_id,
                                'entry_type' => PRFEntryType::DEBIT,
                                'unit_cost' => $expense->unit_cost,
                                'quantity' => $expense->quantity,
                                'amount' => $expense->line_total + $expense->charge,
                                'charge' => $expense->charge,
                                'narration' => $expense->narration,
                                'confirmation_message' => $expense->confirmation_message,
                            ]);

                            // Migrate receipts
                            foreach ($expense->receipts as $expenseReceipt) {
                                $expenseReceipt
                                    ->copy(
                                        $allocationEntry,
                                        AllocationEntry::RECEIPTS,
                                    );
                            }
                        }

                        // Credit any token received
                        if ($missionExpense->token_amount > 0) {
                            AllocationEntry::create([
                                'accounting_event_id' => $accountingEvent->id,
                                'member_id' => $missionDesk->id, // Default to missions desk
                                'entry_type' => PRFEntryType::CREDIT,
                                'unit_cost' => $missionExpense->token_amount,
                                'quantity' => 1,
                                'amount' => $missionExpense->token_amount,
                                'charge' => 0,
                                'narration' => 'Credit for token received migration',
                            ]);
                        }
                    });
                }
            });

        $this->info('Setting default accounting event for missions without expenses...');
        Mission::doesntHave('accountingEvent')
            ->chunk(100, function ($missions) {
                foreach ($missions as $mission) {
                    $this->info('Creating default Accounting Event for Mission ULID: '.$mission->ulid);
                    AccountingEvent::firstOrCreate(
                        [
                            'accounting_eventable_id' => $mission->id,
                            'accounting_eventable_type' => PRFMorphType::MISSION,
                        ],
                        [
                            'accounting_eventable_id' => $mission->id,
                            'accounting_eventable_type' => PRFMorphType::MISSION,
                            'name' => sprintf('%s: %s - %s', $mission->start_date->format('d-m-Y'), $mission->school->name, $mission->missionType->name),
                            'due_date' => $mission->start_date->subDays(1),
                            'responsible_desk' => PRFResponsibleDesk::MISSIONS_DESK,
                        ]
                    );
                }
            });
    }
}
