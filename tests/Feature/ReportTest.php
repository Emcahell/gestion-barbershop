<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_cannot_access_reports(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get(route('reports.index'))->assertForbidden();
    }

    public function test_barbers_and_admins_can_see_reports(): void
    {
        $barber = User::factory()->barber()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($barber)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reportes');

        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
    }

    public function test_income_only_counts_completed_appointments(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create(['price' => 100]);

        // Completada: suma.
        Appointment::factory()->completed()->create([
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => today()->toDateString(),
            'time' => '09:00',
        ]);

        // Cancelada y agendada: nunca suman.
        Appointment::factory()->cancelled()->create([
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => today()->toDateString(),
            'time' => '10:00',
        ]);
        Appointment::factory()->create([
            'barber_id' => $barber->id,
            'service_id' => Service::factory()->create(['price' => 999])->id,
            'date' => today()->toDateString(),
            'time' => '11:00',
        ]);

        $this->actingAs($barber)->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('cards', function ($cards) {
                return $cards['semana']['count'] === 1
                    && (int) $cards['semana']['income'] === 100
                    && (int) $cards['mes']['income'] === 100;
            });
    }

    public function test_completing_from_the_panel_stamps_completed_at_and_price(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create(['price' => 250]);

        $appointment = Appointment::factory()->create([
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => today()->toDateString(),
            'time' => '11:00',
        ]);

        $this->actingAs($barber)
            ->patch(route('panel.appointments.complete', $appointment))
            ->assertSessionHasNoErrors();

        $fresh = $appointment->fresh();

        $this->assertNotNull($fresh->completed_at);
        $this->assertSame(250.0, (float) $fresh->price);
    }

    public function test_periods_filter_the_report(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create(['price' => 100]);

        // Recién completada.
        Appointment::factory()->completed()->create([
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => today()->toDateString(),
            'time' => '09:00',
        ]);

        // Completada hace 40 días: fuera de la semana y del mes.
        $old = Appointment::factory()->completed()->create([
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => today()->subDays(40)->toDateString(),
            'time' => '10:00',
        ]);
        $old->forceFill(['completed_at' => now()->subDays(40)])->save();

        // Por defecto se muestra el mes.
        $this->actingAs($barber)->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('selected', 'mes')
            ->assertViewHas('totals', fn ($totals) => (int) $totals['income'] === 100 && $totals['count'] === 1);

        // Período desconocido cae en el mes por defecto.
        $this->actingAs($barber)->get(route('reports.index', ['periodo' => 'inventado']))
            ->assertOk()
            ->assertViewHas('selected', 'mes');

        // La semana excluye la cita antigua; los 3 meses la incluyen.
        $this->actingAs($barber)->get(route('reports.index', ['periodo' => 'semana']))
            ->assertOk()
            ->assertViewHas('cards', fn ($cards) => $cards['semana']['count'] === 1 && $cards['semana']['income'] == 100);

        $this->actingAs($barber)->get(route('reports.index', ['periodo' => 'tres-meses']))
            ->assertOk()
            ->assertViewHas('cards', fn ($cards) => $cards['tres-meses']['count'] === 2 && $cards['tres-meses']['income'] == 200)
            ->assertViewHas('totals', fn ($totals) => (int) $totals['income'] === 200 && $totals['count'] === 2);

        // El año en curso agrupa por mes y siempre incluye la cita reciente.
        $this->actingAs($barber)->get(route('reports.index', ['periodo' => 'anio']))
            ->assertOk()
            ->assertViewHas('selected', 'anio')
            ->assertViewHas('totals', fn ($totals) => $totals['count'] >= 1);
    }

    public function test_ranking_lists_top_clients_by_completed_appointments(): void
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create(['price' => 50]);

        $champion = User::factory()->create(['name' => 'Cliente Campeón']);

        foreach (range(1, 3) as $index) {
            Appointment::factory()->completed()->create([
                'user_id' => $champion->id,
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'date' => today()->subDays($index)->toDateString(),
                'time' => '09:00',
            ]);
        }

        // 12 clientes más con una sola cita completada.
        foreach (range(1, 12) as $index) {
            Appointment::factory()->completed()->create([
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'date' => today()->subDays($index)->toDateString(),
                'time' => '10:00',
            ]);
        }

        // Cliente con muchas citas canceladas: no debe aparecer.
        $cancelledOnly = User::factory()->create(['name' => 'Cliente Cancelado']);

        foreach (range(1, 5) as $index) {
            Appointment::factory()->cancelled()->create([
                'user_id' => $cancelledOnly->id,
                'barber_id' => $barber->id,
                'service_id' => $service->id,
                'date' => today()->addDays($index)->toDateString(),
                'time' => '11:00',
            ]);
        }

        $this->actingAs($barber)->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('ranking', function ($ranking) {
                return count($ranking) === 10
                    && $ranking[0]['name'] === 'Cliente Campeón'
                    && $ranking[0]['count'] === 3
                    && collect($ranking)->pluck('name')->doesntContain('Cliente Cancelado');
            })
            ->assertSee('Cliente Campeón')
            ->assertDontSee('Cliente Cancelado');
    }

    public function test_barbers_see_only_their_own_reports_and_admins_see_everything(): void
    {
        $barber = User::factory()->barber()->create();
        $otherBarber = User::factory()->barber()->create();
        $admin = User::factory()->admin()->create();

        Appointment::factory()->completed()->create([
            'barber_id' => $barber->id,
            'service_id' => Service::factory()->create(['price' => 100])->id,
            'date' => today()->toDateString(),
            'time' => '09:00',
        ]);
        Appointment::factory()->completed()->create([
            'barber_id' => $otherBarber->id,
            'service_id' => Service::factory()->create(['price' => 500])->id,
            'date' => today()->toDateString(),
            'time' => '10:00',
        ]);

        $this->actingAs($barber)->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('cards', fn ($cards) => (int) $cards['semana']['income'] === 100);

        $this->actingAs($admin)->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('cards', fn ($cards) => (int) $cards['semana']['income'] === 600);
    }
}
