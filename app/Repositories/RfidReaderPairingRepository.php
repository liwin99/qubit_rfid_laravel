<?php

namespace App\Repositories;

use App\Models\RfidReaderPairing;

class RfidReaderPairingRepository
{
    public function getPairings($filter = [], $asList = true, $pagination = false)
    {
        $query = RfidReaderPairing::query();

        if (isset($filter['relationships'])) {
            $query->with($filter['relationships']);
        }

        if (isset($filter['pair_id'])) {
            $query->where('pair_id', $filter['pair_id']);
        }

        if (isset($filter['reader_id'])) {
            $query->where('reader_id', $filter['reader_id']);
        }

        if (isset($filter['name'])) {
            $query->whereHas('reader', function ($q) use ($filter) {
                $q->where('name', 'LIKE', "%{$filter['name']}%");
            });
        }

        if ($asList) {

            if (!isset($filter['sort_by'])) {
                $filter['sort_by'] = 'pair_id';
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

    public function destroy($pairId)
    {
        return RfidReaderPairing::where('pair_id', $pairId)->delete();
    }

    public function insert($pairId, $input)
    {
        if (
            !isset($input['reader_1_id']) &&
            !isset($input['reader_1_position']) &&
            !isset($input['reader_2_id']) &&
            !isset($input['reader_2_position'])
        ) {
            return;
        }

        $pairs[] = [
            'pair_id' => $pairId,
            'reader_id' => $input['reader_1_id'],
            'reader_position' => $input['reader_1_position'],
        ];

        $pairs[] = [
            'pair_id' => $pairId,
            'reader_id' => $input['reader_2_id'],
            'reader_position' => $input['reader_2_position'],
        ];

        RfidReaderPairing::insert($pairs);
    }
}
