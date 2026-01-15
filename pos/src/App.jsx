import { useState, useEffect } from 'react';
import toast, { Toaster } from 'react-hot-toast';
import {Expand, Minimize, ShoppingCart, ClipboardList} from "lucide-react"
import { MenuGrid } from './components/MenuGrid';
import { OrderSummary } from './components/OrderSummary';
import { VariantModal } from './components/VariantModal';
import { OrdersView } from './components/OrdersView';
import { Login } from './components/Login';
import { api } from './services/api';
import { cn } from './lib/utils';

function App() {
  const [user, setUser] = useState(null);
  const [isCheckingAuth, setIsCheckingAuth] = useState(true);
  const [categories, setCategories] = useState([]);
  const [menuItems, setMenuItems] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [orderItems, setOrderItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [activeView, setActiveView] = useState('pos'); // 'pos' or 'orders'
  
  // Variant modal state
  const [selectedItem, setSelectedItem] = useState(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  
  // User dropdown state
  const [showUserDropdown, setShowUserDropdown] = useState(false);
  const [isFullscreen, setIsFullscreen] = useState(false);

  // Fullscreen toggle handler
  const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen();
      setIsFullscreen(true);
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
        setIsFullscreen(false);
      }
    }
  };

  // Listen for fullscreen changes (e.g., user presses ESC)
  useEffect(() => {
    const handleFullscreenChange = () => {
      setIsFullscreen(!!document.fullscreenElement);
    };
    
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    return () => document.removeEventListener('fullscreenchange', handleFullscreenChange);
  }, []);

  // Check if user is already logged in
  useEffect(() => {
    const storedUser = localStorage.getItem('pos_user');
    if (storedUser) {
      try {
        const userData = JSON.parse(storedUser);
        // Only allow cashier and admin
        if (userData.role === 'cashier' || userData.role === 'admin') {
          setUser(userData);
        } else {
          localStorage.removeItem('pos_user');
        }
      } catch (error) {
        localStorage.removeItem('pos_user');
      }
    }
    setIsCheckingAuth(false);
  }, []);

  const handleLoginSuccess = (userData) => {
    // Only allow cashier and admin to access POS
    if (userData.role === 'cashier' || userData.role === 'admin') {
      setUser(userData);
    } else {
      toast.error('Access denied. POS is only for cashiers and admins.');
      localStorage.removeItem('pos_user');
    }
  };

  const handleLogout = async () => {
    try {
      await api.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('pos_user');
      setUser(null);
      setOrderItems([]);
    }
  };

  // Fetch categories and menu items when authenticated
  useEffect(() => {
    if (!user) return;

    const fetchData = async () => {
      setLoading(true);
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
  }, [user]);

  // Show loading while checking auth
  if (isCheckingAuth) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-gray-500">Loading...</div>
      </div>
    );
  }

  // Show login if not authenticated
  if (!user) {
    return <Login onLoginSuccess={handleLoginSuccess} />;
  }

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
  const handleConfirmPayment = async ({ orderType, payments, total, notes, tableNumber, resetForm }) => {
    setIsSubmitting(true);
    try {
      const orderData = {
        order_type: orderType,
        total_amount: total.toFixed(2),
        status: 'ordered',
        payments: payments,
        notes: notes || null,
        table_number: tableNumber || null,
        items: consolidatedOrderItems.map(item => ({
          menu_item_id: item.menuItemId,
          variant_id: item.variantId,
          quantity: item.quantity,
          price: item.price,
          item_name: `${item.name}${item.variantName ? ` - ${item.variantName}` : ''}`,
          variant_name: item.variantName || null,
        })),
      };

      const result = await api.createOrder(orderData);
      
      if (result.id) {
        // Show detailed payment summary
        const paymentSummary = payments.map(p => 
          `${p.method.toUpperCase()}: Rs ${parseFloat(p.amount).toFixed(2)}`
        ).join(', ');
        
        toast.success(
          <div>
            <p className="font-semibold">Order #{result.id} created successfully!</p>
            <p className="text-sm mt-1">{paymentSummary}</p>
            <p className="text-sm font-semibold mt-1">Total: Rs {total.toFixed(2)}</p>
          </div>,
          { duration: 5000 }
        );
        
        // Clear the order and reset form
        setOrderItems([]);
        resetForm();
      }
    } catch (error) {
      console.error('Error creating order:', error);
      toast.error('Failed to create order. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Toaster 
        position="top-right"
        toastOptions={{
          success: {
            duration: 5000,
            style: {
              background: '#10b981',
              color: '#fff',
            },
          },
          error: {
            duration: 4000,
            style: {
              background: '#ef4444',
              color: '#fff',
            },
          },
        }}
      />
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="px-6 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Flavour POS</h1>
              <p className="text-sm text-gray-500">
                {activeView === 'pos' ? 'Cashier Terminal' : 'Order Management'}
              </p>
            </div>
            <span className="text-sm text-gray-600">
                {new Date().toLocaleDateString('en-US', { 
                  weekday: 'long', 
                  year: 'numeric', 
                  month: 'long', 
                  day: 'numeric' 
                })}
              </span>
            <div className="flex items-center gap-4">
              <div className="relative">
                <button
                  onClick={() => setShowUserDropdown(!showUserDropdown)}
                  className="flex items-center gap-3 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl shadow-sm hover:bg-gray-100 hover:border-gray-300 transition-all"
                >
                  <div className="w-9 h-9 rounded-full bg-orange-100 text-orange-700 font-semibold flex items-center justify-center uppercase">
                    {user.full_name?.[0] ?? 'U'}
                  </div>
                  <div className="text-right leading-tight">
                    <p className="text-sm font-semibold text-gray-900">{user.full_name}</p>
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide bg-orange-600 text-white">
                      <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 11c1.657 0 3-1.567 3-3.5S13.657 4 12 4s-3 1.567-3 3.5S10.343 11 12 11z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 20v-1.5A4.5 4.5 0 0110.5 14h3A4.5 4.5 0 0118 18.5V20" />
                      </svg>
                      {user.role}
                    </span>
                  </div>
                  <svg className={cn("w-4 h-4 text-gray-500 transition-transform", showUserDropdown && "rotate-180")} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                
                {/* Dropdown Menu */}
                {showUserDropdown && (
                  <>
                    <div 
                      className="fixed inset-0 z-10" 
                      onClick={() => setShowUserDropdown(false)}
                    ></div>
                    <div className="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20">
                      <div className="px-4 py-3 border-b border-gray-100">
                        <p className="text-sm font-semibold text-gray-900">{user.full_name}</p>
                        <p className="text-xs text-gray-500">{user.email}</p>
                      </div>
                      <button
                        onClick={handleLogout}
                        className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                      >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                      </button>
                    </div>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="h-[calc(100vh-88px)]">
        <div className="h-full flex">
          {/* Left Sidebar */}
          <div className="w-20 bg-gray-800 flex flex-col items-center py-6 gap-4">
            {/* POS View Button */}
            <button
              onClick={() => setActiveView('pos')}
              className={cn(
                "w-14 h-14 rounded-xl flex flex-col items-center justify-center gap-1 transition-all",
                activeView === 'pos'
                  ? "bg-orange-600 text-white shadow-lg"
                  : "text-gray-400 hover:bg-gray-700 hover:text-white"
              )}
              title="POS Terminal"
            >
              <ShoppingCart className="w-6 h-6" />
              <span className="text-[10px] font-medium">POS</span>
            </button>

            {/* Orders View Button */}
            <button
              onClick={() => setActiveView('orders')}
              className={cn(
                "w-14 h-14 rounded-xl flex flex-col items-center justify-center gap-1 transition-all",
                activeView === 'orders'
                  ? "bg-orange-600 text-white shadow-lg"
                  : "text-gray-400 hover:bg-gray-700 hover:text-white"
              )}
              title="View Orders"
            >
              <ClipboardList className="w-6 h-6" />
              <span className="text-[10px] font-medium">Orders</span>
            </button>

            {/* Spacer */}
            <div className="flex-1"></div>

            {/* Fullscreen Button */}
            <button
              onClick={toggleFullscreen}
              className={cn(
                "w-14 h-14 rounded-xl flex flex-col items-center justify-center gap-1 transition-all text-gray-400 hover:bg-gray-700 hover:text-white"
              )}
              title={isFullscreen ? "Exit Fullscreen" : "Enter Fullscreen"}
            >
              {isFullscreen ? (
                <>
                  <Minimize className="w-6 h-6" />
                  <span className="text-[10px] font-medium">Exit</span>
                </>
              ) : (
                <>
                  <Expand className="w-6 h-6" />
                  <span className="text-[10px] font-medium">Full</span>
                </>
              )}
            </button>
          </div>

          {/* Main Content Area */}
          {activeView === 'pos' ? (
            <>
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
            isSubmitting={isSubmitting}
          />
        </div>
      </>
    ) : (
      /* Orders View */
      <div className="flex-1">
        <OrdersView />
      </div>
    )}
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

