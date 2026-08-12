<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Tarea;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TareaController extends Controller
{
    public function index(Request $request): View
    {
        $tareas = Tarea::query()
            ->with('asignadaA')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('crm::paginas.tareas.index', compact('tareas'));
    }

    public function show(Tarea $tarea): View
    {
        $tarea->load('asignadaA');

        return view('crm::paginas.tareas.show', compact('tarea'));
    }
}