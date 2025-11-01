<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PickBasket;
use App\Support\OrderLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
class OrderController extends Controller
{
    // Lista con filtros simples (?method=&pstatus=&status=)
    public function index(Request $request)
    {
        $q = Order::with(['user'])->latest();

        // Filtros existentes (método, pago, status)
        if ($m = $request->string('method')->toString())
            $q->where('payment_method', $m);
        if ($ps = $request->string('pstatus')->toString())
            $q->where('payment_status', $ps);
        if ($s = $request->string('status')->toString())
            $q->where('status', $s);

        // 🔎 Nuevos filtros
        if ($from = $request->date('from'))
            $q->whereDate('created_at', '>=', $from);
        if ($to = $request->date('to'))
            $q->whereDate('created_at', '<=', $to);

        if ($email = $request->string('email')->toString()) {
            $q->whereHas('user', fn($uq) => $uq->where('email', 'like', "%{$email}%"));
        }

        // Filtro por tipo: retail | wholesale
        if ($type = $request->string('type')->toString()) {
            if (in_array($type, ['retail', 'wholesale'])) {
                $q->where('order_type', $type);
            }
        }

        // Solo pedidos con picking pendiente (al menos un item con backorder_qty>0)
        if ($request->boolean('pending')) {
            $q->whereHas('items', fn($iq) => $iq->where('backorder_qty', '>', 0));
        }

        // Orden por prioridad y fecha
        $q->orderByDesc('priority_level')->orderByDesc('created_at');

        $orders = $q->paginate(20)->withQueryString();

        // Totales
        $pageTotal = $orders->sum('total');
        $filterTotal = (clone $q)->sum('total');

        $methods = ['transfer', 'cod', 'online'];
        $pstatuses = ['unpaid', 'pending_confirmation', 'cod_promised', 'authorized', 'paid', 'failed', 'partially_paid'];
        $statuses = ['new', 'confirmed', 'preparing', 'shipped', 'delivered', 'cancelled'];

        // 🔢 Banners: conteo de pendientes
        $openStatuses = ['new', 'confirmed', 'preparing'];
        $pendingRetail = Order::where('order_type', 'retail')
            ->whereIn('status', $openStatuses)
            ->whereHas('items', fn($iq) => $iq->where('backorder_qty', '>', 0))
            ->count();

        $pendingWholesale = Order::where('order_type', 'wholesale')
            ->whereIn('status', $openStatuses)
            ->whereHas('items', fn($iq) => $iq->where('backorder_qty', '>', 0))
            ->count();

        return view('admin.orders.index', compact(
            'orders',
            'methods',
            'pstatuses',
            'statuses',
            'pageTotal',
            'filterTotal',
            'pendingRetail',
            'pendingWholesale'
        ));
    }


    // Detalle
    public function show(Order $order)
    {
        // Eager loading
        $order->load([
            'user',
            'payments',
            'items' => fn($q) => $q->with(['order']),
        ]);

        // Canasta
        $basket = \App\Models\PickBasket::where('order_id', $order->id)->latest()->first();

        // Usuario actual
        $me = Auth::user();

        // Users activos
        $activeUsers = \App\Models\User::query()
            ->when(Schema::hasColumn('users', 'is_active'), fn($q) => $q->where('is_active', 1))
            ->where('id', '!=', $me?->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $hasPendingTransfer = $basket ? $basket->transfers()->where('status', 'pending')->exists() : false;

        $canTransfer = $basket
            && $basket->status === 'open'
            && (int) $basket->responsible_user_id === (int) ($me?->id ?? 0)
            && !$hasPendingTransfer;

        $readOnly = !$basket || (int) $basket->responsible_user_id !== (int) ($me?->id ?? 0);
        $meId = (int) ($me?->id ?? 0);

        $jsUsers = $activeUsers->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
        ]);

        // === NUEVO: sincroniza estado global segun pagos y usa el resumen
        $sync = $order->syncPaymentStatus(save: true);
        $sumPaid = $sync['sum_paid'];
        $sumPending = $sync['sum_pending'];
        $sumFailed = $sync['sum_failed'];
        $orderTotal = $sync['total'];
        $remaining = $sync['remaining'];
        $progressPct = $sync['progress_pct'];

