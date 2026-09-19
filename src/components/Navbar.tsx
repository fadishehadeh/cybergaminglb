"use client";

import Link from "next/link";
import { useCart } from "./CartContext";
import { useState } from "react";

export default function Navbar() {
  const { totalItems } = useCart();
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <nav className="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6">
        <div className="flex items-center justify-between h-16">
          <Link href="/" className="flex items-center" aria-label="CyberGaming Lebanon home">
            <img src="/logo-wide.png" alt="CyberGaming Lebanon" className="h-11 w-auto" />
          </Link>

          <div className="hidden md:flex items-center gap-6">
            <Link href="/games" className="text-sm text-slate-600 hover:text-navy transition-colors">
              Games
            </Link>
            <Link href="/sell" className="text-sm text-slate-600 hover:text-navy transition-colors">
              Sell / Trade
            </Link>
            <Link href="/about" className="text-sm text-slate-600 hover:text-navy transition-colors">
              About
            </Link>
            <Link
              href="/cart"
              className="relative flex items-center gap-1.5 text-sm bg-brand/10 text-brand-dark px-3 py-1.5 rounded-lg hover:bg-brand/20 transition-colors"
            >
              <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
              </svg>
              Cart
              {totalItems > 0 && (
                <span className="absolute -top-1.5 -right-1.5 bg-brand text-navy text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">
                  {totalItems}
                </span>
              )}
            </Link>
          </div>

          <button onClick={() => setMenuOpen(!menuOpen)} className="md:hidden text-slate-600 hover:text-navy">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              {menuOpen ? (
                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
              ) : (
                <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              )}
            </svg>
          </button>
        </div>

        {menuOpen && (
          <div className="md:hidden pb-4 flex flex-col gap-2">
            <Link href="/games" onClick={() => setMenuOpen(false)} className="text-sm text-slate-600 hover:text-navy py-2">
              Games
            </Link>
            <Link href="/sell" onClick={() => setMenuOpen(false)} className="text-sm text-slate-600 hover:text-navy py-2">
              Sell / Trade
            </Link>
            <Link href="/about" onClick={() => setMenuOpen(false)} className="text-sm text-slate-600 hover:text-navy py-2">
              About
            </Link>
            <Link href="/cart" onClick={() => setMenuOpen(false)} className="text-sm text-brand-dark py-2 flex items-center gap-2">
              Cart {totalItems > 0 && `(${totalItems})`}
            </Link>
          </div>
        )}
      </div>
    </nav>
  );
}
