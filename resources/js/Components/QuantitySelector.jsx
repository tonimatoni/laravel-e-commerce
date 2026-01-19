import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function QuantitySelector({ 
    cartItemId, 
    currentQuantity, 
    maxQuantity,
    onUpdate 
}) {
    const [quantity, setQuantity] = useState(currentQuantity);
    const [processing, setProcessing] = useState(false);

    const handleChange = (newQuantity) => {
        const qty = parseInt(newQuantity) || 1;
        const finalQuantity = Math.max(1, Math.min(qty, maxQuantity));
        
        setQuantity(finalQuantity);

        if (finalQuantity !== currentQuantity) {
            setProcessing(true);
            
            router.put(`/cart/${cartItemId}`, {
                quantity: finalQuantity,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => {
                    setProcessing(false);
                    if (onUpdate) {
                        onUpdate();
                    }
                },
            });
        }
    };

    const handleIncrement = () => {
        if (quantity < maxQuantity) {
            handleChange(quantity + 1);
        }
    };

    const handleDecrement = () => {
        if (quantity > 1) {
            handleChange(quantity - 1);
        }
    };

    return (
        <div className="flex items-center space-x-2">
            <button
                type="button"
                onClick={handleDecrement}
                disabled={quantity <= 1 || processing}
                className="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                -
            </button>
            <input
                type="number"
                min="1"
                max={maxQuantity}
                value={quantity}
                onChange={(e) => handleChange(e.target.value)}
                disabled={processing}
                className="w-16 rounded-md border border-gray-300 px-2 py-1 text-center text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50"
            />
            <button
                type="button"
                onClick={handleIncrement}
                disabled={quantity >= maxQuantity || processing}
                className="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                +
            </button>
        </div>
    );
}
