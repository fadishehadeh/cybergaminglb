"use client";

import { Game } from "@/data/games";
import { useCart } from "./CartContext";

export default function GameCard({ game }: { game: Game }) {
  const { items, addToCart, removeFromCart } = useCart();
  const inCart = items.some((i) => i.game.id === game.id);

  const genreColors: Record<string, string> = {
    Action: "bg-red-500/15 text-red-700",
    RPG: "bg-purple-500/15 text-purple-700",
    Shooter: "bg-orange-500/15 text-orange-700",
    Horror: "bg-rose-500/15 text-rose-700",
    "Open World": "bg-sky-500/15 text-sky-700",
    Adventure: "bg-teal-500/15 text-teal-700",
    Sports: "bg-green-500/15 text-green-700",
    Fighting: "bg-yellow-500/15 text-yellow-700",
    Racing: "bg-blue-500/15 text-blue-700",
    Platformer: "bg-pink-500/15 text-pink-700",
    Strategy: "bg-indigo-500/15 text-indigo-700",
    Puzzle: "bg-violet-500/15 text-violet-700",
    Simulation: "bg-cyan-500/15 text-cyan-700",
  };

  return (
    <div className="group bg-white border border-slate-200 rounded-xl overflow-hidden hover:border-slate-300 transition-all hover:shadow-lg hover:shadow-brand/20">
      <div className="aspect-[3/4] bg-slate-100 relative overflow-hidden">
        {game.image ? (
          <img
            src={game.image}
            alt={game.title}
            className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="absolute inset-0 flex items-center justify-center text-center px-4">
            <div>
              <p className="text-slate-500 text-xs font-medium uppercase tracking-wider mb-1">{game.platform}</p>
              <p className="text-navy font-semibold text-sm leading-tight">{game.title}</p>
            </div>
          </div>
        )}
        {game.isSteelbook && (
          <div className="absolute top-2 left-2 bg-amber-500/20 text-amber-700 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded">
            Steelbook
          </div>
        )}
        {!game.inStock && (
          <div className="absolute inset-0 bg-white/70 flex items-center justify-center">
            <span className="text-slate-600 font-medium text-sm">Sold Out</span>
          </div>
        )}
      </div>
      <div className="p-4">
        <div className="flex items-start justify-between gap-2 mb-2">
          <h3 className="text-sm font-semibold text-navy leading-tight line-clamp-2">{game.title}</h3>
          <span className="text-brand-dark font-bold text-lg shrink-0">${game.price}</span>
        </div>
        <div className="flex flex-wrap gap-1 mb-3">
          {game.genre.slice(0, 2).map((g) => (
            <span key={g} className={`text-[10px] font-medium px-1.5 py-0.5 rounded ${genreColors[g] || "bg-slate-100 text-slate-600"}`}>
              {g}
            </span>
          ))}
          {game.edition !== "Standard" && (
            <span className="text-[10px] font-medium px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-700">
              {game.edition}
            </span>
          )}
        </div>
        <div className="flex items-center justify-between">
          <span className="text-[11px] text-slate-500">{game.condition} &middot; {game.year}</span>
          {game.inStock && (
            <button
              onClick={() => (inCart ? removeFromCart(game.id) : addToCart(game))}
              className={`text-xs font-medium px-3 py-1.5 rounded-lg transition-colors ${
                inCart
                  ? "bg-slate-100 text-slate-600 hover:bg-slate-200"
                  : "bg-brand/15 text-brand-dark hover:bg-brand/25"
              }`}
            >
              {inCart ? "Remove" : "Add to Cart"}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
