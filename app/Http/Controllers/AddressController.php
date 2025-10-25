<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\User;                 // 👈 pista de tipo para el IDE
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 👈 si quieres usar Auth::user()

class AddressController extends Controller
{
    // Lista de direcciones
    public function index(Request $request)
    {
        /** @var User $user */       // 👈 le decimos al IDE qué tipo es
        $user = $request->user();    // también vale: Auth::user()

        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->get();

        return view('addresses.index', compact('addresses'));
    }

    // Crear dirección
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'nullable|string|max:100',
            'contact_name' => 'required|string|max:100',
            'phone'        => 'required|string|max:20',
            'region'       => 'nullable|string|max:100',
            'province'     => 'nullable|string|max:100',
            'district'     => 'required|string|max:100',
            'line1'        => 'required|string|max:255',
            'reference'    => 'nullable|string|max:255',
        ]);

        /** @var User $user */
        $user = $request->user();

        $validated['user_id'] = $user->id;  // 👈 ya no marca “id” indefinido
        // si no tiene ninguna, esta queda como predeterminada
        if (!$user->addresses()->exists()) {
            $validated['is_default'] = true;
        }

        Address::create($validated);

        return back()->with('success', 'Dirección guardada correctamente.');
    }

    // Eliminar dirección (solo del usuario dueño)
    public function destroy(Request $request, Address $address)
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($address->user_id === $user->id, 403);

        $address->delete();

        return back()->with('success', 'Dirección eliminada.');
    }
}
