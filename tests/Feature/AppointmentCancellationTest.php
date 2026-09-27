<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_view_their_appointments_list(): void
    {
        $client = User::factory()->create();

        Appointment::factory()->create([
            'user_id' => $client->id,
            'date' => today()->addDays(3)->toDateString(),
            'time' => '10:00',
        ]);

        $this->actingAs($client)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Próximas citas')
            ->assertSee('Historial');
    }

    public function test_client_can_cancel_more_than_two_hours_before_the_appointment(): void
    {
        $client = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'date' => today()->addDays(3)->toDateString(),
            'time' => '10:00',
        ]);

        $response = $this->actingAs($client)
            ->patch(route('appointments.cancel', $appointment));

        $response->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $appointment->fresh()->status->value);
    }

    public function test_client_cannot_cancel_less_than_two_hours_before_the_appointment(): void
    {
        $client = User::factory()->create();
        $startsAt = now()->addHour();

        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'date' => $startsAt->toDateString(),
            'time' => $startsAt->format('H:i'),
        ]);

        $response = $this->actingAs($client)
            ->patch(route('appointments.cancel', $appointment));

        $response->assertSessionHasErrors('appointment');

        $this->assertSame('scheduled', $appointment->fresh()->status->value);
    }

    public function test_client_cannot_cancel_another_clients_appointment(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'user_id' => $owner->id,
            'date' => today()->addDays(3)->toDateString(),
            'time' => '10:00',
        ]);

        $this->actingAs($intruder)
            ->patch(route('appointments.cancel', $appointment))
            ->assertForbidden();

        $this->assertSame('scheduled', $appointment->fresh()->status->value);
    }

    public function test_guests_cannot_cancel_appointments(): void
    {
        $appointment = Appointment::factory()->create([
            'date' => today()->addDays(3)->toDateString(),
            'time' => '10:00',
        ]);

        $this->patch(route('appointments.cancel', $appointment))
            ->assertRedirect(route('login'));
    }

    public function test_already_cancelled_appointments_cannot_be_cancelled_twice(): void
    {
        $client = User::factory()->create();

        $appointment = Appointment::factory()->cancelled()->create([
            'user_id' => $client->id,
            'date' => today()->addDays(3)->toDateString(),
            'time' => '10:00',
        ]);

        $this->actingAs($client)
            ->patch(route('appointments.cancel', $appointment))
            ->assertSessionHasErrors('appointment');

        $this->assertSame('cancelled', $appointment->fresh()->status->value);
    }
}
