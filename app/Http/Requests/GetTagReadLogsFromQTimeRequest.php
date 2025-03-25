<?php

namespace App\Http\Requests;

class GetTagReadLogsFromQTimeRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rfid_tag_code'     => 'required|array',
            'rfid_tag_code.*'   => 'string',
            'fromDateTime'      => 'nullable|date_format:Y-m-d\TH:i:s\Z',
            'toDateTime'        => 'nullable|date_format:Y-m-d\TH:i:s\Z',
            'keyword'           => 'nullable|string|max:255',
            'sortBy'            => 'nullable|in:tag_read_datetime,epc,reader_name',
            'sortOrder'         => 'nullable|in:asc,desc',
            'limit'             => 'nullable|integer|min:1|max:100',
            'page'              => 'nullable|integer|min:1',
        ];
    }
}