<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Repository
{
    protected Model $model;
    protected Builder $query;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->query = $this->model->query();
    }

    public function find(int $id, bool $lockForUpdate = false): ?Model
    {
        if ($lockForUpdate) {
            $this->query->lockForUpdate();
        }

        return $this->query->find($id);
    }

    public function findOneBy(array $criteria, bool $lockForUpdate = false): ?Model
    {
        $this->criteriaBuilder($criteria);

        if ($lockForUpdate) {
            $this->query->lockForUpdate();
        }

        return $this->query->first();
    }

    public function findAll(array $orderBy = []): Collection
    {
        return $this->findBy([], $orderBy);
    }

    public function findBy(array $criteria, array $orderBy = [], int $limit = null, int $offset = null): Collection
    {
        $this->criteriaBuilder($criteria);
        $this->orderBuilder($orderBy);

        if ($offset !== null && $offset > 0) {
            $this->query->offset($offset);
        }

        if ($limit !== null) {
            $this->query->limit($limit);
        }

        return $this->query->get();
    }

    protected function criteriaBuilder(array $criteria): void
    {
        $this->query->where(function (Builder $query) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if ($value === null) {
                    continue; // skip null values.
                }

                $query->where($key, '=', $value);
            }
        });
    }

    protected function orderBuilder(array $orderBy): void
    {
        foreach ($orderBy as $fieldName => $orientation) {
            $this->query->orderBy($fieldName, $orientation);
        }
    }
}
