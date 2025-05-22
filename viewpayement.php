<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - View Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#5D5CDE',
                        secondary: '#4945B4',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="dashboard.php" class="flex items-center text-gray-800 font-bold text-2xl">
                <i class="fas fa-graduation-cap text-primary mr-2"></i>
                OCSMS
            </a>
            <div class="flex items-center space-x-4">
                <span class="bg-primary text-white text-sm py-1 px-3 rounded-full">Student</span>
                <span class="hidden md:inline" id="studentName">Eba Gudeta</span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white text-sm py-2 px-4 rounded transition">
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6">
        <a href="dashboard.php" class="inline-flex items-center text-primary hover:underline mb-4">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
        
        <h1 class="text-2xl font-bold text-primary mb-6">View Payment</h1>
        
        <!-- Financial Summary Card -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 pb-2 border-b border-gray-200">Financial Summary</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500 mb-1">Total Billed</div>
                    <div class="text-lg font-bold" id="totalBilled">$12,500.00</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500 mb-1">Cost Share Amount</div>
                    <div class="text-lg font-bold" id="costShareAmount">$7,500.00</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500 mb-1">Your Responsibility</div>
                    <div class="text-lg font-bold" id="yourResponsibility">$5,000.00</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500 mb-1">Amount Paid</div>
                    <div class="text-lg font-bold" id="amountPaid">$3,500.00</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-500 mb-1">Balance Due</div>
                    <div class="text-lg font-bold text-red-600" id="balanceDue">$1,500.00</div>
                </div>
            </div>
        </div>
        
        <!-- Invoice History Card -->
        <div class="bg-white rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold p-6 pb-4 border-b border-gray-200">Invoice History</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Responsibility</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="invoiceTableBody">
                        <!-- Fall 2023 -->
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">Fall 2023</td>
                            <td class="px-6 py-4 whitespace-nowrap">Aug 15, 2023</td>
                            <td class="px-6 py-4 whitespace-nowrap">Sep 15, 2023</td>
                            <td class="px-6 py-4 whitespace-nowrap">$6,250.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">$2,500.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">$2,500.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">$0.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button class="view-invoice-btn text-primary hover:text-secondary" data-invoice-id="1">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <!-- Spring 2024 -->
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">Spring 2024</td>
                            <td class="px-6 py-4 whitespace-nowrap">Jan 10, 2024</td>
                            <td class="px-6 py-4 whitespace-nowrap">Feb 10, 2024</td>
                            <td class="px-6 py-4 whitespace-nowrap">$6,250.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">$2,500.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">$1,000.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">$1,500.00</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Partially Paid</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button class="view-invoice-btn text-primary hover:text-secondary" data-invoice-id="2">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Invoice Detail Section (Initially Hidden) -->
        <div id="invoiceDetail" class="bg-white rounded-lg shadow mb-6 hidden">
            <div class="flex justify-between items-center p-6 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold" id="invoiceDetailTitle">Invoice Detail: Spring 2024</h2>
                
                <div id="payButtonContainer">
                    <a href="make_payment.php" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded transition">
                        <i class="fas fa-credit-card mr-2"></i> Pay Now
                    </a>
                </div>
            </div>
            
            <div class="p-6">
                <h3 class="text-lg font-medium mb-3">Invoice Items</h3>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="invoiceItemsBody">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">Tuition Fee</td>
                                <td class="px-6 py-4">CS201: Data Structures</td>
                                <td class="px-6 py-4">Tuition</td>
                                <td class="px-6 py-4">$3,200.00</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">Tuition Fee</td>
                                <td class="px-6 py-4">MATH201: Calculus II</td>
                                <td class="px-6 py-4">Tuition</td>
                                <td class="px-6 py-4">$2,800.00</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">Student Services Fee</td>
                                <td class="px-6 py-4">N/A</td>
                                <td class="px-6 py-4">Fee</td>
                                <td class="px-6 py-4">$250.00</td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-6 py-4 text-right" colspan="3">Total Amount:</td>
                                <td class="px-6 py-4">$6,250.00</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-right" colspan="3">Cost Share Amount:</td>
                                <td class="px-6 py-4">$3,750.00</td>
                            </tr>
                            <tr class="font-bold">
                                <td class="px-6 py-4 text-right" colspan="3">Your Responsibility:</td>
                                <td class="px-6 py-4">$2,500.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h3 class="text-lg font-medium mb-3">Payments</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="paymentsBody">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">Jan 20, 2024</td>
                                <td class="px-6 py-4">$1,000.00</td>
                                <td class="px-6 py-4">Credit Card</td>
                                <td class="px-6 py-4">PAY-20240120-8765</td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-6 py-4 text-right" colspan="3">Total Paid:</td>
                                <td class="px-6 py-4">$1,000.00</td>
                            </tr>
                            <tr class="font-bold">
                                <td class="px-6 py-4 text-right" colspan="3">Balance Due:</td>
                                <td class="px-6 py-4">$1,500.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white py-4 shadow-inner mt-8">
        <div class="container mx-auto px-4 text-center text-gray-500 text-sm">
            &copy; 2025 Online Course/Student Management System. All rights reserved.
        </div>
    </footer>

    <script>
        // Sample data for demonstration
        const invoices = [
            {
                id: 1,
                term: "Fall 2023",
                issueDate: "2023-08-15",
                dueDate: "2023-09-15",
                totalAmount: 6250.00,
                costShareAmount: 3750.00,
                studentResponsibility: 2500.00,
                paidAmount: 2500.00,
                balance: 0.00,
                status: "Paid",
                items: [
                    {
                        description: "Tuition Fee",
                        course: "CS101: Introduction to Programming",
                        category: "Tuition",
                        amount: 3200.00
                    },
                    {
                        description: "Tuition Fee",
                        course: "MATH101: Calculus I",
                        category: "Tuition",
                        amount: 2800.00
                    },
                    {
                        description: "Student Services Fee",
                        course: "N/A",
                        category: "Fee",
                        amount: 250.00
                    }
                ],
                payments: [
                    {
                        date: "2023-09-01",
                        amount: 1500.00,
                        method: "Bank Transfer",
                        reference: "PAY-20230901-1234"
                    },
                    {
                        date: "2023-09-10",
                        amount: 1000.00,
                        method: "Credit Card",
                        reference: "PAY-20230910-5678"
                    }
                ]
            },
            {
                id: 2,
                term: "Spring 2024",
                issueDate: "2024-01-10",
                dueDate: "2024-02-10",
                totalAmount: 6250.00,
                costShareAmount: 3750.00,
                studentResponsibility: 2500.00,
                paidAmount: 1000.00,
                balance: 1500.00,
                status: "Partially Paid",
                items: [
                    {
                        description: "Tuition Fee",
                        course: "CS201: Data Structures",
                        category: "Tuition",
                        amount: 3200.00
                    },
                    {
                        description: "Tuition Fee",
                        course: "MATH201: Calculus II",
                        category: "Tuition",
                        amount: 2800.00
                    },
                    {
                        description: "Student Services Fee",
                        course: "N/A",
                        category: "Fee",
                        amount: 250.00
                    }
                ],
                payments: [
                    {
                        date: "2024-01-20",
                        amount: 1000.00,
                        method: "Credit Card",
                        reference: "PAY-20240120-8765"
                    }
                ]
            }
        ];

        // Format currency
        function formatCurrency(amount) {
            return '$' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }

        // View invoice details
        function viewInvoice(invoiceId) {
            const invoice = invoices.find(inv => inv.id == invoiceId);
            if (!invoice) return;

            // Set invoice title
            document.getElementById('invoiceDetailTitle').textContent = `Invoice Detail: ${invoice.term}`;
            
            // Show/hide pay button based on status
            const payButtonContainer = document.getElementById('payButtonContainer');
            if (invoice.status === "Paid") {
                payButtonContainer.classList.add('hidden');
            } else {
                payButtonContainer.classList.remove('hidden');
            }

            // Populate invoice items
            let itemsHtml = '';
            invoice.items.forEach(item => {
                itemsHtml += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">${item.description}</td>
                        <td class="px-6 py-4">${item.course}</td>
                        <td class="px-6 py-4">${item.category}</td>
                        <td class="px-6 py-4">${formatCurrency(item.amount)}</td>
                    </tr>
                `;
            });

            itemsHtml += `
                <tr class="bg-gray-50 font-bold">
                    <td class="px-6 py-4 text-right" colspan="3">Total Amount:</td>
                    <td class="px-6 py-4">${formatCurrency(invoice.totalAmount)}</td>
                </tr>
                <tr>
                    <td class="px-6 py-4 text-right" colspan="3">Cost Share Amount:</td>
                    <td class="px-6 py-4">${formatCurrency(invoice.costShareAmount)}</td>
                </tr>
                <tr class="font-bold">
                    <td class="px-6 py-4 text-right" colspan="3">Your Responsibility:</td>
                    <td class="px-6 py-4">${formatCurrency(invoice.studentResponsibility)}</td>
                </tr>
            `;
            
            document.getElementById('invoiceItemsBody').innerHTML = itemsHtml;

            // Populate payments
            let paymentsHtml = '';
            
            if (invoice.payments.length === 0) {
                paymentsHtml = `
                    <tr>
                        <td class="px-6 py-4 text-center" colspan="4">No payments have been made for this invoice.</td>
                    </tr>
                `;
            } else {
                invoice.payments.forEach(payment => {
                    paymentsHtml += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">${formatDate(payment.date)}</td>
                            <td class="px-6 py-4">${formatCurrency(payment.amount)}</td>
                            <td class="px-6 py-4">${payment.method}</td>
                            <td class="px-6 py-4">${payment.reference}</td>
                        </tr>
                    `;
                });
            }
            
            paymentsHtml += `
                <tr class="bg-gray-50 font-bold">
                    <td class="px-6 py-4 text-right" colspan="3">Total Paid:</td>
                    <td class="px-6 py-4">${formatCurrency(invoice.paidAmount)}</td>
                </tr>
                <tr class="font-bold">
                    <td class="px-6 py-4 text-right" colspan="3">Balance Due:</td>
                    <td class="px-6 py-4">${formatCurrency(invoice.balance)}</td>
                </tr>
            `;
            
            document.getElementById('paymentsBody').innerHTML = paymentsHtml;

            // Show the invoice detail section
            document.getElementById('invoiceDetail').classList.remove('hidden');
            
            // Scroll to invoice detail section
            document.getElementById('invoiceDetail').scrollIntoView({ behavior: 'smooth' });
        }

        // Add event listeners to view buttons
        document.querySelectorAll('.view-invoice-btn').forEach(button => {
            button.addEventListener('click', function() {
                const invoiceId = this.getAttribute('data-invoice-id');
                viewInvoice(invoiceId);
            });
        });
    </script>
</body>
</html>