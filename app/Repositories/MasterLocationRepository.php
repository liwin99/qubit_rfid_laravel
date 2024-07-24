<?php

namespace App\Repositories;

use App\Models\MasterLocation;

class MasterLocationRepository
{
    public function getLocations($filter = [], $asList = true, $pagination = false)
    {
        $query = MasterLocation::query();

        if (isset($filter['id'])) {
            $query->where('id', $filter['id']);
        }

        if (isset($filter['name'])) {
            $query->where('name', 'LIKE', "%{$filter['name']}%");
        }

        if ($asList) {

            if (!isset($filter['sort_by'])) {
                $filter['sort_by'] = 'id';
                $filter['sort_direction'] = 'asc';
            }

            if (isset($filter['sort_by'])) {

                if (!isset($filter['sort_direction'])) {
                    $filter['sort_direction'] = 'asc';
                }

                $query->orderBy($filter['sort_by'], $filter['sort_direction']);
            }

            if ($pagination == true) {
                return $query->paginate($filter['per_page'] ?? 10);
            } else {
                return $query->get();
            }
        } else {
            return $query->first();
        }
    }

    public function store(array $input)
    {
        $masterLocation = MasterLocation::create($input);

        return $masterLocation;
    }

    public function update(MasterLocation $masterLocation, array $input)
    {
        $masterLocation->update($input);

        return $masterLocation->refresh();
    }

    public function destroy(MasterLocation $masterLocation)
    {
        $masterLocation->delete();

        return true;
    }
}
