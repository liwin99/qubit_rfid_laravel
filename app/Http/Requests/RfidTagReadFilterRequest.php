<?php

namespace App\Http\Requests;

class RfidTagReadFilterRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tag_read_datetime_from' => 'required|date_format:Y-m-d H:i:s',
            'tag_read_datetime_to' => 'required|date_format:Y-m-d H:i:s',
            'reader_name' => 'string',
            'epc' => 'string',
        ];
    }
}
