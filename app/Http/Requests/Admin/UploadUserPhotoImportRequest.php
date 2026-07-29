<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadUserPhotoImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->hasRole('superadmin') || $user->hasPermissionTo('users.bulk-import-photos'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'matching_type' => ['required', 'string', 'in:nis,nip'],
            'existing_photo_policy' => ['required', 'string', 'in:skip,replace'],
            'file' => ['required', 'file', 'mimes:zip', 'max:512000'], // 500 MB
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'matching_type.required' => 'Metode pencocokan (NIS atau NIP) wajib dipilih.',
            'matching_type.in' => 'Metode pencocokan harus berupa NIS atau NIP.',
            'existing_photo_policy.required' => 'Kebijakan foto existing wajib dipilih.',
            'existing_photo_policy.in' => 'Kebijakan foto existing harus berupa Skip atau Replace.',
            'file.required' => 'File ZIP foto wajib diupload.',
            'file.mimes' => 'File yang diupload harus berformat .zip',
            'file.max' => 'Ukuran file ZIP tidak boleh melebihi 500 MB.',
        ];
    }
}
