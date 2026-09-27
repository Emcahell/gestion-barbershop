<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_services(): void
    {
        $this->get(route('services.index'))->assertRedirect(route('login'));

        $this->get(route('panel.index'))->assertRedirect(route('login'));
    }

    public function test_clients_cannot_access_the_admin_areas(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get(route('services.index'))->assertForbidden();

        $this->actingAs($client)->post(route('services.store'), [
            'name' => 'Servicio Irrelevante',
            'price' => 1000,
            'duration' => 15,
        ])->assertForbidden();

        $this->actingAs($client)->get(route('panel.index'))->assertForbidden();
    }

    public function test_barbers_can_view_the_service_screens(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();

        $this->actingAs($barber)->get(route('services.index'))->assertOk();

        $this->actingAs($barber)->get(route('services.create'))->assertOk();

        $this->actingAs($barber)->get(route('services.edit', $service))->assertOk();
    }

    public function test_barbers_can_create_services(): void
    {
        $barber = User::factory()->barber()->create();

        $response = $this->actingAs($barber)->post(route('services.store'), [
            'name' => 'Mascarilla facial',
            'description' => 'Limpieza profunda del rostro.',
            'price' => 3000,
            'duration' => 20,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'name' => 'Mascarilla facial',
            'duration' => 20,
            'is_active' => true,
        ]);
    }

    public function test_service_creation_requires_name_price_and_duration(): void
    {
        $barber = User::factory()->barber()->create();

        $this->actingAs($barber)
            ->post(route('services.store'), [])
            ->assertSessionHasErrors(['name', 'price', 'duration']);
    }

    public function test_admins_can_update_services(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin)->put(route('services.update', $service), [
            'name' => 'Combo premium',
            'description' => 'Corte, barba y cejas con acabado premium.',
            'price' => 9999,
            'duration' => 75,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('services.index'));

        $service->refresh();

        $this->assertSame('Combo premium', $service->name);
        $this->assertTrue($service->is_active);
    }

    public function test_services_are_deactivated_instead_of_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();

        $this->actingAs($admin)
            ->delete(route('services.destroy', $service))
            ->assertRedirect(route('services.index'));

        $this->assertFalse($service->fresh()->is_active);

        $this->assertDatabaseCount('services', 1);
    }
}
