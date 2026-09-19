"use client";

import { Suspense, useState, useMemo } from "react";
import { useSearchParams } from "next/navigation";
import { games, GameGenre } from "@/data/games";
import GameCard from "@/components/GameCard";

const allGenres: GameGenre[] = [
  "Action", "Adventure", "RPG", "Shooter", "Horror", "Open World",
  "Sports", "Fighting", "Racing", "Platformer", "Strategy", "Puzzle", "Simulation",
];

export default function GamesPage() {
  return (
    <Suspense fallback={<div className="max-w-7xl mx-auto px-4 sm:px-6 py-8"><p className="text-slate-500">Loading games...</p></div>}>
      <GamesContent />
    </Suspense>
  );
}

function GamesContent() {
  const searchParams = useSearchParams();
  const initialFilter = searchParams.get("filter");

  const [search, setSearch] = useState("");
  const [selectedGenre, setSelectedGenre] = useState<string>("All");
  const [editionFilter, setEditionFilter] = useState<string>(
    initialFilter === "steelbook" ? "Steelbook" : "All"
  );
  const [sortBy, setSortBy] = useState<string>("title");

  const filtered = useMemo(() => {
    let result = games.filter((g) => g.inStock);

    if (search) {
      const q = search.toLowerCase();
      result = result.filter((g) => g.title.toLowerCase().includes(q));
    }

    if (selectedGenre !== "All") {
      result = result.filter((g) => g.genre.includes(selectedGenre as GameGenre));
    }

    if (editionFilter === "Steelbook") {
      result = result.filter((g) => g.isSteelbook);
    } else if (editionFilter === "Standard") {
      result = result.filter((g) => !g.isSteelbook);
    }

    if (sortBy === "price-low") result.sort((a, b) => a.price - b.price);
    else if (sortBy === "price-high") result.sort((a, b) => b.price - a.price);
    else if (sortBy === "year") result.sort((a, b) => b.year - a.year);
    else result.sort((a, b) => a.title.localeCompare(b.title));

    return result;
  }, [search, selectedGenre, editionFilter, sortBy]);

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold mb-2">Browse Games</h1>
        <p className="text-slate-600">{filtered.length} games available</p>
      </div>

      <div className="flex flex-col sm:flex-row gap-3 mb-6">
        <input
          type="text"
          placeholder="Search games..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="flex-1 bg-white border border-slate-200 rounded-lg px-4 py-2.5 text-sm text-navy placeholder:text-slate-400 focus:outline-none focus:border-brand"
        />
        <select
          value={selectedGenre}
          onChange={(e) => setSelectedGenre(e.target.value)}
          className="bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:border-brand"
        >
          <option value="All">All Genres</option>
          {allGenres.map((g) => (
            <option key={g} value={g}>{g}</option>
          ))}
        </select>
        <select
          value={editionFilter}
          onChange={(e) => setEditionFilter(e.target.value)}
          className="bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:border-brand"
        >
          <option value="All">All Editions</option>
          <option value="Standard">Standard Only</option>
          <option value="Steelbook">Steelbook Only</option>
        </select>
        <select
          value={sortBy}
          onChange={(e) => setSortBy(e.target.value)}
          className="bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:border-brand"
        >
          <option value="title">Sort: A-Z</option>
          <option value="price-low">Sort: Price Low</option>
          <option value="price-high">Sort: Price High</option>
          <option value="year">Sort: Newest</option>
        </select>
      </div>

      {filtered.length === 0 ? (
        <div className="text-center py-20">
          <p className="text-slate-500 text-lg">No games found matching your filters.</p>
          <button
            onClick={() => { setSearch(""); setSelectedGenre("All"); setEditionFilter("All"); }}
            className="mt-4 text-brand-dark text-sm hover:text-navy"
          >
            Clear filters
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {filtered.map((game) => (
            <GameCard key={game.id} game={game} />
          ))}
        </div>
      )}
    </div>
  );
}
