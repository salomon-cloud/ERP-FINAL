<?php

declare(strict_types=1);

namespace App\Modules\RH\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Enums\EstadoDocumentoEmpleado;
use App\Modules\RH\Enums\TipoDocumentoEmpleado;
use App\Modules\RH\Models\DocumentoEmpleado;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Requests\GuardarDocumentoEmpleadoRequest;
use App\Modules\RH\Utils\OpcionesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * El expediente digital.
 *
 * Aqui solo vive el metadato: el archivo es de la tabla compartida `adjuntos`.
 */
class DocumentoEmpleadoController extends Controller
{
    public function index(Request $peticion): View
    {
        $documentos = DocumentoEmpleado::query()
            ->with('empleado')
            ->when($peticion->filled('empleado_id'),
                fn ($consulta) => $consulta->where('empleado_id', $peticion->integer('empleado_id')))
            ->when($peticion->filled('tipo_documento'),
                fn ($consulta) => $consulta->where('tipo_documento', $peticion->input('tipo_documento')))
            ->when($peticion->filled('estado'),
                fn ($consulta) => $consulta->where('estado', $peticion->input('estado')))
            ->when($peticion->boolean('por_vencer'), fn ($consulta) => $consulta->porVencer())
            ->orderByDesc('vigencia')
            ->paginate(15)
            ->withQueryString();

        return view('rh::paginas.documentos.index', [
            'documentos' => $documentos,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'tipos' => OpcionesEnum::de(TipoDocumentoEmpleado::class),
            'estados' => OpcionesEnum::de(EstadoDocumentoEmpleado::class),
        ]);
    }

    public function create(): View
    {
        return view('rh::paginas.documentos.create', $this->datosDelFormulario(new DocumentoEmpleado));
    }

    public function store(GuardarDocumentoEmpleadoRequest $peticion): RedirectResponse
    {
        DocumentoEmpleado::create($peticion->validated());

        return redirect()->route('rh.documentos.index')
            ->with('success', 'Documento registrado correctamente.');
    }

    public function edit(DocumentoEmpleado $documento): View
    {
        return view('rh::paginas.documentos.edit', $this->datosDelFormulario($documento));
    }

    public function update(GuardarDocumentoEmpleadoRequest $peticion, DocumentoEmpleado $documento): RedirectResponse
    {
        $documento->update($peticion->validated());

        return redirect()->route('rh.documentos.index')
            ->with('success', 'Documento actualizado correctamente.');
    }

    public function destroy(DocumentoEmpleado $documento): RedirectResponse
    {
        $documento->delete();

        return redirect()->route('rh.documentos.index')
            ->with('success', 'Documento eliminado correctamente.');
    }

    /** @return array<string, mixed> */
    private function datosDelFormulario(DocumentoEmpleado $documento): array
    {
        return [
            'documento' => $documento,
            'empleados' => Empleado::activos()->orderBy('nombre')->get(),
            'tipos' => OpcionesEnum::de(TipoDocumentoEmpleado::class),
            'estados' => OpcionesEnum::de(EstadoDocumentoEmpleado::class),
        ];
    }
}
