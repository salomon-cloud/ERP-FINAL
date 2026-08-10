<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Validation\Rule;

/** Alta y edicion de una categoria del catalogo. */
class GuardarCategoriaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->idEnRuta('categoria');

        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('categorias_producto', 'codigo')->ignore($id)->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            // Una categoria no puede colgar de si misma: seria un arbol con un
            // ciclo y idsDeLaRama() no volveria nunca.
            'padre_id' => ['nullable', 'integer', 'exists:categorias_producto,id', Rule::notIn([$id])],
            'activo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo')]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'padre_id.not_in' => 'Una categoria no puede ser su propia categoria padre.',
        ]);
    }
}
