<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SatuanRequest extends FormRequest
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
        $satuanId = $this->route('satuan') ? $this->route('satuan')->id : null;
        
        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('satuans', 'nama')->ignore($satuanId),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama satuan wajib diisi.',
            'nama.string' => 'Nama satuan harus berupa teks.',
            'nama.max' => 'Nama satuan maksimal 255 karakter.',
            'nama.unique' => 'Nama satuan sudah ada.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => strtolower(trim($this->nama ?? '')),
        ]);
    }
}
