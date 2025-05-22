<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - OCSMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#5D5CDE',
                        secondary: '#6366F1',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            },
            darkMode: 'class'
        }

        // Dark mode detection
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            if (event.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        
        .dark body {
            background-color: #111827;
            color: #f3f4f6;
        }
        
        .status-active {
            background-color: rgba(16, 185, 129, 0.1);
            color: rgb(16, 185, 129);
        }
        
        .dark .status-active {
            background-color: rgba(16, 185, 129, 0.2);
            color: rgb(52, 211, 153);
        }
        
        .status-inactive {
            background-color: rgba(239, 68, 68, 0.1);
            color: rgb(239, 68, 68);
        }
        
        .dark .status-inactive {
            background-color: rgba(239, 68, 68, 0.2);
            color: rgb(248, 113, 113);
        }
        
        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: rgb(245, 158, 11);
        }
        
        .dark .status-pending {
            background-color: rgba(245, 158, 11, 0.2);
            color: rgb(251, 191, 36);
        }
        
        /* Modal Animation */
        .modal-enter {
            opacity: 0;
            transform: scale(0.95);
        }
        
        .modal-enter-active {
            opacity: 1;
            transform: scale(1);
            transition: opacity 300ms, transform 300ms;
        }
        
        .modal-exit {
            opacity: 1;
            transform: scale(1);
        }
        
        .modal-exit-active {
            opacity: 0;
            transform: scale(0.95);
            transition: opacity 200ms, transform 200ms;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <svg class="h-8 w-8 text-secondary" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    <path d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-8 6h16"/>
                </svg>
                <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white">OCSMS</span>
            </div>
            <div class="flex items-center space-x-2">
                <a href="#" class="px-3 py-1.5 bg-secondary text-white rounded-md text-sm">Registrar</a>
                <a href="#" class="text-gray-700 dark:text-gray-300 text-sm">Admin Registrar</a>
                <a href="#" class="px-3 py-1.5 bg-red-500 text-white rounded-md text-sm">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">User Management</h1>
            <p class="text-gray-600 dark:text-gray-400">View and manage all users in the system.</p>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                        <select id="role" class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-secondary focus:ring focus:ring-secondary focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select id="status" class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-secondary focus:ring focus:ring-secondary focus:ring-opacity-50 dark:bg-gray-700 dark:text-white">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="w-full md:w-64">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <div class="relative">
                        <input type="text" id="search" placeholder="Search users..." class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-secondary focus:ring focus:ring-secondary focus:ring-opacity-50 dark:bg-gray-700 dark:text-white pl-10">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end">
                <button type="button" id="addUserBtn" class="px-4 py-2 bg-secondary text-white rounded-md hover:bg-opacity-90 transition-colors text-sm font-medium flex items-center">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                    </svg>
                    Add New User
                </button>
            </div>
        </div>

        <!-- User List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Join Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="userTableBody">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">U1001</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">John Smith</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">john.smith@example.com</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Admin</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">2023-01-15</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs rounded-full status-active">Active</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="#" class="text-secondary hover:text-opacity-80">View</a>
                                    <a href="#" class="text-secondary hover:text-opacity-80">Edit</a>
                                    <a href="#" class="text-red-500 hover:text-opacity-80">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">U1002</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Sarah Johnson</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">sarah.johnson@example.com</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Teacher</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">2023-02-10</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs rounded-full status-active">Active</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="#" class="text-secondary hover:text-opacity-80">View</a>
                                    <a href="#" class="text-secondary hover:text-opacity-80">Edit</a>
                                    <a href="#" class="text-red-500 hover:text-opacity-80">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">U1003</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Michael Chen</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">michael.chen@example.com</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Student</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">2023-03-05</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs rounded-full status-inactive">Inactive</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="#" class="text-secondary hover:text-opacity-80">View</a>
                                    <a href="#" class="text-secondary hover:text-opacity-80">Edit</a>
                                    <a href="#" class="text-red-500 hover:text-opacity-80">Delete</a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="bg-white dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <span class="font-medium text-gray-700 dark:text-gray-300">3</span> of <span class="font-medium text-gray-700 dark:text-gray-300">15</span> users
                </div>
                <div class="flex space-x-2">
                    <button type="button" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50" disabled>Previous</button>
                    <button type="button" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Next</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="modalBackdrop"></div>
            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="addUserForm">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Add New User
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
                                            <input type="text" name="firstName" id="firstName" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required>
                                        </div>
                                        <div>
                                            <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                                            <input type="text" name="lastName" id="lastName" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                        <input type="email" name="email" id="email" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required>
                                    </div>
                                    
                                    <div>
                                        <label for="userRole" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                                        <select name="userRole" id="userRole" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary sm:text-sm dark:text-white" required>
                                            <option value="">Select a role</option>
                                            <option value="admin">Admin</option>
                                            <option value="teacher">Teacher</option>
                                            <option value="student">Student</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                                        <input type="password" name="password" id="password" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required minlength="8">
                                    </div>
                                    
                                    <div>
                                        <label for="confirmPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                                        <input type="password" name="confirmPassword" id="confirmPassword" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required minlength="8">
                                    </div>
                                    
                                    <div>
                                        <label for="userStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                        <select name="userStatus" id="userStatus" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary sm:text-sm dark:text-white" required>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-secondary text-base font-medium text-white hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary sm:ml-3 sm:w-auto sm:text-sm">
                            Add User
                        </button>
                        <button type="button" id="cancelBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="successToast" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg hidden transform transition-all duration-300 translate-y-10 opacity-0">
        User added successfully!
    </div>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 mt-12 py-6 border-t border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">© 2025 Online Course/Student Management System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Add User functionality
        document.addEventListener('DOMContentLoaded', function() {
            const addUserBtn = document.getElementById('addUserBtn');
            const addUserModal = document.getElementById('addUserModal');
            const modalBackdrop = document.getElementById('modalBackdrop');
            const cancelBtn = document.getElementById('cancelBtn');
            const addUserForm = document.getElementById('addUserForm');
            const successToast = document.getElementById('successToast');
            const userTableBody = document.getElementById('userTableBody');
            
            // Generate a unique ID for new users
            function generateUserId() {
                return 'U' + (1004 + Math.floor(Math.random() * 1000));
            }
            
            // Current date in YYYY-MM-DD format
            function getCurrentDate() {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
            
            // Show modal
            addUserBtn.addEventListener('click', function() {
                addUserModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                
                // Add animation class
                setTimeout(() => {
                    addUserModal.querySelector('.transform').classList.add('scale-100', 'opacity-100');
                    addUserModal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
                }, 10);
            });
            
            // Hide modal function
            function hideModal() {
                addUserModal.querySelector('.transform').classList.remove('scale-100', 'opacity-100');
                addUserModal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
                
                setTimeout(() => {
                    addUserModal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                    addUserForm.reset(); // Reset form on close
                }, 200);
            }
            
            // Hide modal when clicking cancel
            cancelBtn.addEventListener('click', hideModal);
            
            // Hide modal when clicking backdrop
            modalBackdrop.addEventListener('click', hideModal);
            
            // Prevent modal from closing when clicking inside the modal
            addUserModal.querySelector('.inline-block').addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Form submission
            addUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form values
                const firstName = document.getElementById('firstName').value;
                const lastName = document.getElementById('lastName').value;
                const email = document.getElementById('email').value;
                const role = document.getElementById('userRole').value;
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const status = document.getElementById('userStatus').value;
                
                // Validate passwords match
                if (password !== confirmPassword) {
                    alert('Passwords do not match');
                    return;
                }
                
                // Generate a new user row
                const newUserId = generateUserId();
                const joinDate = getCurrentDate();
                const fullName = `${firstName} ${lastName}`;
                
                // Create new row HTML
                const newRow = document.createElement('tr');
                newRow.className = 'hover:bg-gray-50 dark:hover:bg-gray-700';
                newRow.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${newUserId}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">${fullName}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">${email}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">${role.charAt(0).toUpperCase() + role.slice(1)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">${joinDate}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 py-1 text-xs rounded-full status-${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="#" class="text-secondary hover:text-opacity-80">View</a>
                            <a href="#" class="text-secondary hover:text-opacity-80">Edit</a>
                            <a href="#" class="text-red-500 hover:text-opacity-80">Delete</a>
                        </div>
                    </td>
                `;
                
                // Add the new row at the beginning of the table
                userTableBody.insertBefore(newRow, userTableBody.firstChild);
                
                // Hide modal
                hideModal();
                
                // Show success toast
                successToast.classList.remove('hidden', 'translate-y-10', 'opacity-0');
                
                // Auto hide toast after 3 seconds
                setTimeout(() => {
                    successToast.classList.add('translate-y-10', 'opacity-0');
                    setTimeout(() => {
                        successToast.classList.add('hidden');
                    }, 300);
                }, 3000);
                
                // Update the user count
                const countElement = document.querySelector('.text-gray-500.dark\\:text-gray-400 span:first-of-type');
                if (countElement) {
                    const currentCount = parseInt(countElement.textContent, 10);
                    countElement.textContent = currentCount + 1;
                }
            });
            
            // Delete user functionality
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('text-red-500') || e.target.closest('.text-red-500')) {
                    // Prevent the default link behavior
                    e.preventDefault();
                    
                    // Find the row to delete
                    const row = e.target.closest('tr');
                    if (row && confirm('Are you sure you want to delete this user?')) {
                        // Remove the row
                        row.remove();
                        
                        // Update the user count
                        const countElement = document.querySelector('.text-gray-500.dark\\:text-gray-400 span:first-of-type');
                        if (countElement) {
                            const currentCount = parseInt(countElement.textContent, 10);
                            countElement.textContent = currentCount - 1;
                        }
                        
                        // Show a success toast
                        successToast.textContent = 'User deleted successfully!';
                        successToast.classList.remove('hidden', 'translate-y-10', 'opacity-0');
                        
                        // Auto hide toast after 3 seconds
                        setTimeout(() => {
                            successToast.classList.add('translate-y-10', 'opacity-0');
                            setTimeout(() => {
                                successToast.classList.add('hidden');
                                successToast.textContent = 'User added successfully!';
                            }, 300);
                        }, 3000);
                    }
                }
            });
        });
    </script>
</body>
</html>