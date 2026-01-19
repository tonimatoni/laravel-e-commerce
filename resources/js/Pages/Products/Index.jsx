import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import StockStatus from '@/Components/StockStatus';

export default function Index({ products }) {
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
                                    className="overflow-hidden bg-white shadow-sm rounded-lg transition-shadow hover:shadow-md"
                                >
                                    <div className="p-6">
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
                                        </div>
                                        
                                        <div className="flex items-center justify-between">
                                            <StockStatus stockQuantity={product.stock_quantity} />
                                        </div>
                                    </div>
                                </div>
                            ))}
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
