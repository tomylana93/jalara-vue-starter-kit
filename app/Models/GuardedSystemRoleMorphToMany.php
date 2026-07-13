<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * @extends MorphToMany<Role, User, MorphPivot, 'pivot'>
 */
class GuardedSystemRoleMorphToMany extends MorphToMany
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  bool  $touch
     */
    public function attach(mixed $id, array $attributes = [], mixed $touch = true): void
    {
        $this->getParent()->guardSystemRoleMutation();

        parent::attach($id, $attributes, $touch);
    }

    /**
     * @param  bool  $touch
     */
    public function detach(mixed $ids = null, mixed $touch = true): int
    {
        $this->getParent()->guardSystemRoleMutation();

        return parent::detach($ids, $touch);
    }

    /**
     * @param  Collection<array-key, mixed>|array<array-key, mixed>|Model  $ids
     * @param  bool  $detaching
     * @return array<string, array<int, string|int>>
     */
    public function sync(mixed $ids, mixed $detaching = true): array
    {
        $this->getParent()->guardSystemRoleMutation();

        return parent::sync($ids, $detaching);
    }
}
