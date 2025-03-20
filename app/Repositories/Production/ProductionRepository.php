<?php

namespace App\Repositories\Production;

use App\Models\Production;
use Illuminate\Database\Eloquent\Builder;

class ProductionRepository
{
    public function getBaseQuery(): Builder
    {
        return Production::query()->where('confirmed', false);
    }

    public function deleteById($id): bool
    {
        return Production::find($id)->delete();
    }

    public function update($id, array $data): bool
    {
        return Production::find($id)->update($data);
    }
}
