<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Warehouse;
use App\Models\PickBasket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PickBasketController extends Controller
{
    // 1) Listar canastas (por defecto abiertas)
    public function index(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'open'; // open | in_progress | closed | cancelled

        $baskets = PickBasket::with(['order', 'warehouse', 'responsibleUser'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return view('admin.pick_baskets.index', compact('baskets', 'status'));
    }

    // 2) Formulario de creación (seleccionar pedido y almacén)
    public function create(Request $request)
    {
        // Puedes filtrar pedidos por estado "confirmed/preparing" si quieres
        $orders = Order::latest()->take(50)->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        // Si viene ?order_id en la URL, lo preseleccionamos
        $orderId = $request->integer('order_id') ?: null;

        return view('admin.pick_baskets.create', compact('orders', 'warehouses', 'orderId'));
    }

    // 3) Guardar canasta
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id'     => 'nullable|integer|exists:orders,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
        ]);

        $basket = PickBasket::create([
            'order_id'          => $data['order_id'] ?? null,
            'warehouse_id'      => $data['warehouse_id'],
            'status'            => 'open', // open de inicio
            'created_by_user_id' => Auth::id(),
            // 'responsible_user_id' => null (se asignará luego)
        ]);

        return redirect()
            ->route('admin.pick_baskets.show', $basket)
            ->with('success', 'Canasta creada correctamente.');
    }

    // 4) Ver detalle de la canasta
    public function show(PickBasket $basket)
    {
        $basket->load(['order.items', 'warehouse', 'responsibleUser', 'createdByUser']);

        return view('admin.pick_baskets.show', compact('basket'));
    }
}
