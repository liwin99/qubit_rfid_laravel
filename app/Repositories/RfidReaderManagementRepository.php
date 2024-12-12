<?php

namespace App\Repositories;

use App\Models\RfidReaderManagement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RfidReaderManagementRepository
{
    public function filter($filters)
    {
        $query = RfidReaderManagement::query()
            ->select(DB::raw('rfid_reader_managements.*, master_projects.name as project_name'))
            ->leftJoin('master_projects', 'master_projects.id', '=', 'rfid_reader_managements.project_id');

            $query->with(['heartbeats' => function ($query) {
                $query->select('reader_name', DB::raw('MAX(heartbeat_datetime) as max_heartbeat_datetime'))
                    ->groupBy('reader_name');
            }]);
            
        if (isset($filters['project'])) {
            $query->where('master_projects.name', 'LIKE', "%{$filters['project']}%");
        }

        return $query->get();
    }

    public function getRfidReaderManagements($filter = [], $asList = true, $pagination = false)
    {
        $query = RfidReaderManagement::query()
            ->select(DB::raw('rfid_reader_managements.*, master_projects.name as project_name'))
            ->leftJoin('master_projects', 'master_projects.id', '=', 'rfid_reader_managements.project_id');

        if (isset($filter['relationships'])) {
            $query->with($filter['relationships']);
        }

        if (isset($filter['getReaderWithLatestHeartbeat'])) {
            $query->with(['heartbeats' => function ($query) {
                $query->select('reader_name', DB::raw('MAX(heartbeat_datetime) as max_heartbeat_datetime'))
                    ->groupBy('reader_name');
            }]);
        }

        if (isset($filter['doesntHavePairings'])) {
            $query->whereDoesntHave('pairing');
        }

        if (isset($filter['id'])) {
            $query->where('id', $filter['id']);
        }

        if (isset($filter['name'])) {
            $query->where('rfid_reader_managements.name', 'LIKE', "%{$filter['name']}%");
        }

        if (isset($filter['project_name'])) {
            $query->where('master_projects.name', 'LIKE', "%{$filter['project_name']}%");
        }

        if (isset($filter['location_name'])) {
            $query->whereHas('locationOne', function (Builder $query) use ($filter) {
                $query->where('name', 'LIKE', "%{$filter['location_name']}%");
            });

            $query->orWhereHas('locationTwo', function (Builder $query) use ($filter) {
                $query->where('name', 'LIKE', "%{$filter['location_name']}%");
            });

            $query->orWhereHas('locationThree', function (Builder $query) use ($filter) {
                $query->where('name', 'LIKE', "%{$filter['location_name']}%");
            });

            $query->orWhereHas('locationFour', function (Builder $query) use ($filter) {
                $query->where('name', 'LIKE', "%{$filter['location_name']}%");
            });
        }

        if (isset($filter['project_id'])) {
            $query->where('project_id', $filter['project_id']);
        }

        if (isset($filter['exact_location_name'])) {
            $query->whereHas('locationOne', function (Builder $query) use ($filter) {
                $query->where('name', '=', $filter['exact_location_name']);
            });

            $query->orWhereHas('locationTwo', function (Builder $query) use ($filter) {
                $query->where('name', '=', $filter['exact_location_name']);
            });

            $query->orWhereHas('locationThree', function (Builder $query) use ($filter) {
                $query->where('name', '=', $filter['exact_location_name']);
            });

            $query->orWhereHas('locationFour', function (Builder $query) use ($filter) {
                $query->where('name', '=', $filter['exact_location_name']);
            });
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

                if ($filter['sort_by'] == 'project_name') {
                    $filter['sort_by'] = 'master_projects.name';
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
        $rfidReaderManagement = RfidReaderManagement::create($input);

        return $rfidReaderManagement;
    }

    public function update(RfidReaderManagement $rfidReaderManagement, array $input)
    {
        $rfidReaderManagement->update($input);

        return $rfidReaderManagement->refresh();
    }

    public function destroy(RfidReaderManagement $rfidReaderManagement)
    {
        $rfidReaderManagement->delete();

        return true;
    }
}
