<div class="row g-2 mb-2 align-items-center value-row">
    <input type="hidden" name="values[{{ $i }}][id]" value="{{ $val['id'] ?? '' }}">
    <div class="col-md-1 text-center text-muted fw-semibold fs-13">{{ $i + 1 }}</div>
    <div class="col-md-5">
        <input type="text" name="values[{{ $i }}][value]"
               class="form-control form-control-sm"
               value="{{ $val['value'] ?? '' }}"
               placeholder="Ej: Rojo, XL, Algodón...">
    </div>
    <div class="col-md-3 color-col">
        <input type="color"
               name="values[{{ $i }}][color_hex]"
               class="form-control form-control-color w-100 p-1 form-control-sm"
               value="{{ $val['color_hex'] ?? '#cccccc' }}">
    </div>
    <div class="col-md-2">
        <input type="number" name="values[{{ $i }}][order]"
               class="form-control form-control-sm text-center"
               value="{{ $val['order'] ?? $i }}" min="0">
    </div>
    <div class="col-md-1 text-center">
        <button type="button" class="btn btn-sm btn-ghost-danger remove-row">
            <i class="ri-delete-bin-line"></i>
        </button>
    </div>
</div>
