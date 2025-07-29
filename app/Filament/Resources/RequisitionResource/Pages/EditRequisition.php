<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use App\Enums\PRFApprovalStatus;
use App\Filament\Resources\RequisitionResource;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditRequisition extends EditRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\Action::make('approve')
                ->label('✅ Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Requisition')
                ->modalDescription('Are you sure you want to approve this requisition?')
                ->form([
                    Textarea::make('approval_notes')
                        ->label('Approval Notes')
                        ->placeholder('Add notes about your approval decision...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'approval_status' => PRFApprovalStatus::APPROVED->value,
                        'approved_by' => Auth::user()->member?->id,
                        'approved_at' => now(),
                        'approval_notes' => $data['approval_notes'] ?? null,
                    ]);

                    $this->refreshFormData(['approval_status', 'approved_by', 'approved_at', 'approval_notes']);
                })
                ->visible(function () {
                    $currentUser = Auth::user();
                    $isAppointedApprover = $this->record->appointed_approver_id === $currentUser->member?->id;
                    $canApprove = in_array($this->record->approval_status, [PRFApprovalStatus::PENDING->value, PRFApprovalStatus::UNDER_REVIEW->value]);

                    return $isAppointedApprover && $canApprove;
                }),

            Actions\Action::make('reject')
                ->label('❌ Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reject Requisition')
                ->modalDescription('Are you sure you want to reject this requisition?')
                ->form([
                    Textarea::make('approval_notes')
                        ->label('Rejection Reason')
                        ->placeholder('Please provide a reason for rejection...')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'approval_status' => PRFApprovalStatus::REJECTED->value,
                        'approved_by' => Auth::user()->member?->id,
                        'approved_at' => now(),
                        'approval_notes' => $data['approval_notes'],
                    ]);

                    $this->refreshFormData(['approval_status', 'approved_by', 'approved_at', 'approval_notes']);
                })
                ->visible(function () {
                    $currentUser = Auth::user();
                    $isAppointedApprover = $this->record->appointed_approver_id === $currentUser->member?->id;
                    $canReject = in_array($this->record->approval_status, [PRFApprovalStatus::PENDING->value, PRFApprovalStatus::UNDER_REVIEW->value]);

                    return $isAppointedApprover && $canReject;
                }),

            Actions\Action::make('review')
                ->label('🔍 Under Review')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Mark as Under Review')
                ->modalDescription('Mark this requisition as under review while you evaluate it.')
                ->form([
                    Textarea::make('approval_notes')
                        ->label('Review Notes')
                        ->placeholder('Add notes about what you are reviewing...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'approval_status' => PRFApprovalStatus::UNDER_REVIEW->value,
                        'approved_by' => Auth::user()->member?->id,
                        'approved_at' => now(),
                        'approval_notes' => $data['approval_notes'] ?? null,
                    ]);

                    $this->refreshFormData(['approval_status', 'approved_by', 'approved_at', 'approval_notes']);
                })
                ->visible(function () {
                    $currentUser = Auth::user();
                    $isAppointedApprover = $this->record->appointed_approver_id === $currentUser->member?->id;
                    $isPending = $this->record->approval_status === PRFApprovalStatus::PENDING->value;

                    return $isAppointedApprover && $isPending;
                }),

            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    // Append approval data to the form data
    protected function mutateFormDataBeforeSave($data): array
    {

        $data['approved_by'] = Auth::user()->member?->id;
        $data['approved_at'] = now();

        // dd($data);

        return $data;
    }
}
