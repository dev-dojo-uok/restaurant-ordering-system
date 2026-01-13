import { useState, useEffect } from 'react';
import { MenuGrid } from './components/MenuGrid';
import { OrderSummary } from './components/OrderSummary';
import { VariantModal } from './components/VariantModal';
import { api } from './services/api';
import { cn } from './lib/utils';

function App() {
  const [categories, setCategories] = useState([]);
  const [menuItems, setMenuItems] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [orderItems, setOrderItems] = useState([]);
  const [loading, setLoading] = useState(true);
  
  // Variant modal state
  const [selectedItem, setSelectedItem] = useState(null);
  const [isModalOpen, setIsModalOpen] = useState(false);

  // Fetch categories and menu items
  useEffect(() => {
    const fetchData = async () => {
      try {
        const [categoriesData, itemsData] = await Promise.all([
          api.getCategories(),
          api.getMenuItems(),
        ]);
        setCategories(categoriesData);
        setMenuItems(itemsData);
      } catch (error) {
        console.error('Error fetching data:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  // Filter items by category
  const filteredItems = selectedCategory === 'all'
    ? menuItems
    : menuItems.filter(item => item.category_id === selectedCategory);

  // Handle item click - check variants and add or show modal
  const handleItemClick = (item) => {
    const hasVariants = item.variants && item.variants.length > 0;
    
    // If only one variant, add directly to order
    if (!hasVariants || item.variants.length === 1) {
      const variant = item.variants?.[0];
      const price = variant?.price || item.price;
      
      setOrderItems(prevItems => [
        ...prevItems,
        {
          id: `${item.id}-${variant?.id || 'default'}-${Date.now()}`,
          menuItemId: item.id,
          name: item.name,
          variantId: variant?.id,
          variantName: variant?.variant_name,
          price: parseFloat(price),
          quantity: 1,
        }
      ]);
    } else {
      // Multiple variants - show modal
      setSelectedItem(item);
      setIsModalOpen(true);
    }
  };

  // Add item to order from modal (when variant is selected)
  const handleAddFromModal = ({ selectedVariant, ...item }) => {
    const variant = selectedVariant || item.variants?.[0];
    const price = variant?.price || item.price;
    
    setOrderItems(prevItems => [
      ...prevItems,
      {
        id: `${item.id}-${variant?.id || 'default'}-${Date.now()}`,
        menuItemId: item.id,
        name: item.name,
        variantId: variant?.id,
        variantName: variant?.variant_name,
        price: parseFloat(price),
        quantity: 1,
      }
    ]);
    
    // Close modal after adding
    setIsModalOpen(false);
  };


  // Consolidate order items for display
  const consolidatedOrderItems = orderItems.reduce((acc, item) => {
    const key = `${item.menuItemId}-${item.variantId || 'default'}`;
    const existing = acc.find(i => `${i.menuItemId}-${i.variantId || 'default'}` === key);
    
    if (existing) {
      existing.quantity += item.quantity;
    } else {
      acc.push({ ...item });
    }
    
    return acc;
  }, []);

  // Update item quantity
  const handleUpdateQuantity = (itemId, newQuantity) => {
    if (newQuantity < 1) return;
    setOrderItems(prevItems =>
      prevItems.map(item =>
        item.id === itemId ? { ...item, quantity: newQuantity } : item
      )
    );
  };

  // Remove item from order
  const handleRemoveItem = (itemId) => {
    setOrderItems(prevItems => prevItems.filter(item => item.id !== itemId));
  };

  // Confirm payment and create order
  const handleConfirmPayment = async ({ orderType, paymentMethod, total, notes }) => {
    try {
      const orderData = {
        order_type: orderType,
        total_amount: total.toFixed(2),
        status: 'ordered',
        payment_method: paymentMethod,
        notes: notes || null,
        items: consolidatedOrderItems.map(item => ({
          menu_item_id: item.menuItemId,
          variant_id: item.variantId,
          quantity: item.quantity,
          price: item.price,
          item_name: `${item.name}${item.variantName ? ` - ${item.variantName}` : ''}`,
        })),
      };

      const result = await api.createOrder(orderData);
      
      if (result.id) {
        alert(`Order #${result.id} created successfully!`);
        setOrderItems([]); // Clear the order
      }
    } catch (error) {
      console.error('Error creating order:', error);
      alert('Failed to create order. Please try again.');
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="px-6 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">POS System</h1>
              <p className="text-sm text-gray-500">Cashier Terminal</p>
            </div>
            <div className="flex items-center gap-4">
              <span className="text-sm text-gray-600">
                {new Date().toLocaleDateString('en-US', { 
                  weekday: 'long', 
                  year: 'numeric', 
                  month: 'long', 
                  day: 'numeric' 
                })}
              </span>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="h-[calc(100vh-88px)]">
        <div className="h-full flex">
          {/* Left Side - Menu (2/3 width) */}
          <div className="flex-1 w-2/3 p-6 overflow-y-auto">
            {/* Category Filters */}
            <div className="mb-6">
              <div className="flex gap-2 flex-wrap">
                <button
                  onClick={() => setSelectedCategory('all')}
                  className={cn(
                    "px-4 py-2 rounded-lg font-medium transition-colors",
                    selectedCategory === 'all'
                      ? "bg-orange-600 text-white"
                      : "bg-white text-gray-700 hover:bg-gray-100 border border-gray-200"
                  )}
                >
                  All
                </button>
                {categories.map(category => (
                  <button
                    key={category.id}
                    onClick={() => setSelectedCategory(category.id)}
                    className={cn(
                      "px-4 py-2 rounded-lg font-medium transition-colors",
                      selectedCategory === category.id
                        ? "bg-orange-600 text-white"
                        : "bg-white text-gray-700 hover:bg-gray-100 border border-gray-200"
                    )}
                >
                  {category.name}
                </button>
              ))}
            </div>
          </div>

          {/* Menu Grid */}
          {loading ? (
            <div className="flex items-center justify-center h-64">
              <div className="text-gray-500">Loading menu items...</div>
            </div>
          ) : (
            <MenuGrid items={filteredItems} onItemClick={handleItemClick} />
          )}
        </div>

        {/* Right Side - Order Summary (1/3 width) */}
        <div className="w-1/3 bg-gray-100 border-l border-gray-200 p-6">
          <OrderSummary
            items={consolidatedOrderItems}
            onUpdateQuantity={handleUpdateQuantity}
            onRemoveItem={handleRemoveItem}
            onConfirmPayment={handleConfirmPayment}
          />
        </div>
      </div>
    </main>

    {/* Variant Selection Modal */}
    <VariantModal
      item={selectedItem}
      isOpen={isModalOpen}
      onClose={() => setIsModalOpen(false)}
      onAddToCart={handleAddFromModal}
    />
  </div>
);
}

export default App;

