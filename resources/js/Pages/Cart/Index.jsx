import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import QuantitySelector from '@/Components/QuantitySelector';
import RemoveButton from '@/Components/RemoveButton';

export default function Index({ cartItems = [], subtotal = 0, tax = 0, total = 0, cartCount = 0, flash }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Shopping Cart
                </h2>
            }
        >
            <Head title="Shopping Cart" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 p-4">
                            <div className="text-sm text-green-800">
                                {flash.success}
                            </div>
                        </div>
                    )}

                    {flash?.error && (
                        <div className="mb-4 rounded-md bg-red-50 p-4">
                            <div className="text-sm text-red-800">
                                {flash.error}
                            </div>
                        </div>
                    )}

                    {cartItems.length === 0 ? (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6 text-gray-900">
                                <p className="text-lg">Your cart is empty.</p>
                                <p className="text-sm text-gray-600 mt-2">
                                    Add some products to get started!
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="px-4 sm:px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Cart Items ({cartCount})
                                </h3>
                            </div>
                            <div className="divide-y divide-gray-200">
                                {cartItems.map((item) => {
                                    const productPrice = item.product.has_active_discount 
                                        ? parseFloat(item.product.discounted_price) 
                                        : parseFloat(item.product.price);
                                    const itemSubtotal = item.quantity * productPrice;
                                    return (
                                        <div key={item.id} className="p-4 sm:p-6">
                                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                                <div className="flex-1 min-w-0">
                                                    <h4 className="text-base sm:text-lg font-medium text-gray-900">
                                                        {item.product.name}
                                                    </h4>
                                                    <div className="text-sm text-gray-600 mt-1">
                                                        {item.product.has_active_discount ? (
                                                            <div className="flex items-center gap-2">
                                                                <span>${productPrice.toFixed(2)} each</span>
                                                                <span className="px-1.5 py-0.5 text-xs font-semibold text-white bg-red-600 rounded">
                                                                    -{parseFloat(item.product.discount_percentage).toFixed(0)}%
                                                                </span>
                                                                <span className="text-xs text-gray-400 line-through">
                                                                    ${parseFloat(item.product.price).toFixed(2)}
                                                                </span>
                                                            </div>
                                                        ) : (
                                                            <span>${productPrice.toFixed(2)} each</span>
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-gray-500 mt-1 sm:hidden">
                                                        Quantity: {item.quantity}
                                                    </p>
                                                </div>
                                                <div className="flex items-center justify-between sm:justify-end gap-4 sm:gap-6">
                                                    <div className="hidden sm:block">
                                                        <QuantitySelector
                                                            cartItemId={item.id}
                                                            currentQuantity={item.quantity}
                                                            maxQuantity={item.product.stock_quantity}
                                                        />
                                                    </div>
                                                    <div className="sm:hidden">
                                                        <QuantitySelector
                                                            cartItemId={item.id}
                                                            currentQuantity={item.quantity}
                                                            maxQuantity={item.product.stock_quantity}
                                                        />
                                                    </div>
                                                    <RemoveButton cartItemId={item.id} />
                                                    <div className="text-right min-w-[100px]">
                                                        <p className="text-base sm:text-lg font-semibold text-gray-900">
                                                            ${itemSubtotal.toFixed(2)}
                                                        </p>
                                                        <p className="text-xs sm:text-sm text-gray-500">
                                                            Subtotal
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                            <div className="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
                                <div className="space-y-2">
                                    <div className="flex justify-between items-center text-sm sm:text-base">
                                        <span className="text-gray-700">Subtotal:</span>
                                        <span className="font-medium text-gray-900">
                                            ${subtotal.toFixed(2)}
                                        </span>
                                    </div>
                                    {tax > 0 && (
                                        <div className="flex justify-between items-center text-sm sm:text-base">
                                            <span className="text-gray-700">Tax:</span>
                                            <span className="font-medium text-gray-900">
                                                ${tax.toFixed(2)}
                                            </span>
                                        </div>
                                    )}
                                    <div className="flex justify-between items-center pt-2 border-t border-gray-300">
                                        <span className="text-lg sm:text-xl font-medium text-gray-900">Total:</span>
                                        <span className="text-xl sm:text-2xl font-bold text-gray-900">
                                            ${total.toFixed(2)}
                                        </span>
                                    </div>
                                </div>
                                <div className="px-4 sm:px-6 py-4">
                                    <Link
                                        href={route('checkout.index')}
                                        className="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Proceed to Checkout
                                    </Link>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
