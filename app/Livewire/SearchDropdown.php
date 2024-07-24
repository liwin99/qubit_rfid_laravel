<?php

namespace App\Livewire;

use App\Models\MasterLocation;
use App\Models\MasterProject;
use App\Models\MasterReader;
use Livewire\Attributes\On;
use Livewire\Component;

class SearchDropdown extends Component
{
    public $placeHolderName = '';
    public $placeHolderId = '';
    public $model;
    public $search = '';
    public $inputName;
    public $showDropdown = false;

    public function mount()
    {
        $this->inputName = $this->model;
        $this->setup();
    }

    public function render()
    {
        $results = [];

        if ($this->search == '') {
            $results = $this->model::limit(10)->orderBy('name')->get();
        } elseif (strlen($this->search) >= 2) {
            $results = $this->model::where('name', 'LIKE', '%' . $this->search . '%')->orderBy('name')->get();
        }

        return view('livewire.search-dropdown', [
            'results' => $results
        ]);
    }

    public function setInput($id, $name)
    {
        $this->placeHolderName = $name;
        $this->placeHolderId = $id;
        $this->showDropdown = false;
        $this->search = '';
    }

    public function removeInput()
    {
        $this->placeHolderName = '';
        $this->placeHolderId = '';
        $this->showDropdown = false;
        $this->search = '';
    }

    private function setup()
    {
        switch ($this->model) {
            case 'reader':
                $this->model = MasterReader::class;
                break;

            case 'project':
                $this->model = MasterProject::class;
                break;

            case str_contains($this->model, 'location'):
                $this->model = MasterLocation::class;
                break;

            default:
                break;
        }
    }
}
