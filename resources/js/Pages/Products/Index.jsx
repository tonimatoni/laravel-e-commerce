import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import StockStatus from '@/Components/StockStatus';
import PrimaryButton from '@/Components/PrimaryButton';
import { useState } from 'react';

export default function Index({ products, flash }) {
    const [processing, setProcessing] = useState({});

    const handleAddToCart = (productId) => {
        setProcessing({ ...processing, [productId]: true });

        router.post('/cart', {
            product_id: productId,
            quantity: 1,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
            onFinish: () => {
                setProcessing({ ...processing, [productId]: false });
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Product Catalog
                </h2>
            }
        >
            <Head title="Products" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {products.data.length === 0 ? (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6 text-gray-900">
                                <p>No products available at this time.</p>
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {products.data.map((product) => (
                                <div
                                    key={product.id}
                                    className="flex flex-col overflow-hidden bg-white shadow-sm rounded-lg transition-shadow hover:shadow-md"
                                >
                                    <div className="flex flex-col flex-grow p-6">
                                        <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                            {product.name}
                                        </h3>
                                        
                                        {product.description && (
                                            <p className="text-sm text-gray-600 mb-4 line-clamp-2">
                                                {product.description}
                                            </p>
                                        )}
                                        
                                        <div className="flex items-center justify-between mb-4">
                                            <span className="text-2xl font-bold text-gray-900">
                                                ${parseFloat(product.price).toFixed(2)}
                                            </span>
                                            <StockStatus stockQuantity={product.stock_quantity} />
                                        </div>
                                        
                                        <div className="mt-auto">
                                            <PrimaryButton
                                                onClick={() => handleAddToCart(product.id)}
                                                disabled={product.stock_quantity === 0 || processing[product.id]}
                                                className="w-full"
                                            >
                                                {processing[product.id] ? 'Adding...' : product.stock_quantity === 0 ? 'Out of Stock' : 'Add to Cart'}
                                            </PrimaryButton>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {flash?.success && (
                        <div className="mt-4 rounded-md bg-green-50 p-4">
                            <div className="text-sm text-green-800">
                                {flash.success}
                            </div>
                        </div>
                    )}

                    {flash?.error && (
                        <div className="mt-4 rounded-md bg-red-50 p-4">
                            <div className="text-sm text-red-800">
                                {flash.error}
                            </div>
                        </div>
                    )}

                    {products.links && products.links.length > 3 && (
                        <div className="mt-6 flex justify-center">
                            <nav className="flex space-x-2">
                                {products.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-4 py-2 text-sm font-medium rounded-md ${
                                            link.active
                                                ? 'bg-blue-600 text-white'
                                                : link.url
                                                ? 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </nav>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
