"use client";

import { useState } from "react";

export default function SellPage() {
  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    type: "sell",
    games: "",
    message: "",
  });
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const typeLabel = formData.type === "sell" ? "SELL" : "TRADE";
    const text = encodeURIComponent(
      `[${typeLabel}] from ${formData.name}\n\nGames:\n${formData.games}\n\n${formData.message ? `Note: ${formData.message}` : ""}`
    );
    window.open(`https://wa.me/961?text=${text}`, "_blank");
    setSubmitted(true);
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8">
      <div className="max-w-2xl">
        <h1 className="text-3xl font-bold mb-2">Sell or Trade Your Games</h1>
        <p className="text-slate-600 mb-8">
          Got PS4 games collecting dust? Sell them for cash or trade them for something new from our collection.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <div className="bg-white border border-slate-200 rounded-xl p-6">
              <div className="text-2xl mb-3">&#x1f4b0;</div>
              <h3 className="font-semibold mb-1">Sell for Cash</h3>
              <p className="text-sm text-slate-600">Get paid in USD for your used PS4 games. Fair prices based on condition and demand.</p>
            </div>
            <div className="bg-white border border-slate-200 rounded-xl p-6">
              <div className="text-2xl mb-3">&#x1f504;</div>
              <h3 className="font-semibold mb-1">Trade In</h3>
              <p className="text-sm text-slate-600">Swap your games for others in our collection. Trade value is typically higher than cash.</p>
            </div>
          </div>

          <div className="bg-white border border-slate-200 rounded-xl p-6">
            <h3 className="font-semibold mb-4">How It Works</h3>
            <div className="space-y-4">
              <div className="flex gap-3">
                <span className="shrink-0 w-7 h-7 rounded-full bg-brand/15 text-brand-dark text-sm font-bold flex items-center justify-center">1</span>
                <div>
                  <p className="font-medium text-sm">List Your Games</p>
                  <p className="text-sm text-slate-500">Tell us what games you have and their condition.</p>
                </div>
              </div>
              <div className="flex gap-3">
                <span className="shrink-0 w-7 h-7 rounded-full bg-brand/15 text-brand-dark text-sm font-bold flex items-center justify-center">2</span>
                <div>
                  <p className="font-medium text-sm">Get a Quote</p>
                  <p className="text-sm text-slate-500">We&apos;ll send you a fair offer via WhatsApp within 24 hours.</p>
                </div>
              </div>
              <div className="flex gap-3">
                <span className="shrink-0 w-7 h-7 rounded-full bg-brand/15 text-brand-dark text-sm font-bold flex items-center justify-center">3</span>
                <div>
                  <p className="font-medium text-sm">Meet &amp; Deal</p>
                  <p className="text-sm text-slate-500">Meet up in person, we inspect the games, and you get paid or pick your trades.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-white border border-slate-200 rounded-xl p-6">
          {submitted ? (
            <div className="text-center py-12">
              <div className="text-4xl mb-4">&#x2705;</div>
              <h3 className="text-xl font-bold mb-2">Message Sent!</h3>
              <p className="text-slate-600 mb-4">We&apos;ll get back to you on WhatsApp shortly.</p>
              <button onClick={() => setSubmitted(false)} className="text-brand-dark text-sm hover:text-navy">
                Submit another
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmit}>
              <h3 className="font-semibold mb-4">Submit Your Games</h3>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm text-slate-600 mb-1">Your Name</label>
                  <input
                    type="text"
                    required
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full bg-slate-100 border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-navy focus:outline-none focus:border-brand"
                  />
                </div>
                <div>
                  <label className="block text-sm text-slate-600 mb-1">Phone / WhatsApp</label>
                  <input
                    type="tel"
                    required
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    className="w-full bg-slate-100 border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-navy focus:outline-none focus:border-brand"
                  />
                </div>
                <div>
                  <label className="block text-sm text-slate-600 mb-1">I want to...</label>
                  <div className="flex gap-2">
                    <button
                      type="button"
                      onClick={() => setFormData({ ...formData, type: "sell" })}
                      className={`flex-1 py-2 rounded-lg text-sm font-medium transition-colors ${
                        formData.type === "sell"
                          ? "bg-brand/15 text-brand-dark border border-brand/40"
                          : "bg-slate-100 text-slate-600 border border-slate-300"
                      }`}
                    >
                      Sell for Cash
                    </button>
                    <button
                      type="button"
                      onClick={() => setFormData({ ...formData, type: "trade" })}
                      className={`flex-1 py-2 rounded-lg text-sm font-medium transition-colors ${
                        formData.type === "trade"
                          ? "bg-brand/15 text-brand-dark border border-brand/40"
                          : "bg-slate-100 text-slate-600 border border-slate-300"
                      }`}
                    >
                      Trade / Swap
                    </button>
                  </div>
                </div>
                <div>
                  <label className="block text-sm text-slate-600 mb-1">
                    Games you want to {formData.type === "sell" ? "sell" : "trade"} (one per line)
                  </label>
                  <textarea
                    required
                    rows={5}
                    placeholder={"e.g.\nGod of War - Like New\nSpider-Man - Good condition\nFIFA 23 - Fair"}
                    value={formData.games}
                    onChange={(e) => setFormData({ ...formData, games: e.target.value })}
                    className="w-full bg-slate-100 border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-navy placeholder:text-slate-400 focus:outline-none focus:border-brand resize-none"
                  />
                </div>
                <div>
                  <label className="block text-sm text-slate-600 mb-1">Additional notes (optional)</label>
                  <input
                    type="text"
                    value={formData.message}
                    onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                    placeholder="Any games you're looking for in trade, preferred meetup area, etc."
                    className="w-full bg-slate-100 border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-navy placeholder:text-slate-400 focus:outline-none focus:border-brand"
                  />
                </div>
                <button
                  type="submit"
                  className="w-full bg-brand text-navy font-semibold py-3 rounded-lg hover:bg-brand-dark transition-colors"
                >
                  Send via WhatsApp
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
