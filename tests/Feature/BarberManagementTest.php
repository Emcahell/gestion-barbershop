<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_barber_management(): void
    {
        $this->get(route('barbers.index'))->assertRedirect(route('login'));

        $this->get(route('barbers.create'))->assertRedirect(route('login'));
    }

    public function test_only_admins_can_access_barber_management(): void
    {
        $client = User::factory()->create();
        $barber = User::factory()->barber()->create();

        $this->actingAs($client)->get(route('barbers.index'))->assertForbidden();

        // Ni siquiera otro barbero puede gestionar cuentas.
        $this->actingAs($barber)->get(route('barbers.index'))->assertForbidden();

        $this->actingAs($barber)->post(route('barbers.store'), [
            'name' => 'Intruso',
            'email' => 'intruso@barbershop.test',
            'phone' => '+58 412 123-4567',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@barbershop.test']);
    }

    public function test_admin_can_create_a_barber_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('barbers.store'), [
            'name' => 'Carlos Mendoza',
            'email' => 'carlos@barbershop.test',
            'phone' => '+58 412 123-4567',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $response->assertRedirect(route('barbers.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Carlos Mendoza',
            'email' => 'carlos@barbershop.test',
            'phone' => '+58 412 123-4567',
            'role' => 'barber',
        ]);
    }

    public function test_barber_creation_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('barbers.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'phone', 'password']);
    }

    public function test_barber_email_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->barber()->create(['email' => 'ocupado@barbershop.test']);

        $this->actingAs($admin)->post(route('barbers.store'), [
            'name' => 'Otro Barbero',
            'email' => 'ocupado@barbershop.test',
            'phone' => '+58 412 123-4567',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_sees_the_barber_list(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->barber()->create(['name' => 'Carlos Mendoza']);

        $this->actingAs($admin)
            ->get(route('barbers.index'))
            ->assertOk()
            ->assertSee('Carlos Mendoza');

        $this->actingAs($admin)->get(route('barbers.create'))->assertOk();
    }
}
