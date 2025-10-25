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
            'is_default'   => 'sometimes|boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated['user_id'] = $user->id;
        if (!$user->addresses()->exists() || $request->boolean('is_default')) {
            $validated['is_default'] = true;
        }

        $address = \App\Models\Address::create($validated);

        // ✅ Si el cliente pide JSON (AJAX), devolvemos JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok'      => true,
                'address' => [
                    'id'       => $address->id,
                    'label'    => "{$address->district} — {$address->line1} ({$address->contact_name})",
                    'district' => $address->district,
                    'default'  => (bool) $address->is_default,
                ],
            ]);
        }

        // Fallback: navegación normal
        return redirect()->back()->with('success', 'Dirección guardada correctamente.');
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
