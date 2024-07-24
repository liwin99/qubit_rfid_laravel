<?php

namespace App\Http\Requests;

class RfidHeartbeatInsertRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reader_name' => 'string|required',
            'ip_address' => 'string|required',
            'heart_datetime' => 'string|required',
            'heart_sequence_number' => 'integer|required'
        ];
    }
}
