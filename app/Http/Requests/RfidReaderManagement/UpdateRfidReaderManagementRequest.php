<?php

namespace App\Http\Requests\RfidReaderManagement;

use App\Rules\AlphaDashUnderscore;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateRfidReaderManagementRequest extends FormRequest
{
    protected $errorBag = 'updateExistingReader';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', new AlphaDashUnderscore, 'unique:rfid_reader_managements,name,' . $this->rfidReaderManagement->id],
            'project_id' => 'required|exists:master_projects,id',
            'location_1_id' => 'required|exists:master_locations,id',
            'location_2_id' => [
                'required',
                'exists:master_locations,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $success = $this->validateLocations($this->location_1_id, $value);

                        if (!$success) {
                            $fail(__('Locations cannot be the same.'));
                        }
                    }
                }
            ],
            'location_3_id' => [
                'nullable',
                'exists:master_locations,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->location_2_id) {
                        $success = $this->validateLocations($this->location_1_id, $this->location_2_id, $value);

                        if (!$success) {
                            $fail(__('Locations cannot be the same.'));
                        }
                    }
                }
            ],
            'location_4_id' => [
                'nullable',
                'exists:master_locations,id',
                function ($attribute, $value, $fail) {
                    if (!$this->location_3_id) {
                        $fail(__('Locations 3 cannot be empty if location 4 is set.'));
                    }
                    if ($value && $this->location_2_id && $this->location_3_id) {
                        $success = $this->validateLocations($this->location_1_id, $this->location_2_id, $this->location_3_id, $value);

                        if (!$success) {
                            $fail(__('Locations cannot be the same.'));
                        }
                    }
                }
            ],
            'used_for_attendance' => 'boolean',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        session(['updateExistingReader' => $this->rfidReaderManagement->id]);

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    /**
     * Validate if variables passed has same values
     */
    private function validateLocations(...$locations)
    {
        $uniqueLocations = array_unique($locations);

        return count($uniqueLocations) === count($locations);
    }
}
