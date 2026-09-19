"use client";

import Link from "next/link";
import { games } from "@/data/games";
import GameCard from "@/components/GameCard";

export default function Home() {
  const featured = games.filter((g) => g.isSteelbook).slice(0, 4);
  const latest = games.filter((g) => !g.isSteelbook).sort((a, b) => b.year - a.year).slice(0, 8);
  const totalGames = games.filter((g) => g.inStock).length;

  return (
    <div>
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-b from-brand/10 via-transparent to-transparent" />
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-20 sm:py-32 relative">
          <div className="max-w-2xl">
            <p className="text-brand-dark text-sm font-semibold tracking-wider uppercase mb-4">
              For Gamers, By Gamers
            </p>
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-tight mb-6">
              Buy, Sell &amp; Trade<br />
              <span className="text-brand-dark">PS4 Games</span> in Lebanon
            </h1>
            <p className="text-lg text-slate-600 leading-relaxed mb-8 max-w-lg">
              {totalGames}+ used PS4 games in stock. Steelbook editions, standard copies, and the best prices in Lebanon. Trade your old games or sell them to us.
            </p>
            <div className="flex flex-wrap gap-3">
              <Link
                href="/games"
                className="bg-brand text-navy font-semibold px-6 py-3 rounded-lg hover:bg-brand-dark transition-colors"
              >
                Browse Games
              </Link>
              <Link
                href="/sell"
                className="border border-slate-300 text-slate-700 font-semibold px-6 py-3 rounded-lg hover:border-slate-400 hover:text-navy transition-colors"
              >
                Sell Your Games
              </Link>
            </div>
          </div>
          <div className="mt-12 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div className="bg-white border border-slate-200 rounded-xl p-4 text-center">
              <p className="text-2xl font-bold text-brand-dark">{totalGames}+</p>
              <p className="text-xs text-slate-500 mt-1">Games in Stock</p>
            </div>
            <div className="bg-white border border-slate-200 rounded-xl p-4 text-center">
              <p className="text-2xl font-bold text-navy">18</p>
              <p className="text-xs text-slate-500 mt-1">Steelbook Editions</p>
            </div>
            <div className="bg-white border border-slate-200 rounded-xl p-4 text-center">
              <p className="text-2xl font-bold text-navy">$5</p>
              <p className="text-xs text-slate-500 mt-1">Starting From</p>
            </div>
            <div className="bg-white border border-slate-200 rounded-xl p-4 text-center">
              <p className="text-2xl font-bold text-navy">USD</p>
              <p className="text-xs text-slate-500 mt-1">All Prices in $</p>
            </div>
          </div>
        </div>
      </section>

      <section className="max-w-7xl mx-auto px-4 sm:px-6 py-12">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold">Steelbook Editions</h2>
          <Link href="/games?filter=steelbook" className="text-sm text-brand-dark hover:text-navy">
            View All &rarr;
          </Link>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {featured.map((game) => (
            <GameCard key={game.id} game={game} />
          ))}
        </div>
      </section>

      <section className="max-w-7xl mx-auto px-4 sm:px-6 py-12">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold">Latest Additions</h2>
          <Link href="/games" className="text-sm text-brand-dark hover:text-navy">
            View All &rarr;
          </Link>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {latest.map((game) => (
            <GameCard key={game.id} game={game} />
          ))}
        </div>
      </section>

      <section className="max-w-7xl mx-auto px-4 sm:px-6 py-12">
        <div className="bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 text-center">
          <h2 className="text-2xl sm:text-3xl font-bold mb-4">Got Games to Sell?</h2>
          <p className="text-slate-600 max-w-md mx-auto mb-6">
            We buy used PS4 games and offer trade-ins. Get cash or store credit for your old collection.
          </p>
          <Link
            href="/sell"
            className="inline-block bg-brand text-navy font-semibold px-6 py-3 rounded-lg hover:bg-brand-dark transition-colors"
          >
            Sell or Trade Your Games
          </Link>
        </div>
      </section>
    </div>
  );
}
