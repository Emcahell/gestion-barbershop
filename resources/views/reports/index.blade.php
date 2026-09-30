@extends('layouts.app')

@section('title', 'Reportes — BarberShop')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tighter uppercase">Reportes</h1>
            <p class="mt-1 text-sm text-neutral-600">
                Ingresos de las citas completadas de {{ $scopeLabel }}. Las citas canceladas no cuentan.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <span class="badge bg-steel px-3 py-1.5 text-white">
                ${{ number_format($totals['income'], 0, ',', '.') }} ingresos
            </span>
            <span class="badge bg-mustard px-3 py-1.5">{{ $totals['count'] }} servicios</span>
            <span class="badge bg-white px-3 py-1.5">
                promedio ${{ number_format($totals['avg'], 0, ',', '.') }}
            </span>
        </div>
    </div>

    {{-- Tarjetas de período: muestran ingresos y servicios, y sirven de filtro --}}
    <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($cards as $key => $card)
            <a
                href="{{ route('reports.index', ['periodo' => $key]) }}"
                @class([
                    'card block !p-4 transition',
                    'bg-mustard' => $key === $selected,
                    'hover:bg-paper' => $key !== $selected,
                ])
            >
                <span class="block text-[0.65rem] font-black tracking-widest uppercase">
                    {{ $card['label'] }}
                </span>
                <span class="mt-1 block text-2xl font-black tracking-tighter">
                    ${{ number_format($card['income'], 0, ',', '.') }}
                </span>
                <span class="block text-xs font-bold text-neutral-600">
                    {{ $card['count'] }} servicios completados
                </span>
            </a>
        @endforeach
    </div>

    {{-- Gráfico de ingresos del período seleccionado --}}
    <section class="card mt-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-black tracking-tight uppercase">Ingresos {{ $granularityLabel }}</h2>
            <span class="text-xs font-black tracking-widest uppercase text-neutral-500">
                {{ $selectedLabel }}
            </span>
        </div>

        @if ($chartMax <= 0)
            <p class="mt-6 mb-2 text-center text-sm text-neutral-500">
                No hay citas completadas en este período.
            </p>
        @else
            <div class="mt-4 flex gap-2">
                <div class="flex h-56 w-16 shrink-0 flex-col justify-between text-right text-[0.6rem] font-black text-neutral-500">
                    <span>${{ number_format($chartMax, 0, ',', '.') }}</span>
                    <span>${{ number_format($chartMax / 2, 0, ',', '.') }}</span>
                    <span>$0</span>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="relative h-56 border-b-2 border-l-2 border-ink">
                        <div class="absolute inset-x-0 top-0 border-t border-dashed border-neutral-300"></div>
                        <div class="absolute inset-x-0 top-1/2 border-t border-dashed border-neutral-300"></div>

                        <div class="flex h-full items-end gap-[3px] px-1">
                            @foreach ($buckets as $bucket)
                                @php
                                    $tooltip = $bucket['count'] > 0
                                        ? $bucket['tooltip']
                                            .' — $'.number_format($bucket['income'], 0, ',', '.')
                                            .' · '.$bucket['count'].' servicios'
                                        : $bucket['tooltip'].' — sin citas completadas';

                                    $height = $bucket['income'] > 0
                                        ? max(2, (int) round($bucket['income'] / $chartMax * 100)).'%'
                                        : '3px';
                                @endphp
                                <div
                                    class="min-w-0 flex-1"
                                    style="height: {{ $height }}"
                                    title="{{ $tooltip }}"
                                >
                                    <div @class([
                                        'h-full w-full border-2 border-ink',
                                        'bg-mustard' => $bucket['income'] > 0,
                                        'bg-neutral-200 !border-neutral-300' => $bucket['income'] <= 0,
                                    ])></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-1 flex gap-[3px] px-1">
                        @foreach ($buckets as $bucket)
                            <span class="min-w-0 flex-1 text-center text-[0.6rem] leading-tight font-bold text-neutral-500">
                                {{ $bucket['showLabel'] ? $bucket['label'] : '' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <p class="mt-3 text-xs text-neutral-500">
                Cada barra agrupa los ingresos {{ $granularityLabel }} del período seleccionado.
                Pasa el cursor sobre una barra para ver el detalle.
            </p>
        @endif
    </section>

    {{-- Ranking de clientes --}}
    <section class="mt-10">
        <h2 class="section-title">Top 10 clientes</h2>
        <p class="-mt-3 mb-4 text-xs font-bold tracking-widest text-neutral-500 uppercase">
            Citas completadas · {{ $selectedLabel }} (las canceladas no cuentan)
        </p>

        <div class="card overflow-x-auto !p-0">
            <table class="w-full text-left text-sm">
                <thead class="border-b-2 border-ink bg-paper text-xs font-black tracking-widest uppercase">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Teléfono</th>
                        <th class="px-4 py-3 text-right">Citas completadas</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ranking as $index => $client)
                        <tr class="border-b border-neutral-200 last:border-b-0">
                            <td class="px-4 py-3 font-black">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-bold">{{ $client['name'] }}</td>
                            <td class="px-4 py-3 text-neutral-500">{{ $client['phone'] }}</td>
                            <td class="px-4 py-3 text-right font-black">{{ $client['count'] }}</td>
                            <td class="px-4 py-3 text-right font-black">
                                ${{ number_format($client['spent'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-8 text-center text-neutral-500" colspan="5">
                                Aún no hay citas completadas en este período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
