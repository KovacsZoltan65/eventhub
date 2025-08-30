<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
        /** @var Event $event */
        //$event = $this->route('event');
        //return $this->user()?->can('update', $event) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'       => ['sometimes','required','string','max:255'],
            'description' => ['nullable','string'],
            'starts_at'   => ['sometimes','required','date'],
            'location'    => ['sometimes','required','string','max:255'],
            'capacity'    => ['sometimes','required','integer','min:1','max:100000'],
            'category'    => ['nullable','string','max:100'],
            'status'      => ['sometimes','in:draft,published,cancelled'],
        ];
    }
}
