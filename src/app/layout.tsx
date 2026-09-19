import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";
import { CartProvider } from "@/components/CartContext";
import Navbar from "@/components/Navbar";

const inter = Inter({ subsets: ["latin"] });

export const metadata: Metadata = {
  title: "CyberGaming LB | Buy, Sell & Trade PS4 Games in Lebanon",
  description: "Your local gaming store in Lebanon. Buy used PS4 games, sell your collection, or trade with other gamers.",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" className="dark">
      <body className={`${inter.className} bg-zinc-950 text-white antialiased`}>
        <CartProvider>
          <Navbar />
          <main className="pt-16">{children}</main>
          <footer className="border-t border-zinc-800 mt-20">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-10">
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-8">
                <div>
                  <p className="font-bold text-emerald-400 mb-2">CYBER<span className="text-white">GAMING</span> <span className="text-zinc-500 text-sm">LB</span></p>
                  <p className="text-sm text-zinc-500 leading-relaxed">For gamers, by gamers. Your go-to store for used PS4 games in Lebanon.</p>
                </div>
                <div>
                  <p className="font-semibold text-sm text-zinc-300 mb-2">Quick Links</p>
                  <div className="flex flex-col gap-1">
                    <a href="/games" className="text-sm text-zinc-500 hover:text-zinc-300">Browse Games</a>
                    <a href="/sell" className="text-sm text-zinc-500 hover:text-zinc-300">Sell / Trade</a>
                    <a href="/about" className="text-sm text-zinc-500 hover:text-zinc-300">About Us</a>
                  </div>
                </div>
                <div>
                  <p className="font-semibold text-sm text-zinc-300 mb-2">Connect</p>
                  <div className="flex flex-col gap-1">
                    <a href="https://www.instagram.com/cybergaminglb/" target="_blank" rel="noopener noreferrer" className="text-sm text-zinc-500 hover:text-zinc-300">Instagram</a>
                    <a href="https://wa.me/961" className="text-sm text-zinc-500 hover:text-zinc-300">WhatsApp</a>
                  </div>
                </div>
              </div>
              <div className="mt-8 pt-6 border-t border-zinc-800/50 text-center">
                <p className="text-xs text-zinc-600">&copy; 2026 CyberGaming LB. All rights reserved.</p>
              </div>
            </div>
          </footer>
        </CartProvider>
      </body>
    </html>
  );
}
