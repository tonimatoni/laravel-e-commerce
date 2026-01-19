import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Confirmation({ order, flash }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Order Confirmation
                </h2>
            }
        >
            <Head title="Order Confirmation" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 p-4">
                            <div className="text-sm text-green-800">
                                {flash.success}
                            </div>
                        </div>
                    )}

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="px-4 sm:px-6 py-4 border-b border-gray-200">
                            <div className="flex justify-between items-center">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Order #{order.order_number}
                                    </h3>
                                    <p className="text-sm text-gray-500 mt-1">
                                        Placed on {order.created_at}
                                    </p>
                                </div>
                                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    {order.status}
                                </span>
                            </div>
                        </div>

                        <div className="p-4 sm:p-6">
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <h4 className="text-sm font-medium text-gray-900 mb-2">
                                        Shipping Address
                                    </h4>
                                    <div className="text-sm text-gray-600">
                                        <p>{order.shipping_name}</p>
                                        <p>{order.shipping_email}</p>
                                        <p className="mt-2">{order.shipping_address}</p>
                                        <p>
                                            {order.shipping_city}
                                            {order.shipping_state && `, ${order.shipping_state}`}
                                            {' '}
                                            {order.shipping_postal_code}
                                        </p>
                                        <p>{order.shipping_country}</p>
                                    </div>
                                </div>

                                <div>
                                    <h4 className="text-sm font-medium text-gray-900 mb-2">
                                        Order Items
                                    </h4>
                                    <div className="space-y-2">
                                        {order.order_items.map((item) => (
                                            <div key={item.id} className="flex justify-between text-sm">
                                                <div>
                                                    <p className="font-medium text-gray-900">
                                                        {item.product_name}
                                                    </p>
                                                    <p className="text-gray-500">
                                                        Qty: {item.quantity} × ${parseFloat(item.price).toFixed(2)}
                                                    </p>
                                                </div>
                                                <p className="font-medium text-gray-900">
                                                    ${parseFloat(item.subtotal).toFixed(2)}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 pt-6 border-t border-gray-200">
                                <div className="space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-700">Subtotal:</span>
                                        <span className="font-medium text-gray-900">
                                            ${parseFloat(order.subtotal).toFixed(2)}
                                        </span>
                                    </div>
                                    {parseFloat(order.tax) > 0 && (
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-700">Tax:</span>
                                            <span className="font-medium text-gray-900">
                                                ${parseFloat(order.tax).toFixed(2)}
                                            </span>
                                        </div>
                                    )}
                                    <div className="flex justify-between pt-2 border-t border-gray-300">
                                        <span className="text-base font-medium text-gray-900">Total:</span>
                                        <span className="text-lg font-bold text-gray-900">
                                            ${parseFloat(order.total).toFixed(2)}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 flex gap-4">
                                <Link
                                    href={route('orders.invoice', order.id)}
                                    target="_blank"
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    View Invoice
                                </Link>
                                <Link
                                    href={route('products.index')}
                                    className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Continue Shopping
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