        return view('admin.orders.show', compact(
            'order',
            'basket',
            'activeUsers',
            'canTransfer',
            'readOnly',
            'jsUsers',
            'meId',
            'sumPaid',
            'sumPending',
            'sumFailed',
            'orderTotal',
            'remaining',
            'progressPct'
        ));
    }




    // Cambiar estado del pedido
    public function updateStatus(Request $request, Order $order)
    {
        // 1) Validación básica: cualquier estado de la lista es válido
        $data = $request->validate([
            'status' => 'required|string|in:new,confirmed,preparing,shipped,delivered,cancelled',
            'note' => 'nullable|string|max:1000',
        ]);

        // 2) Permisos: admin o responsable mientras la canasta esté open/in_progress
        $basket = PickBasket::where('order_id', $order->id)->latest()->first();
        $isAdmin = Auth::user()?->hasAnyRole(['admin']) ?? false;
        $isResponsible = $basket
            && in_array($basket->status, ['open', 'in_progress'], true)
            && (int) $basket->responsible_user_id === (int) Auth::id();

        abort_unless($isAdmin || $isResponsible, 403, 'No autorizado para cambiar el estado del pedido.');

        $target = $data['status'];
        $current = $order->status;

        // 3) Auditoría (antes)
        $old = [
            'status' => $current,
            'confirmed_at' => $order->confirmed_at?->toDateTimeString(),
            'preparing_at' => $order->preparing_at?->toDateTimeString(),
            'shipped_at' => $order->shipped_at?->toDateTimeString(),
            'delivered_at' => $order->delivered_at?->toDateTimeString(),
            'cancelled_at' => $order->cancelled_at?->toDateTimeString(),
        ];

        DB::transaction(function () use ($order, $basket, $target) {

            // 4) Setear timestamp SOLO cuando entras por primera vez a ese estado
            //    (nunca limpiar si retrocedes)
            $now = now();
            switch ($target) {
                case 'confirmed':
                    $order->confirmed_at ??= $now;
                    break;
                case 'preparing':
                    $order->preparing_at ??= $now;
                    break;
                case 'shipped':
                    $order->shipped_at ??= $now;
                    break;
                case 'delivered':
                    $order->delivered_at ??= $now;
                    break;
                case 'cancelled':
                    $order->cancelled_at ??= $now;
                    break;
            }

            // 5) Actualizar estado
            $order->status = $target;
            $order->save();

            // 6) Si se entregó, cierra la canasta si seguía abierta/en progreso
            if ($basket && $target === 'delivered' && in_array($basket->status, ['open', 'in_progress'], true)) {
                $basket->status = 'closed';
                $basket->save();
            }
        });

        // 7) Auditoría (después)
        OrderLogger::log(
            $order,
            'update_order_status',
            $old,
            [
                'status' => $order->status,
                'confirmed_at' => $order->confirmed_at?->toDateTimeString(),
                'preparing_at' => $order->preparing_at?->toDateTimeString(),
                'shipped_at' => $order->shipped_at?->toDateTimeString(),
                'delivered_at' => $order->delivered_at?->toDateTimeString(),
                'cancelled_at' => $order->cancelled_at?->toDateTimeString(),
            ],
            [
                'route' => 'admin.orders.status',
                'ip' => $request->ip(),
                'by' => Auth::id(),
                'note' => $data['note'] ?? null,
            ]
        );

        return back()->with('success', "Estado del pedido actualizado a {$target}.");
    }



    // Cambiar estado del pago
    public function updatePaymentStatus(Request $request, Order $order)
    {
        // Autorizar: admin o vendedor
        abort_unless(Auth::user()?->hasAnyRole(['admin', 'vendedor']), 403);

        $data = $request->validate([
            'payment_status' => 'required|in:unpaid,pending_confirmation,cod_promised,authorized,paid,failed,partially_paid',
        ]);

        // Antes (old) para logging/auditoría
        $old = [
            'payment_status' => $order->payment_status,
            'paid_at' => $order->paid_at?->toDateTimeString(), // ya es Carbon|null por el cast
        ];

        // Actualizar
        $order->payment_status = $data['payment_status'];
        $order->paid_at = $data['payment_status'] === 'paid' ? now() : null;
        $order->save();

        // Después (new) para logging/auditoría
        $new = [
            'payment_status' => $order->payment_status,
            'paid_at' => $order->paid_at?->toDateTimeString(),
        ];

        // (Opcional) tu logger si lo usas
        if (class_exists(\App\Support\OrderLogger::class)) {
            \App\Support\OrderLogger::log($order, 'update_payment_status', $old, $new, [
                'route' => 'admin.orders.paystatus',
                'ip' => $request->ip(),
                'user' => Auth::id(),
            ]);
        }

        return back()->with('success', "Estado de pago actualizado a {$data['payment_status']}.");
    }
    public function bulkStatus(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
            'status' => 'required|in:new,confirmed,preparing,shipped,delivered,cancelled',
        ]);

        Order::whereIn('id', $data['ids'])->update(['status' => $data['status']]);
        return back()->with('success', 'Estados de pedido actualizados.');
    }
    public function bulkPayStatus(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
            'payment_status' => 'required|in:unpaid,pending_confirmation,cod_promised,authorized,paid,failed,partially_paid',
        ]);

        $update = ['payment_status' => $data['payment_status']];
        if ($data['payment_status'] === 'paid')
            $update['paid_at'] = now();
        else
            $update['paid_at'] = null;

        Order::whereIn('id', $data['ids'])->update($update);
        return back()->with('success', 'Estados de pago actualizados.');
    }
    public function pickItem(Request $request, Order $order, OrderItem $item)
    {
        // Valida que el ítem pertenezca al pedido
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $data = $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $qtyToPick = (int) $data['qty'];

        return DB::transaction(function () use ($order, $item, $qtyToPick) {
            $item->refresh(); // estado más reciente
            $variant = ProductVariant::where('id', $item->variant_id)->lockForUpdate()->first();

            if (!$variant) {
                return back()->with('error', 'Variante no encontrada.');
            }

            // Reglas:
            // 1) No puedes pickear más de lo pendiente en backorder
            if ($qtyToPick > $item->backorder_qty) {
                return back()->with('error', 'No puedes pickear más que el backorder pendiente.');
            }

            // 2) No puedes pickear más del stock disponible
            if ($qtyToPick > $variant->stock) {
                return back()->with('error', "Stock insuficiente. Disponible: {$variant->stock}.");
            }

            // Descuenta stock real y reduce backorder
            $variant->decrement('stock', $qtyToPick);
            $item->decrement('backorder_qty', $qtyToPick);

            // (Opcional) si quieres reflejar montos/estado en order según progreso, puedes hacerlo aquí.

            return back()->with('success', "Pick registrado por {$qtyToPick} unidades de {$item->sku}.");
        });
    }

    public function unpickItem(Request $request, Order $order, OrderItem $item)
    {
        // Valida que el ítem pertenezca al pedido
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $data = $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $qtyToUnpick = (int) $data['qty'];

        return DB::transaction(function () use ($order, $item, $qtyToUnpick) {
            $variant = ProductVariant::where('id', $item->variant_id)->lockForUpdate()->first();

            if (!$variant) {
                return back()->with('error', 'Variante no encontrada.');
            }

            // Reglas: "unpick" significa revertir un pick ya hecho (reponer stock y aumentar backorder)
            // No deberías unpickear más allá del total original pedido (qty), ni dejar backorder por encima del qty
            $maxPending = $item->qty - $item->backorder_qty; // esto es lo que ya pickeaste antes
            if ($qtyToUnpick > $maxPending) {
                return back()->with('error', 'No puedes revertir más de lo que ya está pickeado.');
            }

            // Reponer stock y aumentar backorder
            $variant->increment('stock', $qtyToUnpick);
            $item->increment('backorder_qty', $qtyToUnpick);

            return back()->with('success', "Unpick registrado por {$qtyToUnpick} unidades de {$item->sku}.");
        });
    }

    public function export(Request $request): StreamedResponse
    {
        // Reutiliza EXACTAMENTE los mismos filtros que index()
        $q = Order::with('user')->latest();
        if ($m = $request->string('method')->toString())
            $q->where('payment_method', $m);
        if ($ps = $request->string('pstatus')->toString())
            $q->where('payment_status', $ps);
        if ($s = $request->string('status')->toString())
            $q->where('status', $s);
        if ($from = $request->date('from'))
            $q->whereDate('created_at', '>=', $from);
        if ($to = $request->date('to'))
            $q->whereDate('created_at', '<=', $to);
        if ($email = $request->string('email')->toString()) {
            $q->whereHas('user', fn($uq) => $uq->where('email', 'like', "%{$email}%"));
        }
        $filename = 'orders_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            // Cabeceras
            fputcsv($out, ['order_id', 'fecha', 'cliente_nombre', 'cliente_email', 'payment_method', 'payment_status', 'status', 'subtotal', 'tax', 'total']);

            // Chunk para no explotar memoria
            $q->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $o) {
                    fputcsv($out, [
                        $o->id,
                        $o->created_at?->format('Y-m-d H:i'),
                        $o->user?->name,
                        $o->user?->email,
                        $o->payment_method,
                        $o->payment_status,
                        $o->status,
                        number_format($o->subtotal, 2, '.', ''),
                        number_format($o->tax, 2, '.', ''),
                        number_format($o->total, 2, '.', ''),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
    public function updatePriority(Request $request, Order $order)
    {
        abort_unless(Auth::user()?->hasAnyRole(['admin', 'vendedor']), 403);

        $data = $request->validate([
            'is_priority' => 'nullable|in:0,1',
            'priority_level' => 'nullable|integer|min:0|max:99',
            'action' => 'nullable|in:raise,lower,toggle',
        ]);

        if (!empty($data['action'])) {
            switch ($data['action']) {
                case 'raise':
                    $order->is_priority = true;
                    $order->priority_level = max((int) $order->priority_level, 1) + 1;
                    break;
                case 'lower':
                    $order->priority_level = max((int) $order->priority_level - 1, 0);
                    if ($order->priority_level === 0) {
                        $order->is_priority = false;
                    }
                    break;
                case 'toggle':
                    $order->is_priority = !$order->is_priority;
                    $order->priority_level = $order->is_priority
                        ? ((int) $order->priority_level === 0 ? 10 : (int) $order->priority_level)
                        : 0;
                    break;
            }
            $order->save();
            return redirect()->route('admin.orders.show', $order)->with('success', 'Prioridad actualizada.');
        }

        if (array_key_exists('is_priority', $data)) {
            $order->is_priority = (bool) ((int) $data['is_priority']);
        }
        if (array_key_exists('priority_level', $data) && $data['priority_level'] !== null) {
            $order->priority_level = (int) $data['priority_level'];
            $order->is_priority = $order->priority_level > 0;
        }

        $order->save();
        return redirect()->route('admin.orders.show', $order)->with('success', 'Prioridad guardada.');
    }


    // app/Http/Controllers/Admin/OrderController.php

    public function payStatus(Request $request, Order $order)
    {

        /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $user */
        $user = Auth::user();

        abort_unless($user && $user->hasAnyRole(['admin', 'vendedor']), 403);
        $data = $request->validate([
            'payment_status' => 'required|string|in:unpaid,pending_confirmation,cod_promised,authorized,paid,failed,partially_paid',
        ]);

        $order->payment_status = $data['payment_status'];
        $order->save();

        return back()->with('success', 'Estado de pago actualizado.');
    }

    public function priority(Request $request, Order $order)
    {
        /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $user */
        $user = Auth::user();

        abort_unless($user && $user->hasAnyRole(['admin', 'vendedor']), 403);

        // Soporta tanto editar el nivel como los botones (raise|lower|toggle)
        $action = $request->string('action')->toString();

        if ($action === 'raise') {
            $order->is_priority = 1;
            $order->priority_level = (int) $order->priority_level + 1;
        } elseif ($action === 'lower') {
            $order->is_priority = 1;
            $order->priority_level = max(0, (int) $order->priority_level - 1);
        } elseif ($action === 'toggle') {
            $order->is_priority = !$order->is_priority;
            if (!$order->is_priority) {
                $order->priority_level = 0;
            }
        } else {
            // Edición normal desde el form
            $data = $request->validate([
                'is_priority' => 'required|boolean',
                'priority_level' => 'nullable|integer|min:0|max:99',
            ]);
            $order->is_priority = (bool) $data['is_priority'];
            if (array_key_exists('priority_level', $data)) {
                $order->priority_level = (int) $data['priority_level'];
            }
        }

        $order->save();

        return back()->with('success', 'Prioridad actualizada.');
    }
}
