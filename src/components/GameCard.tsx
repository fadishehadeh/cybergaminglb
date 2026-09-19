"use client";

import { Game } from "@/data/games";
import { useCart } from "./CartContext";

export default function GameCard({ game }: { game: Game }) {
  const { items, addToCart, removeFromCart } = useCart();
  const inCart = items.some((i) => i.game.id === game.id);

  const genreColors: Record<string, string> = {
    Action: "bg-red-500/15 text-red-400",
    RPG: "bg-purple-500/15 text-purple-400",
    Shooter: "bg-orange-500/15 text-orange-400",
    Horror: "bg-rose-500/15 text-rose-400",
    "Open World": "bg-sky-500/15 text-sky-400",
    Adventure: "bg-teal-500/15 text-teal-400",
    Sports: "bg-green-500/15 text-green-400",
    Fighting: "bg-yellow-500/15 text-yellow-400",
    Racing: "bg-blue-500/15 text-blue-400",
    Platformer: "bg-pink-500/15 text-pink-400",
    Strategy: "bg-indigo-500/15 text-indigo-400",
    Puzzle: "bg-violet-500/15 text-violet-400",
    Simulation: "bg-cyan-500/15 text-cyan-400",
  };

  return (
    <div className="group bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden hover:border-zinc-700 transition-all hover:shadow-lg hover:shadow-emerald-500/5">
      <div className="aspect-[4/3] bg-zinc-800 flex items-center justify-center relative overflow-hidden">
        <div className="text-center px-4">
          <p className="text-zinc-500 text-xs font-medium uppercase tracking-wider mb-1">{game.platform}</p>
          <p className="text-white font-semibold text-sm leading-tight">{game.title}</p>
        </div>
        {game.isSteelbook && (
          <div className="absolute top-2 left-2 bg-amber-500/20 text-amber-400 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded">
            Steelbook
          </div>
        )}
        {!game.inStock && (
          <div className="absolute inset-0 bg-black/60 flex items-center justify-center">
            <span className="text-zinc-400 font-medium text-sm">Sold Out</span>
          </div>
        )}
      </div>
      <div className="p-4">
        <div className="flex items-start justify-between gap-2 mb-2">
          <h3 className="text-sm font-semibold text-white leading-tight line-clamp-2">{game.title}</h3>
          <span className="text-emerald-400 font-bold text-lg shrink-0">${game.price}</span>
        </div>
        <div className="flex flex-wrap gap-1 mb-3">
          {game.genre.slice(0, 2).map((g) => (
            <span key={g} className={`text-[10px] font-medium px-1.5 py-0.5 rounded ${genreColors[g] || "bg-zinc-800 text-zinc-400"}`}>
              {g}
            </span>
          ))}
          {game.edition !== "Standard" && (
            <span className="text-[10px] font-medium px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-400">
              {game.edition}
            </span>
          )}
        </div>
        <div className="flex items-center justify-between">
          <span className="text-[11px] text-zinc-500">{game.condition} &middot; {game.year}</span>
          {game.inStock && (
            <button
              onClick={() => (inCart ? removeFromCart(game.id) : addToCart(game))}
              className={`text-xs font-medium px-3 py-1.5 rounded-lg transition-colors ${
                inCart
                  ? "bg-zinc-800 text-zinc-400 hover:bg-zinc-700"
                  : "bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25"
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
