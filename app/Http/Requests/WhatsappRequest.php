<?php

namespace App\Http\Requests;

use App\Enums\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WhatsappRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        if (request()->whatsapp_status == Activity::ENABLE) {
            return [
                'whatsapp_number'       => ['required', 'string', 'max:20'],
                'whatsapp_status'       => ['required', 'numeric'],
                'whatsapp_calling_code' => ['required', 'string', 'max:20'],

            ];
        } else {
            return [
                'whatsapp_number'       => ['nullable', 'string', 'max:20'],
                'whatsapp_status'       => ['required', 'numeric'],
                'whatsapp_calling_code' => ['nullable', 'string', 'max:20'],
            ];
        }
    }
}
