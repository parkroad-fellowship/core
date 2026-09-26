<?php

use Illuminate\Support\Facades\File;

/**
 * Every `Model::permission('action')` referenced in the app must exist in config/prf/roles.php,
 * otherwise the check silently fails for everyone except super admins.
 */
it('only references permissions that are defined in config/prf/roles.php', function () {
    $defined = collect(config('prf.roles.roles'))->flatten()->unique()->flip();
    $undefined = [];

    foreach (File::allFiles(app_path()) as $file) {
        $source = $file->getContents();

        preg_match('/^namespace ([\w\\\\]+);/m', $source, $namespace);
        preg_match_all(
            '/(?<![\w>$\\\\])(\\\\?[A-Z][\w\\\\]*)::permission\(\'([^\']+)\'\)/',
            $source,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as [, $class, $action]) {
            $fqn = str_starts_with($class, '\\') ? ltrim($class, '\\') : null;

            if (
                $fqn === null
                && preg_match('/^use ([\w\\\\]+\\\\' . preg_quote($class, '/') . ');$/m', $source, $import)
            ) {
                $fqn = $import[1];
            }

            $fqn ??= ($namespace[1] ?? '') . '\\' . $class;

            if (!class_exists($fqn) || !method_exists($fqn, 'permission')) {
                continue;
            }

            $permission = $fqn::permission($action);

            if (!$defined->has($permission)) {
                $undefined[$permission][] = $file->getRelativePathname();
            }
        }
    }

    expect($undefined)->toBeEmpty();
});
