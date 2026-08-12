<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function index(Request $request): View
    {
        $empresas = Empresa::query()
            ->when($request->filled('buscar'), function ($consulta) use ($request): void {
                $termino = $request->string('buscar')->toString();
                $consulta->where(function ($filtro) use ($termino): void {
                    $filtro->where('nombre', 'like', '%'.$termino.'%')
                        ->orWhere('rfc', 'like', '%'.$termino.'%')
                        ->orWhere('correo', 'like', '%'.$termino.'%');
                });
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('crm::catalogos.empresas.index', compact('empresas'));
    }

    public function show(Empresa $empresa): View
    {
        $empresa->load('contactos');

        return view('crm::catalogos.empresas.show', compact('empresa'));
    }
}