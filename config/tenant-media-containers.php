<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy per-tenant media containers
    |--------------------------------------------------------------------------
    |
    | Before consolidating onto a shared storage container, each tenant stored
    | its media in a dedicated Azure blob container. This map lets the
    | `media:migrate-to-shared-container` command know where to read each
    | tenant's existing files from.
    |
    | Keyed by tenant ULID. Each entry may specify:
    |   'container' - the Azure blob container holding that tenant's media.
    |   'prefix'    - the leading path segment used inside the SOURCE container
    |                 (defaults to 'prf-core'; some tenants used e.g. 'hmt-core').
    |
    | Files are migrated into the shared container 'gospel-flood-core-container'
    | using the target prefix configured on the command (defaults to 'gospel-flood-core').
    |
    */

    '01kyvqgepfqh10z3r8wmeq6rcz' => ['container' => 'prf-core-container', 'prefix' => 'prf-core'],
    '01kz9r08gfgb4v54k85rh8snmj' => ['container' => 'hmt-core-container', 'prefix' => 'hmt-core'],
];
