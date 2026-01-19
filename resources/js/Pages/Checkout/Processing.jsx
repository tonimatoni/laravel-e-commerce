import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Processing({ order, flash }) {
    const [status, setStatus] = useState('processing');
    const [error, setError] = useState(null);

    useEffect(() => {
        const eventSource = new EventSource(route('checkout.status', order.id));

        eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                setStatus(data.status);

                if (data.status === 'completed') {
                    eventSource.close();
                    setTimeout(() => {
                        router.visit(route('checkout.confirmation', order.id));
                    }, 500);
                } else if (data.status === 'failed') {
                    eventSource.close();
                    setError('Order processing failed. Please try again.');
                    setTimeout(() => {
                        router.visit(route('checkout.index'));
                    }, 3000);
                } else if (data.status === 'timeout') {
                    eventSource.close();
                    setError('Order processing is taking longer than expected. Please check back later.');
                }
            } catch (e) {
                console.error('Error parsing SSE data:', e);
            }
        };

        eventSource.onerror = (error) => {
            console.error('SSE error:', error);
            eventSource.close();
            setError('Connection error. Please refresh the page.');
        };

        return () => {
            eventSource.close();
        };
    }, [order.id]);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Processing Your Order
                </h2>
            }
        >
            <Head title="Processing Order" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-8 text-center">
                            {error ? (
                                <div className="space-y-4">
                                    <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                                        <svg
                                            className="h-6 w-6 text-red-600"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </div>
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Processing Error
                                    </h3>
                                    <p className="text-sm text-gray-600">{error}</p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    <div className="mx-auto flex h-16 w-16 items-center justify-center">
                                        <div className="h-16 w-16 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
                                    </div>
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Processing Order #{order.order_number}
                                    </h3>
                                    <p className="text-sm text-gray-600">
                                        Please wait while we process your order...
                                    </p>
                                    <div className="mt-4">
                                        <div className="mx-auto h-1 w-64 overflow-hidden rounded-full bg-gray-200">
                                            <div className="h-full w-full animate-pulse bg-indigo-600"></div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
