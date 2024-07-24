<?php

namespace App\Http\Requests\MasterProject;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterProjectRequest extends FormRequest
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
            'name' => 'required|string|unique:master_projects,name,' . $this->masterProject->id,
            'daily_period_from' => 'required',
            'daily_period_to' => 'required',
        ];
    }
}
