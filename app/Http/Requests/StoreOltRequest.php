<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOltRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // RBAC handled by middleware
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:100'],
            'ip_address'        => ['required', 'ip', Rule::unique('olts', 'ip_address')],
            'hostname'          => ['nullable', 'string', 'max:255'],
            'vendor_id'         => ['required', 'exists:vendors,id'],
            'device_model_id'   => ['required', 'exists:device_models,id'],
            'site_id'           => ['nullable', 'exists:sites,id'],
            'firmware_profile_id' => ['nullable', 'exists:firmware_profiles,id'],

            // SNMP Credentials
            'snmp_version'      => ['required', Rule::in(['v1', 'v2c', 'v3'])],
            'snmp_community'    => ['required_unless:snmp_version,v3', 'nullable', 'string', 'max:100'],
            'snmp_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'snmp_v3_username'  => ['required_if:snmp_version,v3', 'nullable', 'string'],
            'snmp_v3_auth_protocol' => ['nullable', Rule::in(['MD5', 'SHA', 'SHA256', 'SHA512'])],
            'snmp_v3_auth_password' => ['nullable', 'string', 'min:8'],
            'snmp_v3_priv_protocol' => ['nullable', Rule::in(['DES', 'AES', 'AES192', 'AES256'])],
            'snmp_v3_priv_password' => ['nullable', 'string', 'min:8'],

            // CLI Credentials
            'cli_protocol'      => ['nullable', Rule::in(['ssh', 'telnet'])],
            'cli_port'          => ['nullable', 'integer', 'min:1', 'max:65535'],
            'cli_username'      => ['nullable', 'string', 'max:100'],
            'cli_password'      => ['nullable', 'string', 'max:255'],
            'cli_enable_password' => ['nullable', 'string', 'max:255'],

            'description'       => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'ip_address.unique' => 'An OLT with this IP address already exists.',
            'snmp_community.required_unless' => 'SNMP community string is required for SNMPv1/v2c.',
            'snmp_v3_username.required_if' => 'SNMPv3 username is required when using SNMPv3.',
        ];
    }
}
