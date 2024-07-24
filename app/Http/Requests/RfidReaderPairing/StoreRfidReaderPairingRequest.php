<?php

namespace App\Http\Requests\RfidReaderPairing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRfidReaderPairingRequest extends FormRequest
{
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
            'reader_1_id' => [
                'required',
                'exists:rfid_reader_managements,id',
                'unique:rfid_reader_pairings,reader_id',
                function ($attribute, $value, $fail) {
                    // reader_1_id must not have the same value with reader_2_id
                    if ($value == $this->reader_2_id) {
                        $fail(__('Readers cannot be the same.'));
                    }
                }
            ],
            'reader_2_id' => [
                'required',
                'exists:rfid_reader_managements,id',
                'unique:rfid_reader_pairings,reader_id',
                function ($attribute, $value, $fail) {
                    // reader_2_id must not have the same value with reader_1_id
                    if ($value == $this->reader_1_id) {
                        $fail(__('Readers cannot be the same.'));
                    }
                }
            ],
            'reader_1_position' => [
                'required',
                Rule::in([1, 2]),
                function ($attribute, $value, $fail) {
                    // reader_1_position must not have the same value with reader_2_position
                    if ($value == $this->reader_2_position) {
                        $fail(__('Readers\' position cannot be the same.'));
                    }
                }
            ],
            'reader_2_position' => [
                'required',
                Rule::in([1, 2]),
                function ($attribute, $value, $fail) {
                    // reader_2_position must not have the same value with reader_1_position
                    if ($value == $this->reader_1_position) {
                        $fail(__('Readers\' position cannot be the same.'));
                    }
                }
            ],
        ];
    }
}
