const API_BASE_URL = 'http://localhost:8080/api';

export const api = {
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
