<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Reportes de la barbería: ingresos de las citas completadas, cantidad
     * de servicios realizados y ranking de los clientes que más citas
     * completaron, filtrables por período.
     *
     * El barbero solo ve sus propias citas; el administrador ve toda la
     * barbería. Las citas canceladas nunca se cuentan.
     */
    public function index(Request $request): View
    {
        $periods = $this->periods();
        $selected = $request->query('periodo');

        if (! is_string($selected) || ! array_key_exists($selected, $periods)) {
            $selected = 'mes';
        }

        $user = $request->user();

        // Una sola consulta alimenta las tarjetas, el gráfico y el ranking.
        $rows = $this->completedRows($user, $periods);

        // Ingresos y servicios completados de cada período (tarjetas).
        $cards = [];

        foreach ($periods as $key => $period) {
            $inPeriod = $rows->filter(fn (Appointment $row) => $this->inPeriod($row, $period));

            $cards[$key] = [
                'label' => $period['label'],
                'income' => (float) $inPeriod->sum(fn (Appointment $row) => (float) $row->price),
                'count' => $inPeriod->count(),
            ];
        }

        $selectedRows = $rows->filter(fn (Appointment $row) => $this->inPeriod($row, $periods[$selected]));
        $income = (float) $selectedRows->sum(fn (Appointment $row) => (float) $row->price);
        $count = $selectedRows->count();
        $buckets = $this->buckets($periods[$selected], $selectedRows);

        return view('reports.index', [
            'cards' => $cards,
            'selected' => $selected,
            'selectedLabel' => $periods[$selected]['label'],
            'totals' => [
                'income' => $income,
                'count' => $count,
                'avg' => $count > 0 ? $income / $count : 0,
            ],
            'buckets' => $buckets,
            'chartMax' => max(array_column($buckets, 'income')),
            'ranking' => $this->ranking($selectedRows),
            'scopeLabel' => $user->isAdmin() ? 'toda la barbería' : 'tus citas',
            'granularityLabel' => match ($periods[$selected]['bucket']) {
                'day' => 'por día',
                'week' => 'por semana',
                'month' => 'por mes',
            },
        ]);
    }

    /**
     * Definición de los períodos disponibles (todos terminan hoy).
     *
     * @return array<string, array{label: string, from: Carbon, to: Carbon, bucket: string}>
     */
    private function periods(): array
    {
        $end = today()->endOfDay();

        return [
            'semana' => [
                'label' => 'Últimos 7 días',
                'from' => today()->subDays(6),
                'to' => $end,
                'bucket' => 'day',
            ],
            'mes' => [
                'label' => 'Últimos 30 días',
                'from' => today()->subDays(29),
                'to' => $end,
                'bucket' => 'day',
            ],
            'tres-meses' => [
                'label' => 'Últimos 3 meses',
                'from' => today()->subDays(89),
                'to' => $end,
                'bucket' => 'week',
            ],
            'anio' => [
                'label' => 'Año en curso',
                'from' => today()->startOfYear(),
                'to' => $end,
                'bucket' => 'month',
            ],
        ];
    }

    /**
     * Citas completadas dentro de la ventana más amplia de todos los
     * períodos, con el ámbito del usuario (barbero propio o barbería).
     *
     * @param  array<string, array{label: string, from: Carbon, to: Carbon, bucket: string}>  $periods
     * @return Collection<int, Appointment>
     */
    private function completedRows(User $user, array $periods): Collection
    {
        $earliest = $periods['semana']['from'];

        foreach ($periods as $period) {
            if ($period['from']->lessThan($earliest)) {
                $earliest = $period['from'];
            }
        }

        return Appointment::query()
            ->where('status', AppointmentStatus::Completed)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $earliest->toDateTimeString())
            ->where('completed_at', '<=', $periods['semana']['to']->toDateTimeString())
            ->when($user->isBarber(), fn ($query) => $query->where('barber_id', $user->id))
            ->get(['user_id', 'completed_at', 'price']);
    }

    /**
     * ¿La cita se completó dentro del período indicado?
     *
     * @param  array{label: string, from: Carbon, to: Carbon, bucket: string}  $period
     */
    private function inPeriod(Appointment $row, array $period): bool
    {
        return $row->completed_at->greaterThanOrEqualTo($period['from'])
            && $row->completed_at->lessThanOrEqualTo($period['to']);
    }

    /**
     * Barras del gráfico: ingresos agregados por día, semana o mes.
     *
     * @param  array{label: string, from: Carbon, to: Carbon, bucket: string}  $period
     * @param  Collection<int, Appointment>  $rows
     * @return list<array{key: string, label: string, tooltip: string, income: float, count: int, showLabel: bool}>
     */
    private function buckets(array $period, Collection $rows): array
    {
        $totals = [];

        foreach ($rows as $row) {
            $key = $this->bucketKey($row->completed_at, $period['bucket']);

            $totals[$key]['income'] = ($totals[$key]['income'] ?? 0) + (float) $row->price;
            $totals[$key]['count'] = ($totals[$key]['count'] ?? 0) + 1;
        }

        $cursor = match ($period['bucket']) {
            'day' => $period['from']->copy(),
            'week' => $period['from']->copy()->startOfWeek(),
            'month' => $period['from']->copy()->startOfMonth(),
        };

        $end = today();
        $buckets = [];

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $this->bucketKey($cursor, $period['bucket']);

            $buckets[] = [
                'key' => $key,
                'label' => $this->bucketLabel($cursor, $period['bucket']),
                'tooltip' => $this->bucketTooltip($cursor, $period['bucket']),
                'income' => (float) ($totals[$key]['income'] ?? 0),
                'count' => (int) ($totals[$key]['count'] ?? 0),
            ];

            $cursor = match ($period['bucket']) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                'month' => $cursor->addMonthNoOverflow(),
            };
        }

        // Etiquetas espaciadas para que no se encimen en el eje.
        $step = $period['bucket'] === 'month'
            ? 1
            : max(1, (int) ceil(count($buckets) / 8));

        foreach ($buckets as $index => &$bucket) {
            $bucket['showLabel'] = $index % $step === 0 || $index === count($buckets) - 1;
        }

        unset($bucket);

        return $buckets;
    }

    /**
     * Identificador de la barra a la que pertenece un momento.
     */
    private function bucketKey(Carbon $moment, string $bucket): string
    {
        return match ($bucket) {
            'day' => $moment->toDateString(),
            'week' => $moment->copy()->startOfWeek()->toDateString(),
            'month' => $moment->format('Y-m'),
        };
    }

    /**
     * Etiqueta corta del eje X.
     */
    private function bucketLabel(Carbon $moment, string $bucket): string
    {
        return match ($bucket) {
            'day' => (string) $moment->day,
            'week' => $moment->isoFormat('D MMM'),
            'month' => $moment->isoFormat('MMM'),
        };
    }

    /**
     * Texto del tooltip de la barra.
     */
    private function bucketTooltip(Carbon $moment, string $bucket): string
    {
        return match ($bucket) {
            'day' => $moment->isoFormat('dddd D [de] MMM'),
            'week' => $moment->isoFormat('D MMM').' – '.$moment->copy()->addDays(6)->isoFormat('D MMM'),
            'month' => $moment->isoFormat('MMMM [de] YYYY'),
        };
    }

    /**
     * Top 10 de clientes con más citas completadas dentro del período.
     *
     * @param  Collection<int, Appointment>  $rows
     * @return list<array{name: string, phone: string, count: int, spent: float}>
     */
    private function ranking(Collection $rows): array
    {
        $byClient = [];

        foreach ($rows as $row) {
            $byClient[$row->user_id] ??= ['count' => 0, 'spent' => 0.0];
            $byClient[$row->user_id]['count']++;
            $byClient[$row->user_id]['spent'] += (float) $row->price;
        }

        uasort(
            $byClient,
            fn (array $a, array $b) => ($b['count'] <=> $a['count']) ?: ($b['spent'] <=> $a['spent']),
        );

        $top = array_slice($byClient, 0, 10, true);

        if ($top === []) {
            return [];
        }

        $clients = User::whereIn('id', array_keys($top))
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');

        return collect($top)
            ->map(fn (array $stats, int $clientId) => [
                'name' => $clients[$clientId]->name ?? 'Cliente',
                'phone' => $clients[$clientId]->phone ?? '—',
                'count' => $stats['count'],
                'spent' => $stats['spent'],
            ])
            ->values()
            ->all();
    }
}
