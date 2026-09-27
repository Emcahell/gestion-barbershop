<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_only_active_services(): void
    {
        Service::factory()->create(['name' => 'Corte Samurai']);
        Service::factory()->inactive()->create(['name' => 'Servicio Retirado']);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Corte Samurai')
            ->assertDontSee('Servicio Retirado');
    }

    public function test_admin_does_not_see_booking_options(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('home'))
            ->assertOk()
            ->assertDontSee('Reservar')
            ->assertDontSee('Mis citas')
            ->assertSee('Ir a la agenda');
    }

    public function test_barbers_do_not_see_the_booking_options(): void
    {
        $barber = User::factory()->barber()->create();

        $this->actingAs($barber)->get(route('home'))
            ->assertOk()
            ->assertDontSee('Reservar')
            ->assertDontSee('Mis citas')
            ->assertSee('Ir a la agenda');
    }

    public function test_booking_form_requires_login(): void
    {
        $this->get(route('appointments.create'))->assertRedirect(route('login'));

        $this->get(route('appointments.index'))->assertRedirect(route('login'));
    }
}
