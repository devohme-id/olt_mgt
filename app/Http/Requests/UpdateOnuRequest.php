<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOnuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'      => ['nullable', 'string', 'max:255'],
            'customer_id'        => ['nullable', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:500'],
            'service_profile_id' => ['nullable', 'exists:service_profiles,id'],
            'tags'               => ['nullable', 'array'],
        ];
    }
}
