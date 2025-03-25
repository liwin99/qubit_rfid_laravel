<?php
namespace App\Repositories;

use App\Models\RfidTagRead;
use App\Models\MasterLocation;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RfidTagReadRepository
{
    public function filter($filters)
    {
        $query = RfidTagRead::query();

        if (isset($filters['reader_name'])) {
            $query->where('reader_name', $filters['reader_name']);
        }

        if (isset($filters['epc'])) {
            $query->where('epc', $filters['epc']);
        }

        if (isset($filters['tag_read_datetime_from'])) {
            $query->where('tag_read_datetime', '>=', $filters['tag_read_datetime_from']);
        }

        if (isset($filters['tag_read_datetime_to'])) {
            $query->where('tag_read_datetime', '<=', $filters['tag_read_datetime_to']);
        }

        return $query->get();
    }

    public function filterTms($filters)
    {

        $query = RfidTagRead::leftJoin('rfid_reader_managements', 'rfid_tag_reads.reader_name', '=', 'rfid_reader_managements.name')
	    ->select('rfid_tag_reads.*',
	DB::raw('IF(rfid_tag_reads.reader_name IN ("Reader_1","Reader_2A","Reader_3A","Reader_3B"),DATE_ADD(rfid_tag_reads.tag_read_datetime,INTERVAL 8 HOUR),rfid_tag_reads.tag_read_datetime) AS tag_read_datetime'),
	DB::raw('IFNULL((SELECT NAME FROM master_projects WHERE rfid_reader_managements.project_id=master_projects.id),"N/A") AS proj'),
        DB::raw('IFNULL((SELECT NAME FROM master_locations WHERE rfid_reader_managements.location_1_id=master_locations.id),"N/A") AS loc1'),
        DB::raw('IFNULL((SELECT NAME FROM master_locations WHERE rfid_reader_managements.location_2_id=master_locations.id),"N/A") AS loc2'),
        DB::raw('IFNULL((SELECT NAME FROM master_locations WHERE rfid_reader_managements.location_3_id=master_locations.id),"N/A") AS loc3'),
        DB::raw('IFNULL((SELECT NAME FROM master_locations WHERE rfid_reader_managements.location_4_id=master_locations.id),"N/A") AS loc4')
	);

       if (isset($filters['reader_name'])) {
            $query->where('reader_name', $filters['reader_name']);
        }

        if (isset($filters['epc'])) {
            $query->where('epc', $filters['epc']);
        }

        if (isset($filters['tag_read_datetime_from'])) {
            $query->where('tag_read_datetime', '>=', date("Y-m-d H:i:s", strtotime($filters['tag_read_datetime_from'] . " -8 hours")));
        }

        if (isset($filters['tag_read_datetime_to'])) {
            $query->where('tag_read_datetime', '<=', date("Y-m-d H:i:s", strtotime($filters['tag_read_datetime_to'] . " -8 hours")));
        }

        return $query->orderBy('tag_read_datetime','desc')->get();

    }

    public function getSingleReaderTagReads($startingPeriod, $endingPeriod, $project)
    {
        $tagReads = RfidTagRead::select(
            'rfid_tag_reads.*',
            'rfid_reader_managements.id as reader_id',
            'rfid_reader_managements.name as reader_name',
            'master_projects.id as project_id',
            'master_projects.name as project_name'
        )
            ->leftJoin('rfid_reader_managements', 'rfid_tag_reads.reader_name', '=', 'rfid_reader_managements.name')
            ->leftJoin('master_projects', 'rfid_reader_managements.project_id', '=', 'master_projects.id')
            ->leftJoin('rfid_reader_pairings', function ($join) {
                $join->on('rfid_reader_managements.id', '=', 'rfid_reader_pairings.reader_id');
            })
            ->whereNotNull('rfid_reader_managements.name')
            ->where('rfid_reader_managements.used_for_attendance', true)
            ->whereNull('rfid_reader_pairings.reader_id')
            ->where('master_projects.id', $project->id)
            ->whereBetween('rfid_tag_reads.tag_read_datetime', [$startingPeriod, $endingPeriod])
            ->orderByDesc('rfid_tag_reads.tag_read_datetime')
            ->get();

        return $tagReads;
    }

    public function getDoubleReaderTagReads($startingPeriod, $endingPeriod, $project)
    {
        $tagReads = RfidTagRead::select(
            'rfid_tag_reads.*',
            'rfid_reader_managements.id as reader_id',
            'rfid_reader_managements.name as reader_name',
            'master_projects.id as project_id',
            'master_projects.name as project_name',
            'rfid_reader_pairings.pair_id',
            'rfid_reader_pairings.reader_position as current_reader_position',
            'paired_reader.reader_id as paired_reader_id',
            'paired_reader.reader_position as paired_reader_position'
        )
            ->leftJoin('rfid_reader_managements', 'rfid_tag_reads.reader_name', '=', 'rfid_reader_managements.name')
            ->leftJoin('master_projects', 'rfid_reader_managements.project_id', '=', 'master_projects.id')
            ->leftJoin('rfid_reader_pairings', function ($join) {
                $join->on('rfid_reader_managements.id', '=', 'rfid_reader_pairings.reader_id');
            })
            ->leftJoin('rfid_reader_pairings as paired_reader', function ($join) {
                $join->on('rfid_reader_pairings.pair_id', '=', 'paired_reader.pair_id')
                    ->where('rfid_reader_pairings.reader_id', '!=', 'rfid_reader_managements.id');
            })
            ->whereNotNull('rfid_reader_managements.name')
            ->where('rfid_reader_managements.used_for_attendance', true)
            ->whereNotNull('rfid_reader_pairings.pair_id')
            ->where('master_projects.id', $project->id)
            ->whereBetween('rfid_tag_reads.tag_read_datetime', [$startingPeriod, $endingPeriod])
            ->orderByDesc('rfid_tag_reads.tag_read_datetime')
            ->get();

        return $tagReads;
    }

    public static function getQuery($query){

        return vsprintf(str_replace('?', '%s', $query->toSql()), collect($query->getBindings())->map(function($binding){
            return is_numeric($binding) ? $binding : "'{$binding}'";
        })->toArray());

    }

    public function insertTagRead($data)
    {
        $readerName = $data['reader_name'];
        $eventData = $data['event_data'];
        $ipAddress = '';

        $parts = explode('/', $readerName);
        if (count($parts) === 2) {
            $readerName = $parts[0];
            $ipAddress = $parts[1];
        }

        $messages = [];
        foreach ($eventData as $key => $eventDatum) {
            try {
                $tagRead = [];

                // debounce if time received less than config threshold
                $lastSeendRfid = RfidTagRead::where('epc', $eventDatum['epc'])
                    ->where('reader_name', $readerName)
                    ->where('last_seen_timestamp', '>',$eventDatum['lastseen_timestamp'] - (int)(config('qubit.debounce_minutes') * 60 * 1000) )->exists();
                if ($lastSeendRfid) {
                    $messages['error']["event_data.$key.unique_hash"] = ['The tag read was recorded within ' . config('qubit.debounce_minutes') . ' minutes. Skipped insertion.'];
                    continue;
                }

                // debounce if same record is inserted
                $concatenatedString = $readerName . $eventDatum['epc'] . $eventDatum['read_count'] .
                    $eventDatum['firstseen_timestamp'] . $eventDatum['lastseen_timestamp'];
                $sha256Hash = hash('sha256', $concatenatedString);
                if (RfidTagRead::where('unique_hash', $sha256Hash)->exists()) {
                    $messages['error']["event_data.$key.unique_hash"] = ['The unique_hash already exists in the database. Skipped insertion.'];
                    continue;
                }

                // debounce if rssi is less than threshold
                if ($eventDatum['rssi'] < config('qubit.rssi_threshold')) {
                    $messages['error']["event_data.$key.rssi"] = ['The rssi is lesser than threshold. Skipped insertion.'];
                    continue;
                }

                $tagRead['unique_hash'] = $sha256Hash;
                $tagRead['reader_name'] = $readerName;
                $tagRead['ip_address'] = $ipAddress;
                $tagRead['epc'] = $eventDatum['epc'];
                $tagRead['bank_data'] = $eventDatum['bank_data'];
                $tagRead['antenna'] = $eventDatum['antenna'];
                $tagRead['read_count'] = $eventDatum['read_count'];
                $tagRead['protocol'] = $eventDatum['protocol'];
                $tagRead['rssi'] = $eventDatum['rssi'];
                $tagRead['first_seen_timestamp'] = $eventDatum['firstseen_timestamp'];
                $tagRead['last_seen_timestamp'] = $eventDatum['lastseen_timestamp'];

                $lastSeenTimestamp = $eventDatum['lastseen_timestamp'];
                $lastSeenDate = Carbon::createFromTimestampMs($lastSeenTimestamp);
                $lastSeenDateTimeString = $lastSeenDate->format('Y-m-d H:i:s');
                $tagRead['tag_read_datetime'] = $lastSeenDateTimeString;
                $tagRead['created_at'] = Carbon::now();

                RfidTagRead::insert($tagRead);
                $messages['data'][] = [
                    'row' => $key,
                    'result' => 'Item inserted successfully',
                ];
            } catch (\Throwable $th) {
                $messages['data'][] = [
                    'row' => $key,
                    'result' => 'Failed to insert. ' . $th->getMessage(),
                ];
                Log::info($th->getMessage());
            }

        }

        return $messages;
    }

    public function getTagReadLogsFromQTime($filters)
    {
        $query = RfidTagRead::leftJoin('rfid_reader_managements', 'rfid_tag_reads.reader_name', '=', 'rfid_reader_managements.name')
	    ->select('rfid_tag_reads.id',
                 'rfid_tag_reads.reader_name',
                 'rfid_tag_reads.epc',
                 'rfid_tag_reads.tag_read_datetime',
                 'rfid_tag_reads.created_at',
            DB::raw('IFNULL((SELECT NAME FROM master_projects WHERE rfid_reader_managements.project_id=master_projects.id),null) AS project_code'),
            DB::raw('IFNULL((SELECT NAME FROM master_locations WHERE rfid_reader_managements.location_1_id=master_locations.id),null) AS location_1'),
            DB::raw('IFNULL((SELECT NAME FROM master_locations WHERE rfid_reader_managements.location_2_id=master_locations.id),null) AS location_2'),
            DB::raw('IFNULL((SELECT NAME FROM master_locations WHERE rfid_reader_managements.location_3_id=master_locations.id),null) AS location_3'),
            DB::raw('IFNULL((SELECT NAME FROM master_locations WHERE rfid_reader_managements.location_4_id=master_locations.id),null) AS location_4'),
        );

        $query->whereIn('rfid_tag_reads.epc', $filters['rfid_tag_code']);

        if (!empty($filters['fromDateTime'])) {
            $filters['fromDateTime'] = Carbon::parse($filters['fromDateTime'])->utc()->format('Y-m-d H:i:s');
        }
        
        if (!empty($filters['toDateTime'])) {
            $filters['toDateTime'] = Carbon::parse($filters['toDateTime'])->utc()->format('Y-m-d H:i:s');
        }
    
        // Filter for a specific Period
        if (!empty($filters['fromDateTime']) && !empty($filters['toDateTime'])) {
            $query->whereBetween('rfid_tag_reads.tag_read_datetime', [$filters['fromDateTime'], $filters['toDateTime']]);
        } elseif (!empty($filters['fromDateTime'])) {
            $query->where('rfid_tag_reads.tag_read_datetime', '>=', $filters['fromDateTime']);
        } elseif (!empty($filters['toDateTime'])) {
            $query->where('rfid_tag_reads.tag_read_datetime', '<=', $filters['toDateTime']);
        }   
    
        // Search functionality
        if (!empty($filters['keyword'])) {
            $search = $filters['keyword'];
            $query->where(function ($q) use ($search) {
                $q->where('rfid_tag_reads.epc', 'LIKE', "%{$search}%");
            });
        }
    
        // Sorting functionality
        if (!empty($filters['sortBy'])) {
            $direction = $filters['sortOrder'] ?? 'asc';
            $query->orderBy($filters['sortBy'], $direction);
        }
    
        // Pagination (default: 10 per page)
        return $query->paginate($filters['limit'] ?? 10);
    }
}
