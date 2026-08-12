<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Contacto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactoController extends Controller
{
    public function index(Request $request): View
    {
        $contactos = Contacto::query()
            ->with(['empresa'])
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('crm::paginas.contactos.index', compact('contactos'));
    }

    public function show(Contacto $contacto): View
    {
        $contacto->load('empresa');

        return view('crm::paginas.contactos.show', compact('contacto'));
    }
}