{{-- Campo de contraseña con botón «Ver» para mostrar u ocultar lo que se escribe. --}}
@props(['id', 'name' => null, 'autocomplete' => 'current-password'])

<div class="relative">
    <input
        type="password"
        id="{{ $id }}"
        name="{{ $name ?? $id }}"
        autocomplete="{{ $autocomplete }}"
        {{ $attributes->merge(['class' => 'input pr-24']) }}
    >
    <button
        type="button"
        class="absolute inset-y-0 right-0 flex w-24 cursor-pointer items-center justify-center border-l-2 border-ink bg-white text-xs font-black tracking-widest uppercase text-ink hover:bg-mustard"
        onclick="const field = this.previousElementSibling; const showing = field.type === 'text'; field.type = showing ? 'password' : 'text'; this.textContent = showing ? 'Ver' : 'Ocultar';"
    >
        Ver
    </button>
</div>
