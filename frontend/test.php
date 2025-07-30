<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add KPay logo from CDN or local -->
    <style>
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-content {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <!-- Main Payment Card -->
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 p-6 text-white">
            <h1 class="text-2xl font-bold">Complete Your Payment</h1>
            <p class="text-blue-100">Choose your preferred payment method</p>
        </div>
        
        <!-- Payment Form -->
        <form action="payment.php" method="POST" class="p-6 space-y-6">
            <!-- Amount Section -->
            <div>
                <label class="block text-gray-700 font-medium mb-2">Amount to Pay</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                    <input 
                        type="number" 
                        class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        placeholder="0.00"
                        required
                    >
                </div>
            </div>
            
            <!-- Payment Method Selection -->
            <div>
                <label class="block text-gray-700 font-medium mb-3">Payment Method</label>
                
                <div class="space-y-3">
                    <!-- KPay Option -->
                    <div class="flex items-center">
                        <input 
                            id="kpay" 
                            name="payment_method" 
                            type="radio" 
                            value="kpay"
                            class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300"
                            checked
                        >
                        <label for="kpay" class="ml-3 flex items-center">
                            <img src="https://via.placeholder.com/100x50/3b82f6/ffffff?text=KPay" alt="KPay Logo" class="h-5 w-10 mr-2 object-contain">
                            <span class="text-gray-700">KPay</span>
                        </label>
                    </div>
                    
                    <!-- Cash Option -->
                    <div class="flex items-center">
                        <input 
                            id="cash" 
                            name="payment_method" 
                            type="radio" 
                            value="cash"
                            class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300"
                        >
                        <label for="cash" class="ml-3 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-gray-700">Cash</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- KPay Transaction Number (shown when KPay is selected) -->
            <div id="kpay-details" class="transition-all duration-200">
                <label for="transaction_no" class="block text-gray-700 font-medium mb-2">KPay Transaction Number</label>
                <input 
                    type="text" 
                    id="transaction_no"
                    name="transaction_no"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                    placeholder="Enter transaction number"
                    required
                >
                <p class="mt-2 text-sm text-gray-500">Please enter the transaction number from your KPay app</p>
            </div>
            
            <!-- Cash Instructions (hidden by default) -->
            <div id="cash-details" class="transition-all duration-200 hidden">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Please prepare exact amount. Your order will be confirmed when payment is received.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enroll Now Button (triggers modal) -->
            <a href="#" id="enroll-btn" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md text-center focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                Enroll Now
            </a>
            
            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Confirm Payment
            </button>
        </form>
    </div>

    <!-- Modal (hidden by default) -->
    <div id="enroll-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 opacity-0 invisible z-50">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md transform scale-95 opacity-0">
            <!-- Modal Header -->
            <div class="bg-indigo-600 p-6 text-white rounded-t-xl">
                <h2 class="text-2xl font-bold">Enrollment Information</h2>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6 space-y-4">
                <p>Thank you for your interest in our program. Please provide your details to complete enrollment.</p>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Full Name</label>
                        <input type="text" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-200 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Email</label>
                        <input type="email" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-200 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Phone Number</label>
                        <input type="tel" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-200 outline-none">
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button id="close-modal" class="px-4 py-2 text-gray-700 font-medium rounded-lg hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                    Submit Enrollment
                </button>
            </div>
        </div>
    </div>

    <script>
        // Toggle between payment methods
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'kpay') {
                    document.getElementById('kpay-details').classList.remove('hidden');
                    document.getElementById('cash-details').classList.add('hidden');
                } else {
                    document.getElementById('kpay-details').classList.add('hidden');
                    document.getElementById('cash-details').classList.remove('hidden');
                }
            });
        });

        // Modal functionality
        const enrollBtn = document.getElementById('enroll-btn');
        const modal = document.getElementById('enroll-modal');
        const closeBtn = document.getElementById('close-modal');
        const modalContent = document.querySelector('.modal-content');

        enrollBtn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.remove('invisible', 'opacity-0');
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
            document.body.style.overflow = 'hidden';
        });

        closeBtn.addEventListener('click', () => {
            closeModal();
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        function closeModal() {
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('invisible', 'opacity-0');
                document.body.style.overflow = 'auto';
            }, 300);
        }
    </script>
</body>
</html>