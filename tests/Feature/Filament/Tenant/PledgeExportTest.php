<?php

use App\Filament\Exports\PledgeExporter;
use App\Filament\Resources\Pledges\Pages\ListPledges;
use App\Models\Pledge;
use App\Models\PledgeInstallment;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;

beforeEach(function () {
    $this->tenant = createOrGetTenant();
    Filament::setCurrentPanel('admin');
    Storage::fake(config('filesystems.default'));
});

/**
 * @return list<list<mixed>>
 */
function readExportedXLSX(Export $export): array
{
    $disk = Storage::disk($export->file_disk);
    $path = $disk->path($export->getFileDirectory() . DIRECTORY_SEPARATOR . $export->file_name . '.xlsx');

    $reader = new Reader();
    $reader->open($path);

    $rows = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
    }
    $reader->close();

    return $rows;
}

it('exports pledges to an xlsx file with the amount fulfilled to date', function () {
    test()->actingAs(tenantUser($this->tenant, ['super admin']));

    $pledge = Pledge::factory()->create(['name' => 'Jane Giver', 'amount' => 5_000, 'member_id' => null]);
    PledgeInstallment::factory()->create(['pledge_id' => $pledge->id, 'amount' => 2_000]);
    PledgeInstallment::factory()->create(['pledge_id' => $pledge->id, 'amount' => 1_500]);

    Livewire::test(ListPledges::class)
        ->assertActionVisible(TestAction::make('export')->table())
        ->callAction(TestAction::make('export')->table())
        ->assertHasNoFormErrors();

    $export = Export::query()->sole();

    expect($export->exporter)->toBe(PledgeExporter::class);

    $disk = Storage::disk($export->file_disk);
    $directory = $export->getFileDirectory();
    expect($disk->exists($directory . DIRECTORY_SEPARATOR . $export->file_name . '.xlsx'))->toBeTrue();

    [$headers, $row] = readExportedXLSX($export);
    $values = array_combine($headers, $row);

    expect($values)->toMatchArray([
        'Name' => 'Jane Giver',
        'Amount (KES)' => '5000',
        'Fulfilled To Date (KES)' => '3500',
        'Installments' => '2',
    ]);
});

it('neutralises spreadsheet formulas in pledger-supplied text', function () {
    test()->actingAs(tenantUser($this->tenant, ['super admin']));

    Pledge::factory()->create(['name' => '=HYPERLINK("http://evil.test")', 'member_id' => null]);

    Livewire::test(ListPledges::class)->callAction(TestAction::make('export')->table());

    [$headers, $row] = readExportedXLSX(Export::query()->sole());

    expect(array_combine($headers, $row)['Name'])->toBe('\'=HYPERLINK("http://evil.test")');
});

it('forbids the pledge list and its export to users without permission', function () {
    test()->actingAs(tenantUser($this->tenant, []));

    Livewire::test(ListPledges::class)->assertForbidden();
});
