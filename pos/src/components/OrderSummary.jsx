import { useState } from 'react';
import { cn } from '../lib/utils';

export function OrderSummary({ items, onUpdateQuantity, onRemoveItem, onConfirmPayment }) {
  const [orderType, setOrderType] = useState('dine_in');
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [notes, setNotes] = useState('');
  const [showNotesInput, setShowNotesInput] = useState(false);
  // const [promoCode, setPromoCode] = useState('');

  const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  // const taxRate = 0.08; // 8% tax
  const discount = 0; // You can add promo code logic here
  // const taxes = subtotal * taxRate;
  const total = subtotal - discount;

  return (
    <div className="bg-white rounded-lg shadow-lg p-6 h-full flex flex-col">
      {/* Order Type */}
      <div className="mb-4 pb-4 border-b border-gray-200">
        <label className="block text-sm font-medium text-gray-700 mb-2">Order Type</label>
        <div className="grid grid-cols-3 gap-2">
          {[
            { value: 'dine_in', label: 'Dine In' },
            { value: 'takeaway', label: 'Takeaway' },
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
                  <p className="text-sm text-gray-600">LKR {parseFloat(item.price).toFixed(2)} each</p>
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
                  LKR {(item.price * item.quantity).toFixed(2)}
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
          <span className="font-medium">LKR {subtotal.toFixed(2)}</span>
        </div>
        {/* <div className="flex justify-between text-sm">
          <span className="text-gray-600">Tax (8%)</span>
          <span className="font-medium">LKR {taxes.toFixed(2)}</span>
        </div> */}
        {discount > 0 && (
          <div className="flex justify-between text-sm text-green-600">
            <span>Discount</span>
            <span>-LKR {discount.toFixed(2)}</span>
          </div>
        )}
        <div className="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t">
          <span>Grand Total</span>
          <span className="text-orange-600">LKR {total.toFixed(2)}</span>
        </div>
      </div>

      {/* Payment Method */}
      <div className="mt-4 pt-4 border-t border-gray-200">
        <label className="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
        <div className="grid grid-cols-3 gap-2">
          {['cash', 'card', 'mobile'].map((method) => (
            <button
              key={method}
              onClick={() => setPaymentMethod(method)}
              className={cn(
                "px-3 py-2 rounded-md text-sm font-medium capitalize transition-colors",
                paymentMethod === method
                  ? "bg-orange-600 text-white"
                  : "bg-gray-100 text-gray-700 hover:bg-gray-200"
              )}
            >
              {method}
            </button>
          ))}
        </div>
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
        onClick={() => onConfirmPayment({ orderType, paymentMethod, total, notes })}
        disabled={items.length === 0}
        className={cn(
          "mt-4 w-full py-3 rounded-lg font-semibold text-white transition-colors",
          items.length === 0
            ? "bg-gray-300 cursor-not-allowed"
            : "bg-orange-600 hover:bg-orange-700"
        )}
      >
        Confirm Payment
      </button>
    </div>
  );
}
