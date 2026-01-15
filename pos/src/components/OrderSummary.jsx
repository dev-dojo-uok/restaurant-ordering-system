import { useState } from 'react';
import { cn } from '../lib/utils';

export function OrderSummary({ items, onUpdateQuantity, onRemoveItem, onConfirmPayment, isSubmitting }) {
  const [orderType, setOrderType] = useState('takeaway');
  const [tableNumber, setTableNumber] = useState('');
  const [notes, setNotes] = useState('');
  const [showNotesInput, setShowNotesInput] = useState(false);
  
  // Split payment state
  const [payments, setPayments] = useState([]);
  const [currentPaymentMethod, setCurrentPaymentMethod] = useState('cash');
  const [currentPaymentAmount, setCurrentPaymentAmount] = useState('');
  const [showPaymentInput, setShowPaymentInput] = useState(false);

  const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const discount = 0;
  const total = subtotal - discount;
  
  // Calculate total paid and remaining
  const totalPaid = payments.reduce((sum, p) => sum + parseFloat(p.amount), 0);
  const remaining = total - totalPaid;
  
  // Add payment
  const handleAddPayment = () => {
    const amount = parseFloat(currentPaymentAmount);
    if (!amount || amount <= 0) {
      alert('Please enter a valid amount');
      return;
    }
    
    if (amount > remaining) {
      alert(`Amount cannot exceed remaining balance: Rs ${remaining.toFixed(2)}`);
      return;
    }
    
    setPayments([...payments, { method: currentPaymentMethod, amount }]);
    setCurrentPaymentAmount('');
    
    // Auto-close if fully paid
    if (Math.abs(amount - remaining) < 0.01) {
      setShowPaymentInput(false);
    }
  };
  
  // Remove payment
  const handleRemovePayment = (index) => {
    setPayments(payments.filter((_, i) => i !== index));
  };
  
  // Quick pay full amount with single method
  const handleQuickPay = (method) => {
    setPayments([{ method, amount: total }]);
    setShowPaymentInput(false);
  };

  // Reset form to default state
  const resetForm = () => {
    setOrderType('takeaway');
    setTableNumber('');
    setNotes('');
    setShowNotesInput(false);
    setPayments([]);
    setCurrentPaymentMethod('cash');
    setCurrentPaymentAmount('');
    setShowPaymentInput(false);
  };

  return (
    <div className="bg-white rounded-lg shadow-lg p-6 h-full flex flex-col">
      {/* Order Type */}
      <div className="mb-4 pb-4 border-b border-gray-200">
        <label className="block text-sm font-medium text-gray-700 mb-2">Order Type</label>
        <div className="grid grid-cols-3 gap-2">
          {[
            { value: 'takeaway', label: 'Takeaway' },
            { value: 'dine_in', label: 'Dine In' },
            { value: 'delivery', label: 'Delivery' },
          ].map((type) => (
            <button
              key={type.value}
              onClick={() => setOrderType(type.value)}
              className={cn(
                "px-3 py-2 rounded-md text-sm font-medium transition-colors",
                orderType === type.value
                  ? "bg-orange-600 text-white"
                  : "bg-gray-100 text-gray-700 hover:bg-gray-200"
              )}
            >
              {type.label}
            </button>
          ))}
        </div>
      </div>

      {/* Table Number for Dine In */}
      {orderType === 'dine_in' && (
        <div className="mb-4 pb-4 border-b border-gray-200">
          <label className="block text-sm font-medium text-gray-700 mb-2">Table Number (Optional)</label>
          <input
            type="text"
            value={tableNumber}
            onChange={(e) => setTableNumber(e.target.value)}
            placeholder="Enter table number"
            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
          />
        </div>
      )}

      {/* Header */}
      <div className="mb-4">
        <h2 className="text-xl font-bold text-gray-900">Order Summary</h2>
        <p className="text-sm text-gray-500 mt-1">
          {items.length} {items.length === 1 ? 'item' : 'items'}
        </p>
      </div>

      {/* Items List */}
      <div className="flex-1 overflow-y-auto mb-4 space-y-3">
        {items.length === 0 ? (
          <div className="text-center py-12">
            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <p className="text-gray-500 mt-2">No items in order</p>
          </div>
        ) : (
          items.map((item) => (
            <div key={item.id} className="bg-gray-50 rounded-lg p-3">
              <div className="flex items-start justify-between mb-2">
                <div className="flex-1">
                  <h4 className="font-semibold text-gray-900">{item.name}</h4>
                  {item.variantName && (
                    <p className="text-xs text-orange-600 font-medium">{item.variantName}</p>
                  )}
                  <p className="text-sm text-gray-600">Rs {parseFloat(item.price).toFixed(2)} each</p>
                </div>
                <button
                  onClick={() => onRemoveItem(item.id)}
                  className="text-red-500 hover:text-red-700 p-1"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              {/* Quantity Controls */}
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => onUpdateQuantity(item.id, Math.max(1, item.quantity - 1))}
                    className="w-8 h-8 rounded-md bg-white border border-gray-300 hover:bg-gray-100 flex items-center justify-center"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" />
                    </svg>
                  </button>
                  <span className="w-8 text-center font-semibold">{item.quantity}</span>
                  <button
                    onClick={() => onUpdateQuantity(item.id, item.quantity + 1)}
                    className="w-8 h-8 rounded-md bg-white border border-gray-300 hover:bg-gray-100 flex items-center justify-center"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                </div>
                <span className="font-bold text-gray-900">
                  Rs {(item.price * item.quantity).toFixed(2)}
                </span>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Totals */}
      <div className="border-t border-gray-200 pt-4 space-y-2">
        <div className="flex justify-between text-sm">
          <span className="text-gray-600">Subtotal</span>
          <span className="font-medium">Rs {subtotal.toFixed(2)}</span>
        </div>
        {/* <div className="flex justify-between text-sm">
          <span className="text-gray-600">Tax (8%)</span>
          <span className="font-medium">Rs {taxes.toFixed(2)}</span>
        </div> */}
        {discount > 0 && (
          <div className="flex justify-between text-sm text-green-600">
            <span>Discount</span>
            <span>-Rs {discount.toFixed(2)}</span>
          </div>
        )}
        <div className="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t">
          <span>Grand Total</span>
          <span className="text-orange-600">Rs {total.toFixed(2)}</span>
        </div>
      </div>

      {/* Payment Method */}
      <div className="mt-4 pt-4 border-t border-gray-200">
        <div className="flex items-center justify-between mb-3">
          <label className="block text-sm font-medium text-gray-700">Payment</label>
          {payments.length === 0 && (
            <span className="text-xs text-gray-500">Quick Pay:</span>
          )}
        </div>
        
        {/* Quick Payment Buttons (show when no payments added) */}
        {payments.length === 0 && (
          <div className="grid grid-cols-2 gap-2 mb-3">
            {['cash', 'card'].map((method) => (
              <button
                key={method}
                onClick={() => handleQuickPay(method)}
                disabled={items.length === 0}
                className={cn(
                  "px-3 py-2 rounded-md text-sm font-medium capitalize transition-colors",
                  items.length === 0
                    ? "bg-gray-100 text-gray-400 cursor-not-allowed"
                    : "bg-orange-600 text-white hover:bg-orange-700"
                )}
              >
                {method}
              </button>
            ))}
          </div>
        )}
        
        {/* Split Payment Section */}
        {payments.length > 0 && (
          <div className="space-y-2 mb-3">
            {payments.map((payment, index) => (
              <div key={index} className="flex items-center justify-between bg-gray-50 px-3 py-2 rounded-md">
                <div className="flex items-center gap-2">
                  <span className="text-sm font-medium capitalize">{payment.method}</span>
                  <span className="text-xs text-gray-500">Rs {parseFloat(payment.amount).toFixed(2)}</span>
                </div>
                <button
                  onClick={() => handleRemovePayment(index)}
                  className="text-red-500 hover:text-red-700"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            ))}
            
            {/* Payment Summary */}
            <div className="bg-blue-50 px-3 py-2 rounded-md space-y-1">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Total Paid:</span>
                <span className="font-medium text-green-600">Rs {totalPaid.toFixed(2)}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Remaining:</span>
                <span className={cn(
                  "font-medium",
                  remaining > 0 ? "text-orange-600" : "text-green-600"
                )}>
                  Rs {remaining.toFixed(2)}
                </span>
              </div>
            </div>
          </div>
        )}
        
        {/* Add Another Payment Button */}
        {items.length > 0 && remaining > 0.01 && !showPaymentInput && (
          <button
            onClick={() => setShowPaymentInput(true)}
            className="w-full px-3 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 flex items-center justify-center gap-2"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            {payments.length === 0 ? 'Split Payment' : 'Add Payment'}
          </button>
        )}
        
        {/* Payment Input Form */}
        {showPaymentInput && remaining > 0.01 && (
          <div className="bg-gray-50 p-3 rounded-md space-y-2">
            <div className="grid grid-cols-2 gap-2">
              {['cash', 'card'].map((method) => (
                <button
                  key={method}
                  onClick={() => setCurrentPaymentMethod(method)}
                  className={cn(
                    "px-2 py-1.5 rounded-md text-xs font-medium capitalize transition-colors",
                    currentPaymentMethod === method
                      ? "bg-orange-600 text-white"
                      : "bg-white text-gray-700 hover:bg-gray-100 border border-gray-300"
                  )}
                >
                  {method}
                </button>
              ))}
            </div>
            
            <div className="flex gap-2">
              <input
                type="number"
                step="0.01"
                placeholder={`Amount (max: ${remaining.toFixed(2)})`}
                value={currentPaymentAmount}
                onChange={(e) => setCurrentPaymentAmount(e.target.value)}
                className="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
              />
              <button
                onClick={() => setCurrentPaymentAmount(remaining.toFixed(2))}
                className="px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium hover:bg-gray-100"
              >
                Full
              </button>
            </div>
            
            <div className="flex gap-2">
              <button
                onClick={handleAddPayment}
                className="flex-1 px-3 py-2 bg-orange-600 text-white rounded-md text-sm font-medium hover:bg-orange-700"
              >
                Add
              </button>
              <button
                onClick={() => {
                  setShowPaymentInput(false);
                  setCurrentPaymentAmount('');
                }}
                className="px-3 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300"
              >
                Cancel
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Order Notes */}
      <div className="mt-3">
        {!showNotesInput ? (
          <button
            onClick={() => setShowNotesInput(true)}
            className="w-full px-3 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 flex items-center justify-center gap-2"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Add Note
          </button>
        ) : (
          <div className="space-y-2">
            <label className="block text-sm font-medium text-gray-700">Order Notes</label>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Special instructions, allergies, etc..."
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none"
            />
            <button
              onClick={() => setShowNotesInput(false)}
              className="text-xs text-gray-500 hover:text-gray-700"
            >
              Hide
            </button>
          </div>
        )}
      </div>

      {/* Promo Code */}
      {/* <div className="mt-3 flex gap-2">
        <input
          type="text"
          placeholder="Promo Code"
          value={promoCode}
          onChange={(e) => setPromoCode(e.target.value)}
          className="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
        />
        <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">
          Apply
        </button>
      </div> */}

      {/* Confirm Payment Button */}
      <button
        onClick={() => {
          if (payments.length === 0) {
            alert('Please add at least one payment');
            return;
          }
          if (Math.abs(remaining) > 0.01) {
            alert(`Remaining balance: Rs ${remaining.toFixed(2)}. Please complete payment.`);
            return;
          }
          onConfirmPayment({ orderType, payments, total, notes, tableNumber, resetForm });
        }}
        disabled={items.length === 0 || payments.length === 0 || Math.abs(remaining) > 0.01 || isSubmitting}
        className={cn(
          "mt-4 w-full py-3 rounded-lg font-semibold text-white transition-colors flex items-center justify-center gap-2",
          items.length === 0 || payments.length === 0 || Math.abs(remaining) > 0.01 || isSubmitting
            ? "bg-gray-300 cursor-not-allowed"
            : "bg-orange-600 hover:bg-orange-700"
        )}
      >
        {isSubmitting ? (
          <>
            <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
          </>
        ) : (
          Math.abs(remaining) > 0.01 ? `Pay Rs ${remaining.toFixed(2)} More` : 'Confirm Payment'
        )}
      </button>
    </div>
  );
}
