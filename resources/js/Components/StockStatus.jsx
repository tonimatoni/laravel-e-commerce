export default function StockStatus({ stockQuantity, showQuantity = true }) {
    const isOutOfStock = stockQuantity === 0;
    const isLowStock = stockQuantity > 0 && stockQuantity < 5;
    const isInStock = stockQuantity >= 5;

    const getStatusBadge = () => {
        if (isOutOfStock) {
            return {
                text: 'Out of Stock',
                className: 'bg-red-100 text-red-800',
            };
        }
        
        if (isLowStock) {
            return {
                text: 'Low Stock',
                className: 'bg-yellow-100 text-yellow-800',
            };
        }
        
        return {
            text: 'In Stock',
            className: 'bg-green-100 text-green-800',
        };
    };

    const badge = getStatusBadge();

    return (
        <div className="flex flex-col gap-1">
            {showQuantity && !isOutOfStock && (
                <span className="text-sm text-gray-600">
                    Stock: {stockQuantity}
                </span>
            )}
            {isOutOfStock && (
                <span className="text-sm text-red-600 font-medium">
                    Out of Stock
                </span>
            )}
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.className}`}>
                {badge.text}
            </span>
        </div>
    );
}
