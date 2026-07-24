<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class MasterDataCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Master Data & Lookups';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 98;
}
