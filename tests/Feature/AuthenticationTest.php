<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_register_with_name_email_and_phone(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Cliente Nuevo',
            'email' => 'nuevo@example.com',
            'phone' => '+58 412 123-4567',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@example.com',
            'phone' => '+58 412 123-4567',
            'role' => 'client',
        ]);
    }

    public function test_registration_ignores_injected_roles(): void
    {
        $this->post(route('register'), [
            'name' => 'Usuario Tramposo',
            'email' => 'tramposo@example.com',
            'phone' => '123456789',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'tramposo@example.com',
            'role' => 'client',
        ]);
    }

    public function test_guests_can_view_the_auth_forms(): void
    {
        $this->get(route('login'))->assertOk();

        $this->get(route('register'))->assertOk();
    }

    public function test_password_fields_have_a_button_to_show_them(): void
    {
        // Botón «Ver» (con su alterno «Ocultar») en cada campo de contraseña.
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('type="password"', false)
            ->assertSee('Ocultar', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('type="password"', false)
            ->assertSee('Ocultar', false);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('barbers.create'))
            ->assertOk()
            ->assertSee('type="password"', false)
            ->assertSee('Ocultar', false);
    }

    public function test_clients_can_login(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(route('appointments.index'));
    }

    public function test_barbers_are_redirected_to_the_panel_after_login(): void
    {
        $barber = User::factory()->barber()->create();

        $response = $this->post(route('login'), [
            'email' => $barber->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('panel.index'));
    }

    public function test_users_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'contraseña-equivocada',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
