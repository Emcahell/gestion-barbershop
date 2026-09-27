<?php

namespace Tests\Feature;

use App\Models\BarberUnavailability;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_availability(): void
    {
        $this->get(route('availability.index'))->assertRedirect(route('login'));

        $this->post(route('availability.day-off'), ['date' => today()->addDay()->toDateString()])
            ->assertRedirect(route('login'));
    }

    public function test_only_barbers_can_manage_availability(): void
    {
        $client = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $date = today()->addDay()->toDateString();

        $this->actingAs($client)->get(route('availability.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('availability.index'))->assertForbidden();

        $this->actingAs($client)->post(route('availability.day-off'), ['date' => $date])->assertForbidden();
        $this->actingAs($admin)->post(route('availability.day-off'), ['date' => $date])->assertForbidden();

        $this->actingAs($client)
            ->post(route('availability.hours'), ['date' => $date, 'times' => ['09:00']])
            ->assertForbidden();

        $this->assertDatabaseCount('barber_unavailabilities', 0);
    }

    public function test_barber_sees_the_availability_calendar_with_all_slots(): void
    {
        $barber = User::factory()->barber()->create();
        $date = today()->addDay()->toDateString();

        $this->actingAs($barber)->get(route('availability.index', ['date' => $date]))
            ->assertOk()
            ->assertSee('Disponibilidad')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertViewHas('slots', fn ($slots) => count($slots) === 10);
    }

    public function test_barber_can_mark_a_day_as_unavailable_and_undo_it(): void
    {
        $barber = User::factory()->barber()->create();
        $date = today()->addDays(5)->toDateString();

        $this->actingAs($barber)
            ->post(route('availability.day-off'), ['date' => $date])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('barber_unavailabilities', [
            'barber_id' => $barber->id,
            'date' => $date,
            'time' => null,
        ]);

        // Volver a pulsar reactiva el día.
        $this->actingAs($barber)->post(route('availability.day-off'), ['date' => $date]);

        $this->assertDatabaseCount('barber_unavailabilities', 0);
    }

    public function test_barber_cannot_block_past_days(): void
    {
        $barber = User::factory()->barber()->create();

        $this->actingAs($barber)
            ->post(route('availability.day-off'), ['date' => today()->subDay()->toDateString()])
            ->assertSessionHasErrors('date');

        $this->assertDatabaseCount('barber_unavailabilities', 0);
    }

    public function test_barber_availability_does_not_leak_to_other_barbers(): void
    {
        $barber = User::factory()->barber()->create();
        $otherBarber = User::factory()->barber()->create();
        $date = today()->addDays(3)->toDateString();

        $this->actingAs($barber)
            ->post(route('availability.day-off'), ['date' => $date]);

        $this->assertFalse(BarberUnavailability::isDayOff((int) $otherBarber->id, $date));
        $this->assertTrue(BarberUnavailability::isDayOff((int) $barber->id, $date));
    }

    public function test_barber_can_block_specific_hours_of_a_day(): void
    {
        $barber = User::factory()->barber()->create();
        $date = today()->addDays(2)->toDateString();

        $this->actingAs($barber)->post(route('availability.hours'), [
            'date' => $date,
            'times' => ['09:00', '10:00'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            ['09:00', '10:00'],
            BarberUnavailability::blockedTimes((int) $barber->id, $date),
        );

        // Guardar de nuevo sustituye el conjunto anterior.
        $this->actingAs($barber)->post(route('availability.hours'), [
            'date' => $date,
            'times' => ['11:00'],
        ]);

        $this->assertSame(['11:00'], BarberUnavailability::blockedTimes((int) $barber->id, $date));

        // Enviar sin horas desbloquea el día por completo.
        $this->actingAs($barber)->post(route('availability.hours'), ['date' => $date]);

        $this->assertSame([], BarberUnavailability::blockedTimes((int) $barber->id, $date));
    }

    public function test_only_business_hours_can_be_blocked(): void
    {
        $barber = User::factory()->barber()->create();
        $date = today()->addDays(2)->toDateString();

        $this->actingAs($barber)->post(route('availability.hours'), [
            'date' => $date,
            'times' => ['07:00'],
        ])->assertSessionHasErrors('times.0');

        $this->assertDatabaseCount('barber_unavailabilities', 0);
    }

    public function test_barber_calendar_marks_the_blocked_days(): void
    {
        $barber = User::factory()->barber()->create();
        $date = today()->addDays(6)->toDateString();

        $this->actingAs($barber)->post(route('availability.day-off'), ['date' => $date]);

        $this->actingAs($barber)
            ->get(route('availability.index', ['month' => substr($date, 0, 7)]))
            ->assertOk()
            ->assertViewHas('dayOffs', fn ($dayOffs) => in_array($date, $dayOffs, true));

        // Con el día bloqueado seleccionado se ofrece reactivarlo.
        $this->actingAs($barber)
            ->get(route('availability.index', ['date' => $date]))
            ->assertOk()
            ->assertSee('Habilitar este día para reservas')
            ->assertDontSee('Horas no disponibles');

        // Los días pasados se pueden ver pero no modificar.
        $this->actingAs($barber)
            ->get(route('availability.index', ['date' => today()->subDay()->toDateString()]))
            ->assertOk()
            ->assertSee('Solo puedes modificar la disponibilidad de días de hoy en adelante.');
    }

    public function test_client_sees_blocked_days_disabled_in_the_booking_calendar(): void
    {
        $client = User::factory()->create();
        $barber = User::factory()->barber()->create();
        $date = today()->addDays(10)->toDateString();

        $this->actingAs($barber)->post(route('availability.day-off'), ['date' => $date]);

        $response = $this->actingAs($client)->get(route('appointments.create', [
            'barber_id' => $barber->id,
            'month' => substr($date, 0, 7),
        ]));

        $response->assertOk()
            ->assertViewHas('blockedDays', fn ($blockedDays) => in_array($date, $blockedDays, true))
            // La celda del día bloqueado deja de ser un enlace.
            ->assertDontSee('date='.$date, false);
    }

    public function test_client_cannot_book_on_a_day_off(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();
        $date = today()->addDays(4)->toDateString();

        $this->actingAs($barber)->post(route('availability.day-off'), ['date' => $date]);

        $this->actingAs(User::factory()->create())
            ->post(route('appointments.store'), [
                'service_id' => $service->id,
                'barber_id' => $barber->id,
                'date' => $date,
                'time' => '10:00',
            ])
            ->assertSessionHasErrors('date');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_client_cannot_book_a_blocked_hour(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();
        $date = today()->addDays(4)->toDateString();

        $this->actingAs($barber)->post(route('availability.hours'), [
            'date' => $date,
            'times' => ['10:00', '11:00'],
        ]);

        $client = User::factory()->create();

        $this->actingAs($client)
            ->post(route('appointments.store'), [
                'service_id' => $service->id,
                'barber_id' => $barber->id,
                'date' => $date,
                'time' => '11:00',
            ])
            ->assertSessionHasErrors('time');

        // Una hora no bloqueada sigue reservándose con normalidad.
        $this->actingAs($client)
            ->post(route('appointments.store'), [
                'service_id' => $service->id,
                'barber_id' => $barber->id,
                'date' => $date,
                'time' => '15:00',
            ])
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_booking_form_notices_when_the_barber_is_off_that_day(): void
    {
        $client = User::factory()->create();
        $barber = User::factory()->barber()->create(['name' => 'Don Ramón']);
        $service = Service::factory()->create();
        $date = today()->addDays(8)->toDateString();

        $this->actingAs($barber)->post(route('availability.day-off'), ['date' => $date]);

        $this->actingAs($client)->get(route('appointments.create', [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'date' => $date,
        ]))
            ->assertOk()
            ->assertViewHas('dayOff', true)
            ->assertSee('no atiende ese día');
    }

    public function test_booking_form_marks_blocked_hours_as_unavailable(): void
    {
        $client = User::factory()->create();
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();
        $date = today()->addDays(6)->toDateString();

        $this->actingAs($barber)->post(route('availability.hours'), [
            'date' => $date,
            'times' => ['09:00'],
        ]);

        $this->actingAs($client)->get(route('appointments.create', [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'date' => $date,
        ]))
            ->assertOk()
            ->assertViewHas('blockedTimes', fn ($blockedTimes) => $blockedTimes === ['09:00'])
            ->assertSee('Las horas en rojo están bloqueadas por el barbero ese día.');
    }

    public function test_barber_sees_the_availability_link_in_the_navigation(): void
    {
        $barber = User::factory()->barber()->create();

        $this->actingAs($barber)->get(route('panel.index'))
            ->assertOk()
            ->assertSee('Disponibilidad');

        // Ni el cliente ni el admin la ven.
        $this->actingAs(User::factory()->create())->get(route('home'))->assertDontSee('Disponibilidad');
        $this->actingAs(User::factory()->admin()->create())->get(route('home'))->assertDontSee('Disponibilidad');
    }
}
