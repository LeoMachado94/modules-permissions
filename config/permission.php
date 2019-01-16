<?php

return [

    'models' => [

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `LeoMachado\Permission\Contracts\Permission` contract.
         */

        'permission' => LeoMachado\Permission\Models\Permission::class,

        /*
         * When using the "HasModules" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Module" model but you may use whatever you like.
         *
         * The model you want to use as a Module model needs to implement the
         * `LeoMachado\Permission\Contracts\Module` contract.
         */

        'module' => LeoMachado\Permission\Models\Module::class,

    ],

    'table_names' => [

        /*
         * When using the "HasModules" trait from this package, we need to know which
         * table should be used to retrieve your modules. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'modules' => 'modules',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your permissions. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'permissions' => 'permissions',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your models permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_permissions' => 'model_has_permissions',

        /*
         * When using the "HasModules" trait from this package, we need to know which
         * table should be used to retrieve your models modules. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_roles' => 'model_has_modules',

        /*
         * When using the "HasModules" trait from this package, we need to know which
         * table should be used to retrieve your modules permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'module_has_permissions' => 'module_has_permissions',
    ],

    'column_names' => [

        /*
         * Change this if you want to name the related model primary key other than
         * `model_id`.
         *
         * For example, this would be nice if your primary keys are all UUIDs. In
         * that case, name this `model_uuid`.
         */

        'model_morph_key' => 'model_id',
    ],

    /*
     * When set to true, the required permission/role names are added to the exception
     * message. This could be considered an information leak in some contexts, so
     * the default setting is false here for optimum safety.
     */

    'display_permission_in_exception' => false,

    'cache' => [

        /*
         * By default all permissions will be cached for 24 hours unless a permission or
         * role is updated. Then the cache will be flushed immediately.
         */

        'expiration_time' => 60 * 24,

        /*
         * The key to use when tagging and prefixing entries in the cache.
         */

        'key' => 'spatie.permission.cache',

        /*
         * When checking for a permission against a model by passing a Permission
         * instance to the check, this key determines what attribute on the
         * Permissions model is used to cache against.
         *
         * Ideally, this should match your preferred way of checking permissions, eg:
         * `$user->can('view-posts')` would be 'name'.
         */

        'model_key' => 'name',

        /*
         * You may optionally indicate a specific cache driver to use for permission and
         * role caching using any of the `store` drivers listed in the cache.php config
         * file. Using 'default' here means to use the `default` set in cache.php.
         */

        'store' => 'default',
    ],
];
