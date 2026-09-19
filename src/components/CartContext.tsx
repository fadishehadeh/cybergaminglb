"use client";

import { createContext, useContext, useState, useEffect, ReactNode } from "react";
import { Game } from "@/data/games";

interface CartItem {
  game: Game;
  quantity: number;
}

interface CartContextType {
  items: CartItem[];
  addToCart: (game: Game) => void;
  removeFromCart: (gameId: string) => void;
  clearCart: () => void;
  totalItems: number;
  totalPrice: number;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export function CartProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<CartItem[]>([]);

  useEffect(() => {
    try {
      const saved = localStorage.getItem("cg-cart");
      if (saved) setItems(JSON.parse(saved));
    } catch {}
  }, []);

  useEffect(() => {
    try {
      localStorage.setItem("cg-cart", JSON.stringify(items));
    } catch {}
  }, [items]);

  const addToCart = (game: Game) => {
    setItems((prev) => {
      const existing = prev.find((i) => i.game.id === game.id);
      if (existing) return prev;
      return [...prev, { game, quantity: 1 }];
    });
  };

  const removeFromCart = (gameId: string) => {
    setItems((prev) => prev.filter((i) => i.game.id !== gameId));
  };

  const clearCart = () => setItems([]);

  const totalItems = items.length;
  const totalPrice = items.reduce((sum, i) => sum + i.game.price * i.quantity, 0);

  return (
    <CartContext.Provider value={{ items, addToCart, removeFromCart, clearCart, totalItems, totalPrice }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const context = useContext(CartContext);
  if (!context) throw new Error("useCart must be used within CartProvider");
  return context;
}
