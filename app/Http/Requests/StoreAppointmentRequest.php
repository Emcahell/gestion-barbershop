<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => [
                'required',
                Rule::exists('services', 'id')->where('is_active', true),
            ],
            'barber_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'barber'),
            ],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i', Rule::in(Appointment::slots())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $slots = Appointment::slots();

        return [
            'service_id.required' => 'Debes elegir un servicio.',
            'service_id.exists' => 'El servicio seleccionado no está disponible.',
            'barber_id.required' => 'Debes elegir un barbero.',
            'barber_id.exists' => 'El barbero seleccionado no está disponible.',
            'date.required' => 'La fecha es obligatoria.',
            'date.after_or_equal' => 'La fecha de la cita debe ser igual o posterior a hoy.',
            'time.required' => 'La hora es obligatoria.',
            'time.date_format' => 'La hora tiene un formato inválido.',
            'time.in' => sprintf(
                'La hora debe corresponder a un turno disponible dentro del horario de atención (%s a %s).',
                head($slots),
                last($slots),
            ),
        ];
    }
}
