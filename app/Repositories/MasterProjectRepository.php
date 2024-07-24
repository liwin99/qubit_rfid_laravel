<?php

namespace App\Repositories;

use App\Models\MasterProject;

class MasterProjectRepository
{
    public function getProjects($filter = [], $asList = true, $pagination = false)
    {
        $query = MasterProject::query();

        if (isset($filter['relationships'])) {
            $query->with($filter['relationships']);
        }

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
        $masterProject = MasterProject::create($input);

        return $masterProject;
    }

    public function update(MasterProject $masterProject, array $input)
    {
        $masterProject->update($input);

        return $masterProject->refresh();
    }

    public function destroy(MasterProject $masterProject)
    {
        $masterProject->delete();

        return true;
    }
}
