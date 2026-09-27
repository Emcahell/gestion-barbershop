<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\BarberUnavailability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
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
     * Reglas adicionales: el barbero no puede tener bloqueado el día
     * completo ni la hora elegida (días y horas que él mismo marcó
     * como no disponibles en su pantalla de disponibilidad).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $barberId = $this->input('barber_id');
                $date = $this->input('date');

                if (! $barberId || ! is_string($date) || $date === '') {
                    return;
                }

                try {
                    $date = Carbon::parse($date)->toDateString();
                } catch (\Throwable) {
                    return;
                }

                $barberId = (int) $barberId;

                if (BarberUnavailability::isDayOff($barberId, $date)) {
                    $validator->errors()->add('date', 'El barbero seleccionado no atiende ese día. Elige otra fecha.');

                    return;
                }

                $time = $this->input('time');

                if (is_string($time) && in_array($time, BarberUnavailability::blockedTimes($barberId, $date), true)) {
                    $validator->errors()->add('time', 'El barbero seleccionado no atiende a esa hora ese día. Elige otro horario.');
                }
            },
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
