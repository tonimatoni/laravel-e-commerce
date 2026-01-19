import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ cartItems = [], subtotal = 0, tax = 0, total = 0, user, flash }) {
    const { data, setData, post, processing, errors } = useForm({
        shipping_name: user?.name || '',
        shipping_email: user?.email || '',
        shipping_phone: '',
        shipping_address: '',
        shipping_city: '',
        shipping_state: '',
        shipping_postal_code: '',
        shipping_country: 'US',
        billing_name: '',
        billing_email: '',
        billing_address: '',
        billing_city: '',
        billing_state: '',
        billing_postal_code: '',
        billing_country: '',
        use_billing_address: false,
    });

    const [useBillingAddress, setUseBillingAddress] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('checkout.store'));
    };

    const handleUseBillingAddressChange = (checked) => {
        setUseBillingAddress(checked);
        if (checked) {
            setData({
                ...data,
                billing_name: data.shipping_name,
                billing_email: data.shipping_email,
                billing_address: data.shipping_address,
                billing_city: data.shipping_city,
                billing_state: data.shipping_state,
                billing_postal_code: data.shipping_postal_code,
                billing_country: data.shipping_country,
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Checkout
                </h2>
            }
        >
            <Head title="Checkout" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {flash?.error && (
                        <div className="mb-4 rounded-md bg-red-50 p-4">
                            <div className="text-sm text-red-800">
                                {flash.error}
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <div className="lg:col-span-2">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                                    <div className="px-4 sm:px-6 py-4 border-b border-gray-200">
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Shipping Information
                                        </h3>
                                    </div>
                                    <div className="p-4 sm:p-6 space-y-4">
                                        <div>
                                            <label htmlFor="shipping_name" className="block text-sm font-medium text-gray-700">
                                                Full Name *
                                            </label>
                                            <input
                                                type="text"
                                                id="shipping_name"
                                                value={data.shipping_name}
                                                onChange={(e) => setData('shipping_name', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                required
                                            />
                                            {errors.shipping_name && (
                                                <p className="mt-1 text-sm text-red-600">{errors.shipping_name}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="shipping_email" className="block text-sm font-medium text-gray-700">
                                                Email *
                                            </label>
                                            <input
                                                type="email"
                                                id="shipping_email"
                                                value={data.shipping_email}
                                                onChange={(e) => setData('shipping_email', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                required
                                            />
                                            {errors.shipping_email && (
                                                <p className="mt-1 text-sm text-red-600">{errors.shipping_email}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="shipping_phone" className="block text-sm font-medium text-gray-700">
                                                Phone
                                            </label>
                                            <input
                                                type="tel"
                                                id="shipping_phone"
                                                value={data.shipping_phone}
                                                onChange={(e) => setData('shipping_phone', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            {errors.shipping_phone && (
                                                <p className="mt-1 text-sm text-red-600">{errors.shipping_phone}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="shipping_address" className="block text-sm font-medium text-gray-700">
                                                Address *
                                            </label>
                                            <textarea
                                                id="shipping_address"
                                                value={data.shipping_address}
                                                onChange={(e) => setData('shipping_address', e.target.value)}
                                                rows={3}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                required
                                            />
                                            {errors.shipping_address && (
                                                <p className="mt-1 text-sm text-red-600">{errors.shipping_address}</p>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label htmlFor="shipping_city" className="block text-sm font-medium text-gray-700">
                                                    City *
                                                </label>
                                                <input
                                                    type="text"
                                                    id="shipping_city"
                                                    value={data.shipping_city}
                                                    onChange={(e) => setData('shipping_city', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    required
                                                />
                                                {errors.shipping_city && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.shipping_city}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="shipping_state" className="block text-sm font-medium text-gray-700">
                                                    State/Province
                                                </label>
                                                <input
                                                    type="text"
                                                    id="shipping_state"
                                                    value={data.shipping_state}
                                                    onChange={(e) => setData('shipping_state', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                />
                                                {errors.shipping_state && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.shipping_state}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label htmlFor="shipping_postal_code" className="block text-sm font-medium text-gray-700">
                                                    Postal Code *
                                                </label>
                                                <input
                                                    type="text"
                                                    id="shipping_postal_code"
                                                    value={data.shipping_postal_code}
                                                    onChange={(e) => setData('shipping_postal_code', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    required
                                                />
                                                {errors.shipping_postal_code && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.shipping_postal_code}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="shipping_country" className="block text-sm font-medium text-gray-700">
                                                    Country
                                                </label>
                                                <input
                                                    type="text"
                                                    id="shipping_country"
                                                    value={data.shipping_country}
                                                    onChange={(e) => setData('shipping_country', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                />
                                                {errors.shipping_country && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.shipping_country}</p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                                    <div className="px-4 sm:px-6 py-4 border-b border-gray-200">
                                        <div className="flex items-center">
                                            <input
                                                type="checkbox"
                                                id="use_billing_address"
                                                checked={useBillingAddress}
                                                onChange={(e) => handleUseBillingAddressChange(e.target.checked)}
                                                className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            />
                                            <label htmlFor="use_billing_address" className="ml-2 text-sm font-medium text-gray-700">
                                                Billing address same as shipping
                                            </label>
                                        </div>
                                    </div>
                                    {!useBillingAddress && (
                                        <div className="p-4 sm:p-6 space-y-4">
                                            <h3 className="text-lg font-medium text-gray-900 mb-4">
                                                Billing Information
                                            </h3>
                                            <div>
                                                <label htmlFor="billing_name" className="block text-sm font-medium text-gray-700">
                                                    Full Name
                                                </label>
                                                <input
                                                    type="text"
                                                    id="billing_name"
                                                    value={data.billing_name}
                                                    onChange={(e) => setData('billing_name', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                />
                                            </div>

                                            <div>
                                                <label htmlFor="billing_email" className="block text-sm font-medium text-gray-700">
                                                    Email
                                                </label>
                                                <input
                                                    type="email"
                                                    id="billing_email"
                                                    value={data.billing_email}
                                                    onChange={(e) => setData('billing_email', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                />
                                            </div>

                                            <div>
                                                <label htmlFor="billing_address" className="block text-sm font-medium text-gray-700">
                                                    Address
                                                </label>
                                                <textarea
                                                    id="billing_address"
                                                    value={data.billing_address}
                                                    onChange={(e) => setData('billing_address', e.target.value)}
                                                    rows={3}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                />
                                            </div>

                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label htmlFor="billing_city" className="block text-sm font-medium text-gray-700">
                                                        City
                                                    </label>
                                                    <input
                                                        type="text"
                                                        id="billing_city"
                                                        value={data.billing_city}
                                                        onChange={(e) => setData('billing_city', e.target.value)}
                                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                </div>

                                                <div>
                                                    <label htmlFor="billing_state" className="block text-sm font-medium text-gray-700">
                                                        State/Province
                                                    </label>
                                                    <input
                                                        type="text"
                                                        id="billing_state"
                                                        value={data.billing_state}
                                                        onChange={(e) => setData('billing_state', e.target.value)}
                                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label htmlFor="billing_postal_code" className="block text-sm font-medium text-gray-700">
                                                        Postal Code
                                                    </label>
                                                    <input
                                                        type="text"
                                                        id="billing_postal_code"
                                                        value={data.billing_postal_code}
                                                        onChange={(e) => setData('billing_postal_code', e.target.value)}
                                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                </div>

                                                <div>
                                                    <label htmlFor="billing_country" className="block text-sm font-medium text-gray-700">
                                                        Country
                                                    </label>
                                                    <input
                                                        type="text"
                                                        id="billing_country"
                                                        value={data.billing_country}
                                                        onChange={(e) => setData('billing_country', e.target.value)}
                                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="flex justify-end">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                    >
                                        {processing ? 'Processing...' : 'Place Order'}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div className="lg:col-span-1">
                            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg sticky top-4">
                                <div className="px-4 sm:px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Order Summary
                                    </h3>
                                </div>
                                <div className="divide-y divide-gray-200">
                                    {cartItems.map((item) => {
                                        const productPrice = item.product.has_active_discount 
                                            ? parseFloat(item.product.discounted_price) 
                                            : parseFloat(item.product.price);
                                        const itemSubtotal = item.quantity * productPrice;
                                        return (
                                            <div key={item.id} className="p-4">
                                                <div className="flex justify-between">
                                                    <div className="flex-1">
                                                        <p className="text-sm font-medium text-gray-900">
                                                            {item.product.name}
                                                        </p>
                                                        <p className="text-xs text-gray-500">
                                                            Qty: {item.quantity} × ${productPrice.toFixed(2)}
                                                            {item.product.has_active_discount && (
                                                                <span className="ml-1 px-1 text-xs font-semibold text-white bg-red-600 rounded">
                                                                    -{parseFloat(item.product.discount_percentage).toFixed(0)}%
                                                                </span>
                                                            )}
                                                        </p>
                                                    </div>
                                                    <p className="text-sm font-medium text-gray-900">
                                                        ${itemSubtotal.toFixed(2)}
                                                    </p>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                                <div className="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
                                    <div className="space-y-2">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-700">Subtotal:</span>
                                            <span className="font-medium text-gray-900">
                                                ${subtotal.toFixed(2)}
                                            </span>
                                        </div>
                                        {tax > 0 && (
                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-700">Tax:</span>
                                                <span className="font-medium text-gray-900">
                                                    ${tax.toFixed(2)}
                                                </span>
                                            </div>
                                        )}
                                        <div className="flex justify-between pt-2 border-t border-gray-300">
                                            <span className="text-base font-medium text-gray-900">Total:</span>
                                            <span className="text-lg font-bold text-gray-900">
                                                ${total.toFixed(2)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
