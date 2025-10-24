<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;

class PricingService
{
    /**
     * Retorna el precio a mostrar para un producto (y opcionalmente variante)
     */
    public function priceFor(?User $user, Product $product, ?int $variantId = null): float
    {
        $priceList = optional(optional($user)->group)->activePriceList;

        if ($priceList) {
            if ($variantId) {
                $item = $priceList->items()
                    ->where('variant_id', $variantId)
                    ->first();
                if ($item) return (float) $item->price;
            }

            $item = $priceList->items()
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->first();
            if ($item) return (float) $item->price;
        }

        return (float) $product->price_base;
    }
    public function priceForWithSource(?User $user, Product $product, ?int $variantId = null): array
    {
        $priceList = optional(optional($user)->group)->activePriceList;

        if ($priceList) {
            if ($variantId) {
                $item = $priceList->items()->where('variant_id', $variantId)->first();
                if ($item) return [(float)$item->price, 'variant'];
            }

            $item = $priceList->items()
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->first();
            if ($item) return [(float)$item->price, 'product'];

            $discount = (float) $priceList->discount_percent;
            if ($discount > 0) {
                $price = (float) $product->price_base * (1 - ($discount / 100));
                return [round($price, 2), 'discount'];
            }
        }

        return [(float) $product->price_base, 'base'];
    }
}
