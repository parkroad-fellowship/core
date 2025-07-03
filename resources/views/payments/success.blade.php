<x-guest-layout>
    <div class="pt-4 bg-gray-100">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div>
                <x-authentication-card-logo />
            </div>

            <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white shadow-md overflow-hidden sm:rounded-lg prose">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>

                    <h2 class="mt-4 text-2xl font-bold text-gray-900">Payment Successful!</h2>
                    <p class="mt-2 text-gray-600">Thank you for your payment. Your transaction has been completed.</p>

                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <div class="flex flex-col space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Name:</span>
                                <span class="font-medium">{{ $payment->member->full_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Purpose:</span>
                                <span class="font-medium">{{ $payment->paymentType->name }}
                                    ({{ $payment->paymentType->description }})</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment ID:</span>
                                <span class="font-medium">{{ $payment->reference }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Amount:</span>
                                <span class="font-medium">KES {{ $payment->amount }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Method:</span>
                                <span class="font-medium">{{ $payment->transaction_meta['channel'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="/"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-hidden focus:border-gray-900 focus:ring-3 focus:ring-gray-300 disabled:opacity-25 transition">
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
