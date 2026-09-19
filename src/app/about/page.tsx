"use client";

import Link from "next/link";

export default function AboutPage() {
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8">
      <div className="max-w-2xl mb-12">
        <h1 className="text-3xl font-bold mb-2">About CyberGaming LB</h1>
        <p className="text-zinc-400">For gamers, by gamers. Based in Lebanon.</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <div className="space-y-6">
          <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
            <h2 className="text-xl font-bold mb-3">Who We Are</h2>
            <p className="text-zinc-400 leading-relaxed">
              CyberGaming LB is Lebanon&apos;s go-to spot for used PS4 games. We&apos;re a local,
              gamer-run operation that believes everyone should have access to great games at fair prices.
              Whether you&apos;re looking to buy, sell, or trade &mdash; we&apos;ve got you covered.
            </p>
          </div>

          <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
            <h2 className="text-xl font-bold mb-3">What We Offer</h2>
            <ul className="space-y-3">
              <li className="flex gap-3">
                <span className="shrink-0 w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-bold flex items-center justify-center mt-0.5">&#x2713;</span>
                <div>
                  <p className="font-medium text-sm">100+ PS4 Games</p>
                  <p className="text-sm text-zinc-500">Standard editions, steelbooks, deluxe &mdash; all tested and working.</p>
                </div>
              </li>
              <li className="flex gap-3">
                <span className="shrink-0 w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-bold flex items-center justify-center mt-0.5">&#x2713;</span>
                <div>
                  <p className="font-medium text-sm">Steelbook Collection</p>
                  <p className="text-sm text-zinc-500">18 rare steelbook editions including collector&apos;s items like Dark Souls III Apocalypse Edition.</p>
                </div>
              </li>
              <li className="flex gap-3">
                <span className="shrink-0 w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-bold flex items-center justify-center mt-0.5">&#x2713;</span>
                <div>
                  <p className="font-medium text-sm">Buy, Sell &amp; Trade</p>
                  <p className="text-sm text-zinc-500">Sell your old games for cash or swap them for something new.</p>
                </div>
              </li>
              <li className="flex gap-3">
                <span className="shrink-0 w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-bold flex items-center justify-center mt-0.5">&#x2713;</span>
                <div>
                  <p className="font-medium text-sm">Fair USD Pricing</p>
                  <p className="text-sm text-zinc-500">All prices in US dollars. No surprises, no hidden fees.</p>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <div className="space-y-6">
          <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
            <h2 className="text-xl font-bold mb-3">How It Works</h2>
            <div className="space-y-4">
              <div className="flex gap-3">
                <span className="shrink-0 w-7 h-7 rounded-full bg-emerald-500/15 text-emerald-400 text-sm font-bold flex items-center justify-center">1</span>
                <div>
                  <p className="font-medium text-sm">Browse &amp; Pick</p>
                  <p className="text-sm text-zinc-500">Check out our collection online. Filter by genre, price, or edition.</p>
                </div>
              </div>
              <div className="flex gap-3">
                <span className="shrink-0 w-7 h-7 rounded-full bg-emerald-500/15 text-emerald-400 text-sm font-bold flex items-center justify-center">2</span>
                <div>
                  <p className="font-medium text-sm">Order via WhatsApp</p>
                  <p className="text-sm text-zinc-500">Add games to your cart and place your order through WhatsApp. Quick and easy.</p>
                </div>
              </div>
              <div className="flex gap-3">
                <span className="shrink-0 w-7 h-7 rounded-full bg-emerald-500/15 text-emerald-400 text-sm font-bold flex items-center justify-center">3</span>
                <div>
                  <p className="font-medium text-sm">Meet &amp; Inspect</p>
                  <p className="text-sm text-zinc-500">We meet in person so you can inspect the games before paying in USD cash.</p>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
            <h2 className="text-xl font-bold mb-3">Connect With Us</h2>
            <div className="space-y-3">
              <a
                href="https://www.instagram.com/cybergaminglb/"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 p-3 rounded-lg bg-zinc-800/50 hover:bg-zinc-800 transition-colors"
              >
                <svg className="w-5 h-5 text-pink-400" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                </svg>
                <div>
                  <p className="font-medium text-sm">@cybergaminglb</p>
                  <p className="text-xs text-zinc-500">Follow us on Instagram</p>
                </div>
              </a>
              <a
                href="https://wa.me/961"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 p-3 rounded-lg bg-zinc-800/50 hover:bg-zinc-800 transition-colors"
              >
                <svg className="w-5 h-5 text-emerald-400" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                </svg>
                <div>
                  <p className="font-medium text-sm">WhatsApp</p>
                  <p className="text-xs text-zinc-500">Message us directly</p>
                </div>
              </a>
            </div>
          </div>

          <div className="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-6 text-center">
            <p className="text-emerald-400 font-semibold mb-2">Ready to browse?</p>
            <Link
              href="/games"
              className="inline-block bg-emerald-500 text-black font-semibold px-6 py-2.5 rounded-lg hover:bg-emerald-400 transition-colors text-sm"
            >
              View All Games
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
