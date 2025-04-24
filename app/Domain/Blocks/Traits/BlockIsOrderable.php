<?php

namespace DDD\Domain\Blocks\Traits;

use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use ArrayAccess;

trait BlockIsOrderable
{
    protected static function bootBlockIsOrderable(): void
    {
        static::creating(function (Model $model) {
            Log::info('Creating block with order: ' . request()->order);

            if (!request()->order) {
                $model->setHighestOrderNumber();
                return;
            }

            $model->reorder(request()->order);
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
        // List related records by id and order
        $blocks = $this->buildSortQuery();
        
        // If this is a new model being created, we need to handle reordering differently
        if (!$this->exists) {
            // Update all blocks with order >= the requested order to shift them up
            static::withoutGlobalScope(SoftDeletingScope::class)
                ->where('page_id', $this->page_id)
                ->where('order', '>=', $order)
                ->increment('order');
                
            // Set the order for this new block
            $this->order = $order;
            return;
        }
        
        // For existing models being updated
        $ids = $blocks->pluck('id');
        
        // Remove then add self to list at new index
        $ids = $ids->reject($this->id);
        $ids->splice($order - 1, 0, $this->id);
        
        // Set new order for all records in list
        $this->setNewOrder($ids);
    }

    public function buildSortQuery(): Collection
    {
        return static::query()
            ->where('page_id', $this->page->id)
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
