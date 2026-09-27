<div>
    <label class="label" for="name">Nombre</label>
    <input
        class="input"
        id="name"
        type="text"
        name="name"
        value="{{ old('name', $service->name) }}"
        placeholder="Ej: Corte clásico"
        required
    >
</div>

<div>
    <label class="label" for="description">Descripción</label>
    <textarea class="input" id="description" name="description" rows="3"
        placeholder="Qué incluye el servicio">{{ old('description', $service->description) }}</textarea>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="price">Precio</label>
        <input
            class="input"
            id="price"
            type="number"
            name="price"
            step="0.01"
            min="0"
            value="{{ old('price', $service->price) }}"
            required
        >
    </div>

    <div>
        <label class="label" for="duration">Duración (minutos)</label>
        <input
            class="input"
            id="duration"
            type="number"
            name="duration"
            step="5"
            min="5"
            max="720"
            value="{{ old('duration', $service->duration) }}"
            required
        >
    </div>
</div>

<label class="flex items-center gap-2 text-sm font-bold">
    <input
        type="checkbox"
        name="is_active"
        value="1"
        class="size-4 accent-ink"
        @checked(old('is_active', $service->is_active ?? true))
    >
    Activo (visible para reservar)
</label>

<div class="flex gap-3">
    <button type="submit" class="btn btn-primary">Guardar servicio</button>
    <a href="{{ route('services.index') }}" class="btn btn-secondary">Cancelar</a>
</div>
