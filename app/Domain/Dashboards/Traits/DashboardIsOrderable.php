<?php

namespace DDD\Domain\Dashboards\Traits;

use ArrayAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use InvalidArgumentException;

trait DashboardIsOrderable
{
    protected static function bootDashboardIsOrderable(): void
    {
        static::creating(function (Model $model) {
            if (!request()->order) {
                $model->setHighestOrderNumber();
            }
        });

        static::updating(function (Model $model) {
            if (request()->order) {
                $model->reorder(request()->order);
            }
        });
    }

    public function setHighestOrderNumber(): void
    {
        $this->order = $this->buildSortQuery()->max('order') + 1;
    }

    public function reorder(string $order)
    {
        // List related records by id
        $ids = $this->buildSortQuery()->pluck('id');

        // Remove then add self to list at new index
        $ids = $ids->reject($this->id);
        $ids->splice($order - 1, 0, $this->id);

        // Set new order for all records in list
        $this->setNewOrder($ids);
    }

    public function buildSortQuery(): Collection
    {
        return static::query()
            ->where('organization_id', $this->organization->id)
            ->orderBy('order')
            ->get();
    }

    public static function setNewOrder($ids, int $startOrder = 1, string $primaryKeyColumn = null): void
    {
        if (! is_array($ids) && ! $ids instanceof ArrayAccess) {
            throw new InvalidArgumentException('You must pass an array or ArrayAccess object to setNewOrder');
        }

        $model = new static();

        if (is_null($primaryKeyColumn)) {
            $primaryKeyColumn = $model->getKeyName();
        }

        foreach ($ids as $id) {
            static::withoutGlobalScope(SoftDeletingScope::class)
                ->where($primaryKeyColumn, $id)
                ->update(['order' => $startOrder++]);
        }
    }
}
