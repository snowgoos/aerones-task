<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\File;

class FileRepository extends Repository
{
    public function __construct(File $model)
    {
        parent::__construct($model);
    }

    public function getWhereIdIn(array $ids)
    {
        return $this->model->whereIn('id', $ids)->get();
    }
}
