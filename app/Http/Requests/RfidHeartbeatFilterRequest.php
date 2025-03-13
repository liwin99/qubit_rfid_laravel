<?php

namespace App\Http\Requests;

class RfidHeartbeatFilterRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reader_name' => 'required|string',
            'heartbeat_datetime_from' => 'required|date_format:Y-m-d H:i:s',
            'heartbeat_datetime_to' => 'required|date_format:Y-m-d H:i:s',
        ];
    }
}
