<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::with('empleado')
            ->when($request->buscar, fn ($q, $buscar) => $q->where(fn ($w) => $w->where('name', 'like', "%$buscar%")->orWhere('email', 'like', "%$buscar%")))
            ->latest()->paginate(10)->withQueryString();
        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.create', ['usuario' => new User(), 'empleados' => Empleado::orderBy('nombre')->get()]);
    }

    public function store(Request $request)
    {
        User::create($this->validated($request));
        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        return view('usuarios.edit', ['usuario' => $usuario, 'empleados' => Empleado::orderBy('nombre')->get()]);
    }

    public function update(Request $request, User $usuario)
    {
        $data = $this->validated($request, $usuario->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $usuario->update($data);
        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        $usuario->update(['estado' => 'inactivo']);
        return redirect()->route('usuarios.index')->with('success', 'Usuario desactivado correctamente.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'empleado_id' => ['nullable', 'exists:empleados,id'], 'name' => ['required', 'max:160'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password' => [$id ? 'nullable' : 'required', 'min:8'],
            'role' => ['required', 'in:Administrador,Recursos Humanos,Contador,Empleado'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);
    }
}
