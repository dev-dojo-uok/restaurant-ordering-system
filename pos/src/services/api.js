const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

export const api = {
  // Auth
  login: async (username, password) => {
    const res = await fetch(`${API_BASE_URL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ username, password }),
    });
    return res.json();
  },

  logout: async () => {
    const res = await fetch(`${API_BASE_URL}/auth/logout`, {
      method: 'POST',
      credentials: 'include',
    });
    return res.json();
  },

  // Menu Categories
  getCategories: async () => {
    const res = await fetch(`${API_BASE_URL}/menu-categories`);
    return res.json();
  },

  // Menu Items
  getMenuItems: async (categoryId = null) => {
    const url = categoryId 
      ? `${API_BASE_URL}/menu-items?category_id=${categoryId}`
      : `${API_BASE_URL}/menu-items`;
    const res = await fetch(url);
    return res.json();
  },

  // Orders
  createOrder: async (orderData) => {
    const res = await fetch(`${API_BASE_URL}/orders`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(orderData),
    });
    return res.json();
  },

  getOrders: async () => {
    const res = await fetch(`${API_BASE_URL}/orders`);
    return res.json();
  },
};
