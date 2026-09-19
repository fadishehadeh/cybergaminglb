"use client";

import Link from "next/link";
import { useCart } from "@/components/CartContext";

export default function CartPage() {
  const { items, removeFromCart, clearCart, totalPrice } = useCart();

  const handleCheckout = () => {
    const gameList = items.map((i) => `- ${i.game.title} (${i.game.edition}) - $${i.game.price}`).join("\n");
    const text = encodeURIComponent(
      `Hi! I'd like to order these games from CyberGaming LB:\n\n${gameList}\n\nTotal: $${totalPrice}\n\nPlease let me know availability and meetup details!`
    );
    window.open(`https://wa.me/961?text=${text}`, "_blank");
  };

  if (items.length === 0) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-20 text-center">
        <div className="text-5xl mb-4">&#x1f6d2;</div>
        <h1 className="text-2xl font-bold mb-2">Your cart is empty</h1>
        <p className="text-slate-600 mb-6">Browse our collection and add some games!</p>
        <Link
          href="/games"
          className="inline-block bg-brand text-navy font-semibold px-6 py-3 rounded-lg hover:bg-brand-dark transition-colors"
        >
          Browse Games
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-3xl font-bold">Your Cart</h1>
        <button onClick={clearCart} className="text-sm text-slate-500 hover:text-navy">
          Clear all
        </button>
      </div>

      <div className="space-y-3 mb-8">
        {items.map((item) => (
          <div
            key={item.game.id}
            className="bg-white border border-slate-200 rounded-xl p-4 flex items-center justify-between gap-4"
          >
            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-2 mb-1">
                <h3 className="font-semibold text-sm truncate">{item.game.title}</h3>
                {item.game.isSteelbook && (
                  <span className="shrink-0 text-[10px] font-bold uppercase text-amber-700 bg-amber-500/15 px-1.5 py-0.5 rounded">
                    Steelbook
                  </span>
                )}
              </div>
              <p className="text-xs text-slate-500">
                {item.game.platform} &middot; {item.game.edition} &middot; {item.game.condition}
              </p>
            </div>
            <div className="flex items-center gap-4">
              <span className="text-brand-dark font-bold">${item.game.price}</span>
              <button
                onClick={() => removeFromCart(item.game.id)}
                className="text-slate-400 hover:text-red-600 transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        ))}
      </div>

      <div className="bg-white border border-slate-200 rounded-xl p-6">
        <div className="flex items-center justify-between mb-4">
          <span className="text-slate-600">Subtotal ({items.length} games)</span>
          <span className="text-xl font-bold">${totalPrice}</span>
        </div>
        <p className="text-xs text-slate-500 mb-4">
          Orders are confirmed via WhatsApp. Meet in person to inspect games and pay in USD cash.
        </p>
        <button
          onClick={handleCheckout}
          className="w-full bg-brand text-navy font-semibold py-3 rounded-lg hover:bg-brand-dark transition-colors flex items-center justify-center gap-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
          </svg>
          Order via WhatsApp &mdash; ${totalPrice}
        </button>
      </div>
    </div>
  );
}
