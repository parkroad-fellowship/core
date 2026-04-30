<?php

namespace App\Filament\Concerns;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;

/**
 * Switch relation manager tabs to Alpine-based (client-side) switching.
 * The default Livewire property-based approach requires a full server
 * re-render on each tab click, which is too slow for pages with
 * multiple relation managers.
 */
trait HasAlpineRelationManagerTabs
{
    public function getRelationManagersContentComponent(): Component
    {
        $component = parent::getRelationManagersContentComponent();

        if ($component instanceof Tabs) {
            $component
                ->livewireProperty(null)
                ->persistTabInQueryString('relation');
        }

        return $component;
    }
}
