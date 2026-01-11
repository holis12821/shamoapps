<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            //
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Optional: custom error message
     */
    public function messages(): array
    {
        return [
            'address.required' => 'Alamat pengiriman wajib diisi',
            'address.string'   => 'Alamat pengiriman tidak valid',
            'address.min'      => 'Alamat pengiriman terlalu pendek',
        ];
    }
}
