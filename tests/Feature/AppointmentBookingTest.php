<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
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

    public function test_barbers_can_book_appointments_too(): void
    {
        $booker = User::factory()->barber()->create();
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();

        $this->actingAs($booker)->get(route('appointments.create'))->assertOk();

        $this->actingAs($booker)->post(route('appointments.store'), [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'date' => today()->addDay()->toDateString(),
            'time' => '16:00',
        ])->assertRedirect(route('appointments.index'));

        $this->assertDatabaseCount('appointments', 1);
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
}
