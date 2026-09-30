<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_cannot_access_client_appointments(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('appointments.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('appointments.create'))->assertForbidden();

        $this->actingAs($admin)->post(route('appointments.store'), [
            'service_id' => Service::factory()->create()->id,
            'barber_id' => User::factory()->barber()->create()->id,
            'date' => today()->addDay()->toDateString(),
            'time' => '10:00',
        ])->assertForbidden();

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_barbers_cannot_access_client_appointments(): void
    {
        $barber = User::factory()->barber()->create();

        // El barbero no reserva: el rol de citas es solo para clientes.
        $this->actingAs($barber)->get(route('appointments.index'))->assertForbidden();

        $this->actingAs($barber)->get(route('appointments.create'))->assertForbidden();

        $this->actingAs($barber)->post(route('appointments.store'), [
            'service_id' => Service::factory()->create()->id,
            'barber_id' => User::factory()->barber()->create()->id,
            'date' => today()->addDay()->toDateString(),
            'time' => '10:00',
        ])->assertForbidden();

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_clients_cannot_book_without_logging_in(): void
    {
        $response = $this->post(route('appointments.store'), [
            'service_id' => Service::factory()->create()->id,
            'barber_id' => User::factory()->barber()->create()->id,
            'date' => today()->addDay()->toDateString(),
            'time' => '10:00',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_booking_form_shows_the_slots_for_barber_and_date(): void
    {
        $client = User::factory()->create();
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($client)->get(route('appointments.create', [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'date' => today()->addDay()->toDateString(),
        ]));

        $response->assertOk()
            ->assertSee('Horarios disponibles')
            ->assertSee('09:00');
    }

    public function test_client_can_book_an_available_slot(): void
    {
        $client = User::factory()->create();
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();
        $date = today()->addDay()->toDateString();

        $response = $this->actingAs($client)->post(route('appointments.store'), [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'date' => $date,
            'time' => '10:00',
        ]);

        $response->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'user_id' => $client->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => $date,
            'time' => '10:00',
            'status' => 'scheduled',
        ]);
    }

    public function test_a_barber_cannot_have_two_appointments_at_the_same_date_and_time(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();
        $slot = [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'date' => today()->addDay()->toDateString(),
            'time' => '11:00',
        ];

        $this->actingAs(User::factory()->create())
            ->post(route('appointments.store'), $slot)
            ->assertRedirect(route('appointments.index'));

        $this->actingAs(User::factory()->create())
            ->post(route('appointments.store'), $slot)
            ->assertSessionHasErrors('time');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_cancelling_an_appointment_frees_the_slot(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();
        $firstClient = User::factory()->create();
        $secondClient = User::factory()->create();

        $slot = [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'date' => today()->addDays(2)->toDateString(),
            'time' => '16:00',
        ];

        $this->actingAs($firstClient)->post(route('appointments.store'), $slot);

        $appointment = Appointment::first();

        $this->actingAs($firstClient)->patch(route('appointments.cancel', $appointment));

        // Al cancelarse, la clave del turno queda libre (NULL) para otros.
        $this->assertNull($appointment->fresh()->slot_key);

        $this->actingAs($secondClient)
            ->post(route('appointments.store'), $slot)
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_appointment_date_cannot_be_in_the_past(): void
    {
        $client = User::factory()->create();

        $response = $this->actingAs($client)->post(route('appointments.store'), [
            'service_id' => Service::factory()->create()->id,
            'barber_id' => User::factory()->barber()->create()->id,
            'date' => today()->subDay()->toDateString(),
            'time' => '10:00',
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_appointment_time_must_be_within_business_hours(): void
    {
        $client = User::factory()->create();

        $response = $this->actingAs($client)->post(route('appointments.store'), [
            'service_id' => Service::factory()->create()->id,
            'barber_id' => User::factory()->barber()->create()->id,
            'date' => today()->addDay()->toDateString(),
            'time' => '20:00',
        ]);

        $response->assertSessionHasErrors('time');
    }

    public function test_appointment_time_must_match_a_fixed_slot(): void
    {
        $client = User::factory()->create();

        $response = $this->actingAs($client)->post(route('appointments.store'), [
            'service_id' => Service::factory()->create()->id,
            'barber_id' => User::factory()->barber()->create()->id,
            'date' => today()->addDay()->toDateString(),
            'time' => '10:30',
        ]);

        $response->assertSessionHasErrors('time');
    }

    public function test_deactivated_services_cannot_be_booked(): void
    {
        $client = User::factory()->create();

        $response = $this->actingAs($client)->post(route('appointments.store'), [
            'service_id' => Service::factory()->inactive()->create()->id,
            'barber_id' => User::factory()->barber()->create()->id,
            'date' => today()->addDay()->toDateString(),
            'time' => '10:00',
        ]);

        $response->assertSessionHasErrors('service_id');
    }

    public function test_database_rejects_a_second_scheduled_appointment_in_the_same_slot(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();
        $date = today()->addDays(3)->toDateString();

        Appointment::factory()->create([
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => $date,
            'time' => '12:00',
        ]);

        // La garantía final la da el índice único de slot_key.
        $this->expectException(UniqueConstraintViolationException::class);

        Appointment::factory()->create([
            'user_id' => User::factory()->create()->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => $date,
            'time' => '12:00',
        ]);
    }

    public function test_two_different_barbers_can_be_booked_at_the_same_time(): void
    {
        $service = Service::factory()->create();
        $date = today()->addDays(3)->toDateString();

        foreach (['first', 'second'] as $index) {
            Appointment::factory()->create([
                'barber_id' => User::factory()->barber()->create()->id,
                'service_id' => $service->id,
                'date' => $date,
                'time' => '12:00',
            ]);
        }

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_when_two_clients_race_only_one_gets_the_slot(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();
        $date = today()->addDays(4)->toDateString();

        $slot = [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'date' => $date,
            'time' => '13:00',
        ];

        // Simula al otro cliente que confirma exactamente entre la
        // verificación y la inserción de esta solicitud.
        $injected = false;

        Appointment::creating(function () use (&$injected, $barber, $service, $date): void {
            if ($injected) {
                return;
            }

            $injected = true;

            Appointment::create([
                'user_id' => User::factory()->create()->id,
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'date' => $date,
                'time' => '13:00',
                'status' => AppointmentStatus::Scheduled,
            ]);
        });

        $this->actingAs(User::factory()->create())
            ->post(route('appointments.store'), $slot)
            ->assertSessionHasErrors('time');

        // Solo quedó la cita del otro cliente: el turno nunca se duplica.
        $this->assertDatabaseCount('appointments', 1);
    }
}
