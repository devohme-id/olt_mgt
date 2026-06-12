<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOltRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $oltId = $this->route('olt')?->id ?? $this->route('olt');

        return [
            'name'              => ['sometimes', 'string', 'max:100'],
            'ip_address'        => ['sometimes', 'ip', Rule::unique('olts', 'ip_address')->ignore($oltId)->whereNull('deleted_at')],
            'hostname'          => ['nullable', 'string', 'max:255'],
            'vendor_id'         => ['sometimes', 'exists:vendors,id'],
            'device_model_id'   => ['sometimes', 'exists:device_models,id'],
            'site_id'           => ['nullable', 'exists:sites,id'],
            'firmware_profile_id' => ['nullable', 'exists:firmware_profiles,id'],
            'status'            => ['sometimes', Rule::in(['online', 'offline', 'maintenance', 'unknown'])],

            'snmp_version'      => ['sometimes', Rule::in(['v1', 'v2c', 'v3'])],
            'snmp_community'    => ['nullable', 'string', 'max:100'],
            'snmp_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'snmp_v3_username'  => ['nullable', 'string'],
            'snmp_v3_auth_protocol' => ['nullable', Rule::in(['MD5', 'SHA', 'SHA256', 'SHA512'])],
            'snmp_v3_auth_password' => ['nullable', 'string', 'min:8'],
            'snmp_v3_priv_protocol' => ['nullable', Rule::in(['DES', 'AES', 'AES192', 'AES256'])],
            'snmp_v3_priv_password' => ['nullable', 'string', 'min:8'],

            'cli_protocol'      => ['nullable', Rule::in(['ssh', 'telnet'])],
            'cli_port'          => ['nullable', 'integer', 'min:1', 'max:65535'],
            'cli_username'      => ['nullable', 'string', 'max:100'],
            'cli_password'      => ['nullable', 'string', 'max:255'],
            'cli_enable_password' => ['nullable', 'string', 'max:255'],

            'description'       => ['nullable', 'string', 'max:500'],
        ];
    }
}
