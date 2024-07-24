<?php

namespace App\Http\Requests;

class RfidInsertRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reader_name' => 'string',
            'event_type' => 'required|string',
            'event_data' => ['required',
                function ($attribute, $value, $fail) {
                    if ($this->input('event_type') === 'tag_read' && !is_array($value)) {
                        $fail("The $attribute must be an array when event_type is tag_read.");
                    }

                    if ($this->input('event_type') === 'heart_beat' && !is_int($value)) {
                        $fail("The $attribute must be an integer when event_type is heart_beat.");
                    }
                }],
            'event_data.*.epc' => 'required_if:event_type,tag_read|string',
            'event_data.*.read_count' => 'required_if:event_type,tag_read|integer',
            'event_data.*.rssi' => 'required_if:event_type,tag_read|integer',
            'event_data.*.firstseen_timestamp' => 'required_if:event_type,tag_read|integer',
            'event_data.*.lastseen_timestamp' => 'required_if:event_type,tag_read|integer',
        ];
    }
}
