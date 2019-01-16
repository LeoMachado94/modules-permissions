<?php

namespace LeoMachado\Permission;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use LeoMachado\Permission\Contracts\Module;
use Illuminate\Contracts\Auth\Access\Gate;
use LeoMachado\Permission\Contracts\Permission;
use Illuminate\Contracts\Auth\Access\Authorizable;
use LeoMachado\Permission\Exceptions\PermissionDoesNotExist;

class PermissionRegistrar
{
    /** @var \Illuminate\Contracts\Auth\Access\Gate */
    protected $gate;

    /** @var \Illuminate\Contracts\Cache\Repository */
    protected $cache;

    /** @var \Illuminate\Cache\CacheManager */
    protected $cacheManager;

    /** @var string */
    protected $permissionClass;

    /** @var string */
    protected $moduleClass;

    /** @var int */
    public static $cacheExpirationTime;

    /** @var string */
    public static $cacheKey;

    /** @var string */
    public static $cacheModelKey;

    /** @var bool */
    public static $cacheIsTaggable = false;

    /**
     * PermissionRegistrar constructor.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate $gate
     * @param \Illuminate\Cache\CacheManager $cacheManager
     */
    public function __construct(Gate $gate, CacheManager $cacheManager)
    {
        $this->gate = $gate;
        $this->permissionClass = config('permission.models.permission');
        $this->moduleClass = config('permission.models.module');

        $this->cacheManager = $cacheManager;
        $this->initializeCache();
    }

    protected function initializeCache()
    {
        self::$cacheExpirationTime = config('permission.cache.expiration_time', config('permission.cache_expiration_time'));
        self::$cacheKey = config('permission.cache.key');
        self::$cacheModelKey = config('permission.cache.model_key');

        $cache = $this->getCacheStoreFromConfig();

        self::$cacheIsTaggable = ($cache->getStore() instanceof \Illuminate\Cache\TaggableStore);

        $this->cache = self::$cacheIsTaggable ? $cache->tags(self::$cacheKey) : $cache;
    }

    protected function getCacheStoreFromConfig(): \Illuminate\Contracts\Cache\Repository
    {
        // the 'default' fallback here is from the permission.php config file, where 'default' means to use config(cache.default)
        $cacheDriver = config('permission.cache.store', 'default');

        // when 'default' is specified, no action is required since we already have the default instance
        if ($cacheDriver === 'default') {
            return $this->cacheManager->store();
        }

        // if an undefined cache store is specified, fallback to 'array' which is Laravel's closest equiv to 'none'
        if (! \array_key_exists($cacheDriver, config('cache.stores'))) {
            $cacheDriver = 'array';
        }

        return $this->cacheManager->store($cacheDriver);
    }

    /**
     * Register the permission check method on the gate.
     *
     * @return bool
     */
    public function registerPermissions(): bool
    {
        $this->gate->before(function (Authorizable $user, string $ability) {
            try {
                if (method_exists($user, 'hasPermissionTo')) {
                    return $user->hasPermissionTo($ability) ?: null;
                }
            } catch (PermissionDoesNotExist $e) {
            }
        });

        return true;
    }

    /**
     * Flush the cache.
     */
    public function forgetCachedPermissions()
    {
        self::$cacheIsTaggable ? $this->cache->flush() : $this->cache->forget(self::$cacheKey);
    }

    /**
     * Get the permissions based on the passed params.
     *
     * @param array $params
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPermissions(array $params = []): Collection
    {
        $permissions = $this->cache->remember($this->getKey($params), self::$cacheExpirationTime,
            function () use ($params) {
                return $this->getPermissionClass()
                    ->when($params && self::$cacheIsTaggable, function ($query) use ($params) {
                        return $query->where($params);
                    })
                    ->with('modules')
                    ->get();
            });

        if (! self::$cacheIsTaggable) {
            foreach ($params as $attr => $value) {
                $permissions = $permissions->where($attr, $value);
            }
        }

        return $permissions;
    }

    /**
     * Get the key for caching.
     *
     * @param $params
     *
     * @return string
     */
    public function getKey(array $params): string
    {
        if ($params && self::$cacheIsTaggable) {
            return self::$cacheKey.'.'.implode('.', array_values($params));
        }

        return self::$cacheKey;
    }

    /**
     * Get an instance of the permission class.
     *
     * @return \LeoMachado\Permission\Contracts\Permission
     */
    public function getPermissionClass(): Permission
    {
        return app($this->permissionClass);
    }

    /**
     * Get an instance of the module class.
     *
     * @return \LeoMachado\Permission\Contracts\Module
     */
    public function getModuleClass(): Module
    {
        return app($this->moduleClass);
    }

    /**
     * Get the instance of the Cache Store.
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getCacheStore(): \Illuminate\Contracts\Cache\Store
    {
        return $this->cache->getStore();
    }
}
