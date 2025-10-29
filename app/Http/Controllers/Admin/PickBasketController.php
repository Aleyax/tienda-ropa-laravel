<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PickBasket;
use App\Models\PickBasketTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PickBasketController extends Controller
{
    // Crear / abrir canasta para el pedido y asignarme como responsable (si no hay)
    public function open(Request $request, Order $order)
    {
        $basket = PickBasket::firstOrCreate(
            ['order_id' => $order->id],
            [
                'status'              => 'open',
                'created_by_user_id'  => Auth::id(),
                'responsible_user_id' => Auth::id(),
            ]
        );

        // Si existe pero sin responsable, tomar responsabilidad
        if (!$basket->responsible_user_id) {
            $basket->responsible_user_id = Auth::id();
            $basket->save();
        }

        return back()->with('success', 'Canasta abierta/asignada.');
    }

    // Pick: incrementar picked_qty de un OrderItem (sin almacenes aún)
    public function pick(Request $request, PickBasket $basket)
    {
        $data = $request->validate([
            'order_item_id' => 'required|integer|exists:order_items,id',
            'qty'           => 'required|integer|min:1',
        ]);

        // Seguridad: la canasta debe corresponder al pedido del item
        $item = OrderItem::with('order')->findOrFail($data['order_item_id']);
        if ((int)$item->order_id !== (int)$basket->order_id) {
            return back()->with('error', 'El ítem no pertenece a esta canasta.');
        }

        // Lógica: picked_qty no puede exceder qty total del item
        $toPick = (int) $data['qty'];
        $maxPickable = max(0, $item->qty - $item->picked_qty);
        if ($maxPickable <= 0) {
            return back()->with('error', 'Nada por pickear en este ítem.');
        }

        $final = min($toPick, $maxPickable);

        DB::transaction(function () use ($item, $final, $basket) {
            $item->picked_qty = $item->picked_qty + $final;
            $item->save();

            // Marcar canasta en progreso si estaba open
            if ($basket->status === 'open') {
                $basket->status = 'in_progress';
                $basket->save();
            }
        });

        return back()->with('success', "Se movieron {$final} a la canasta.");
    }

    // Unpick: devolver desde picked hacia "no pickeado" (sin almacenes aún)
    public function unpick(Request $request, PickBasket $basket)
    {
        $data = $request->validate([
            'order_item_id' => 'required|integer|exists:order_items,id',
            'qty'           => 'required|integer|min:1',
        ]);

        $item = OrderItem::with('order')->findOrFail($data['order_item_id']);
        if ((int)$item->order_id !== (int)$basket->order_id) {
            return back()->with('error', 'El ítem no pertenece a esta canasta.');
        }

        $toUnpick = (int) $data['qty'];
        $maxUnpickable = max(0, $item->picked_qty);
        if ($maxUnpickable <= 0) {
            return back()->with('error', 'No hay cantidades pickeadas para devolver.');
        }

        $final = min($toUnpick, $maxUnpickable);

        DB::transaction(function () use ($item, $final) {
            $item->picked_qty = $item->picked_qty - $final;
            $item->save();
        });

        return back()->with('success', "Se devolvieron {$final} a no pickeado.");
    }

    // Cerrar canasta
    public function close(Request $request, PickBasket $basket)
    {
        if (!in_array($basket->status, ['open', 'in_progress'])) {
            return back()->with('error', 'La canasta no está abierta.');
        }
        $basket->status = 'closed';
        $basket->save();

        return back()->with('success', 'Canasta cerrada.');
    }


    public function transferAccept(Request $request, PickBasketTransfer $transfer)
    {
        if ((int)$transfer->to_user_id !== (int)Auth::id()) {
            return back()->with('error', 'No puedes aceptar una transferencia que no es para ti.');
        }
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'La transferencia no está pendiente.');
        }

        DB::transaction(function () use ($transfer) {
            // Tomar responsabilidad
            $basket = PickBasket::findOrFail($transfer->pick_basket_id);
            $basket->responsible_user_id = $transfer->to_user_id;
            $basket->save();

            $transfer->status = 'accepted';
            $transfer->decided_at = now();
            $transfer->save();
        });

        return back()->with('success', 'Transferencia aceptada. Ahora eres responsable de la canasta.');
    }

    public function transferDecline(Request $request, PickBasketTransfer $transfer)
    {
        if ((int)$transfer->to_user_id !== (int)Auth::id()) {
            return back()->with('error', 'No puedes declinar una transferencia que no es para ti.');
        }
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'La transferencia no está pendiente.');
        }

        $transfer->status = 'declined';
        $transfer->decided_at = now();
        $transfer->save();

        return back()->with('success', 'Transferencia declinada.');
    }

    public function transferCancel(Request $request, PickBasketTransfer $transfer)
    {
        // El originador puede cancelar si sigue pendiente
        if ((int)$transfer->from_user_id !== (int)Auth::id()) {
            return back()->with('error', 'Solo quien inició la transferencia puede cancelarla.');
        }
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'La transferencia no está pendiente.');
        }

        $transfer->status = 'cancelled';
        $transfer->decided_at = now();
        $transfer->save();

        return back()->with('success', 'Transferencia cancelada.');
    }


    // NUEVO: búsqueda de usuarios activos por q (id|nombre|email)
    public function userLookup(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        // Importante: define claramente tu campo de "activo".
        // Si tu tabla users tiene 'is_active' (boolean), úsalo:
        $users = \App\Models\User::query()
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_active'), function ($qq) {
                $qq->where('is_active', true);
            }, function ($qq) {
                // Si no tienes is_active, ajusta aquí (por ejemplo, status='active')
                // $qq->where('status', 'active');
            })
            ->when(is_numeric($q), function ($qq) use ($q) {
                $qq->where('id', (int)$q);
            }, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        // No permitir sugerirme a mí mismo
    $me = (int) Auth::id();
        $users = $users->reject(fn($u) => (int)$u->id === $me)->values();

        return response()->json($users);
    }

    // AJUSTE: transferCreate — bloquear transferirse a sí mismo y usuarios inactivos
    public function transferCreate(Request $request, \App\Models\PickBasket $basket)
    {
        $data = $request->validate([
            'to_user_id' => 'required|integer|exists:users,id',
            'note'       => 'nullable|string|max:1000',
        ]);

        // Solo responsable puede transferir
        if ((int)$basket->responsible_user_id !== (int) Auth::id()) {
            return back()->with('error', 'Solo el responsable puede transferir la canasta.');
        }

        // No permitirse a sí mismo
        if ((int)$data['to_user_id'] === (int) Auth::id()) {
            return back()->with('error', 'No puedes derivarte la canasta a ti mismo.');
        }

        // Validar que el destinatario esté activo
        $to = \App\Models\User::query()
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_active'), function ($qq) {
                $qq->where('is_active', true);
            }, function ($qq) {
                // Si no tienes is_active, ajusta aquí (p. ej. status='active')
                // $qq->where('status','active');
            })
            ->find($data['to_user_id']);

        if (!$to) {
            return back()->with('error', 'El usuario destino no está disponible (inactivo o no existe).');
        }

        // Permitir solo una transferencia pendiente
        $hasPending = \App\Models\PickBasketTransfer::where('pick_basket_id', $basket->id)
            ->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return back()->with('error', 'Ya existe una transferencia pendiente para esta canasta.');
        }

        \App\Models\PickBasketTransfer::create([
            'pick_basket_id' => $basket->id,
            'from_user_id'   => Auth::id(),
            'to_user_id'     => (int)$data['to_user_id'],
            'status'         => 'pending',
            'note'           => $data['note'] ?? null,
        ]);

        return back()->with('success', 'Transferencia creada. Pendiente de aceptación.');
    }
}
