<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'title'       => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'starts_at'   => ['required','date'],
            'location'    => ['required','string','max:255'],
            'capacity'    => ['required','integer','min:1','max:100000'],
            'category'    => ['nullable','string','max:100'],
            'status'      => ['nullable','in:draft,published,cancelled'],
        ];
    }
    
    public function prepareForValidation(): void
    {
        if (!$this->has('status')) {
            $this->merge(['status' => 'draft']);
        }
    }
}
