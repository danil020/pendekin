<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLinkRequest extends FormRequest
{
    private const array RESERVED_ALIASES = [
        'health',
        'api',
        'login',
        'register',
        'logout',
        'links',
        'up',
        'sanctum',
        'storage',
    ];

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'original_url' => ['required', 'url:http,https', 'max:2048'],
            'alias' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z0-9-]+$/',
                'min:3',
                'max:30',
                'unique:links,code',
                'not_in:'.implode(',', self::RESERVED_ALIASES),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'original_url.required' => 'URL tujuan wajib diisi.',
            'original_url.url' => 'URL tujuan harus valid dan menggunakan HTTP atau HTTPS.',
            'original_url.max' => 'URL tujuan maksimal 2048 karakter.',
            'alias.regex' => 'Alias hanya boleh berisi huruf, angka, dan tanda hubung.',
            'alias.min' => 'Alias minimal 3 karakter.',
            'alias.max' => 'Alias maksimal 30 karakter.',
            'alias.unique' => 'Alias sudah digunakan.',
            'alias.not_in' => 'Alias menggunakan kata yang dicadangkan.',
            'expires_at.date' => 'Tanggal kedaluwarsa tidak valid.',
            'expires_at.after' => 'Tanggal kedaluwarsa harus setelah waktu sekarang.',
        ];
    }
}
