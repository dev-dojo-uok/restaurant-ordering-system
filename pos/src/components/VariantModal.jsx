import * as Dialog from '@radix-ui/react-dialog';
import { cn } from '../lib/utils';

export function VariantModal({ item, isOpen, onClose, onAddToCart }) {
  if (!item) return null;

  const handleVariantClick = (variant) => {
    onAddToCart({
      ...item,
      selectedVariant: variant,
    });
  };

  return (
    <Dialog.Root open={isOpen} onOpenChange={onClose}>
      <Dialog.Portal>
        <Dialog.Overlay className="fixed inset-0 bg-black/50 z-50" />
        <Dialog.Content className="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-2xl shadow-2xl z-50 w-full max-w-md max-h-[90vh] overflow-y-auto">
          <div className="p-6">
            {/* Close Button */}
            <Dialog.Close className="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </Dialog.Close>

            {/* Item Image */}
            {item.image_url && (
              <div className="w-full h-48 rounded-xl overflow-hidden mb-4">
                <img
                  src={item.image_url}
                  alt={item.name}
                  className="w-full h-full object-cover"
                />
              </div>
            )}

            {/* Item Info */}
            <Dialog.Title className="text-2xl font-bold text-gray-900 mb-2">
              {item.name}
            </Dialog.Title>
            
            {item.description && (
              <Dialog.Description className="text-gray-600 mb-4">
                {item.description}
              </Dialog.Description>
            )}

            {/* Variants Selection - Click to add */}
            <div className="mb-4">
              <label className="block text-sm font-semibold text-gray-700 mb-3">
                Select Size / Variant
              </label>
              <div className="space-y-2">
                {item.variants?.map((variant) => (
                  <button
                    key={variant.id}
                    onClick={() => handleVariantClick(variant)}
                    className="w-full flex items-center justify-between p-4 rounded-lg border-2 border-gray-200 hover:border-orange-600 hover:bg-orange-50 transition-all"
                  >
                    <span className="font-medium text-gray-900">
                      {variant.variant_name}
                    </span>
                    <span className="font-bold text-orange-600">
                      LKR {parseFloat(variant.price).toFixed(2)}
                    </span>
                  </button>
                ))}
              </div>
            </div>

            <p className="text-sm text-gray-500 text-center">Click on a variant to add to order</p>
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}
