<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_barber_sees_only_their_own_appointments_ordered_by_time(): void
    {
        $barber = User::factory()->barber()->create();
        $otherBarber = User::factory()->barber()->create();
        $service = Service::factory()->create();

        $lateClient = User::factory()->create(['name' => 'Cliente Alfa']);
        $earlyClient = User::factory()->create(['name' => 'Cliente Beta']);
        $foreignClient = User::factory()->create(['name' => 'Cliente Gamma']);

        $this->createAppointment($barber, $lateClient, $service, '15:00');
        $this->createAppointment($barber, $earlyClient, $service, '09:00');
        $this->createAppointment($otherBarber, $foreignClient, $service, '10:00');

        $response = $this->actingAs($barber)->get(route('panel.index'));

        $response->assertOk()
            ->assertViewHas('appointments', function ($appointments) {
                return $appointments->pluck('time')->all() === ['09:00', '15:00'];
            })
            ->assertSee('Cliente Alfa')
            ->assertSee('Cliente Beta')
            ->assertDontSee('Cliente Gamma');
    }

    public function test_admins_see_the_whole_agenda(): void
    {
        $admin = User::factory()->admin()->create();
        $firstBarber = User::factory()->barber()->create();
        $secondBarber = User::factory()->barber()->create();
        $service = Service::factory()->create();

        $this->createAppointment($firstBarber, User::factory()->create(['name' => 'Cliente Alfa']), $service, '09:00');
        $this->createAppointment($secondBarber, User::factory()->create(['name' => 'Cliente Beta']), $service, '11:00');

        $response = $this->actingAs($admin)->get(route('panel.index'));

        $response->assertOk()
            ->assertSee('Cliente Alfa')
            ->assertSee('Cliente Beta')
            ->assertSee($firstBarber->name);
    }

    public function test_agenda_only_shows_appointments_from_today(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();

        $this->createAppointment($barber, User::factory()->create(), $service, '09:00', today()->addDay()->toDateString());

        $this->actingAs($barber)
            ->get(route('panel.index'))
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->isEmpty());
    }

    public function test_clients_cannot_access_the_panel(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get(route('panel.index'))->assertForbidden();
    }

    public function test_upcoming_client_bookings_are_listed_below_the_agenda(): void
    {
        $barber = User::factory()->barber()->create();
        $todayClient = User::factory()->create(['name' => 'Cliente Alfa']);
        $futureClient = User::factory()->create(['name' => 'Cliente Beta']);
        $service = Service::factory()->create();

        $nextWeek = today()->addDays(7)->toDateString();

        $this->createAppointment($barber, $todayClient, $service, '10:00');
        $this->createAppointment($barber, $futureClient, $service, '15:00', $nextWeek);

        $response = $this->actingAs($barber)->get(route('panel.index'));

        $response->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->count() === 1)
            ->assertViewHas('upcoming', function ($upcoming) use ($nextWeek) {
                return $upcoming->count() === 1
                    && $upcoming->first()->date->toDateString() === $nextWeek;
            })
            ->assertSee('Cliente Alfa')
            ->assertSee('Cliente Beta');
    }

    public function test_client_bookings_appear_in_the_barber_agenda(): void
    {
        $barber = User::factory()->barber()->create();
        $client = User::factory()->create();
        $service = Service::factory()->create();

        $this->actingAs($client)->post(route('appointments.store'), [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'date' => today()->toDateString(),
            'time' => '14:00',
        ])->assertRedirect(route('appointments.index'));

        $this->actingAs($barber)->get(route('panel.index'))
            ->assertOk()
            ->assertViewHas('appointments', function ($appointments) {
                return $appointments->count() === 1 && $appointments->first()->time === '14:00';
            })
            ->assertSee($client->name);
    }

    public function test_agenda_can_be_navigated_to_another_day(): void
    {
        $barber = User::factory()->barber()->create();
        $client = User::factory()->create();
        $service = Service::factory()->create();
        $tomorrow = today()->addDay()->toDateString();

        $this->createAppointment($barber, $client, $service, '10:00', $tomorrow);

        $this->actingAs($barber)->get(route('panel.index'))
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->isEmpty());

        $this->actingAs($barber)->get(route('panel.index', ['date' => $tomorrow]))
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments->count() === 1)
            ->assertSee($client->name);
    }

    public function test_barber_can_mark_an_appointment_as_completed(): void
    {
        $barber = User::factory()->barber()->create();
        $client = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'barber_id' => $barber->id,
            'date' => today()->toDateString(),
            'time' => '11:00',
        ]);

        $this->actingAs($barber)
            ->patch(route('panel.appointments.complete', $appointment))
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $appointment->fresh()->status->value);

        // El cambio se sincroniza en el historial del cliente.
        $this->actingAs($client)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Completada');
    }

    public function test_barber_cannot_complete_other_barbers_appointments(): void
    {
        $barber = User::factory()->barber()->create();
        $otherBarber = User::factory()->barber()->create();

        $appointment = Appointment::factory()->create([
            'barber_id' => $otherBarber->id,
            'date' => today()->toDateString(),
            'time' => '11:00',
        ]);

        $this->actingAs($barber)
            ->patch(route('panel.appointments.complete', $appointment))
            ->assertForbidden();

        $this->assertSame('scheduled', $appointment->fresh()->status->value);
    }

    private function createAppointment(
        User $barber,
        User $client,
        Service $service,
        string $time,
        ?string $date = null,
    ): Appointment {
        return Appointment::create([
            'user_id' => $client->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => $date ?? today()->toDateString(),
            'time' => $time,
            'status' => AppointmentStatus::Scheduled,
        ]);
    }
}
