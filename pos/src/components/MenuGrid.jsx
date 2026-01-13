import { cn } from '../lib/utils';

export function MenuGrid({ items, onItemClick }) {
  // Helper to get display price
  const getDisplayPrice = (item) => {
    if (!item.variants || item.variants.length === 0) {
      return parseFloat(item.price).toFixed(2);
    }
    
    if (item.variants.length === 1) {
      return parseFloat(item.variants[0].price).toFixed(2);
    }
    
    // Multiple variants - show price range
    const prices = item.variants.map(v => parseFloat(v.price));
    const minPrice = Math.min(...prices).toFixed(2);
    const maxPrice = Math.max(...prices).toFixed(2);
    
    if (minPrice === maxPrice) {
      return minPrice;
    }
    
    return `${minPrice} - ${maxPrice}`;
  };

  return (
    <div className="grid grid-cols-4 gap-4">
      {items.map((item) => (
        <button
          key={item.id}
          onClick={() => onItemClick(item)}
          className={cn(
            "bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-all",
            "border-2 border-gray-200 hover:border-orange-500",
            "flex flex-col items-center gap-3 group"
          )}
        >
          {/* Image */}
          <div className="w-full aspect-square rounded-lg bg-gray-100 overflow-hidden">
            {item.image_url ? (
              <img
                src={item.image_url}
                alt={item.name}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-gray-400">
                <svg className="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
            )}
          </div>
          
          {/* Item Info */}
          <div className="w-full text-left">
            <h3 className="font-semibold text-gray-900 truncate">{item.name}</h3>
            {item.description && (
              <p className="text-xs text-gray-500 line-clamp-2 mt-1">{item.description}</p>
            )}
            <p className="text-lg font-bold text-orange-600 mt-2">
              LKR {getDisplayPrice(item)}
            </p>
            {item.variants && item.variants.length > 1 && (
              <p className="text-xs text-gray-500 mt-1">{item.variants.length} variants</p>
            )}
          </div>
        </button>
      ))}
    </div>
  );
}
