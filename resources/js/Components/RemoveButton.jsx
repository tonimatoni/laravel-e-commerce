import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function RemoveButton({ cartItemId }) {
    const [processing, setProcessing] = useState(false);

    const handleRemove = () => {
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            setProcessing(true);
            
            router.delete(`/cart/${cartItemId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => {
                    setProcessing(false);
                },
            });
        }
    };

    return (
        <button
            type="button"
            onClick={handleRemove}
            disabled={processing}
            className="rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            {processing ? 'Removing...' : 'Remove'}
        </button>
    );
}
