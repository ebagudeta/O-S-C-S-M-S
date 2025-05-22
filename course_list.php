<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - OCSMS</title>
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
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .dark .status-active {
            background-color: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }
        
        .status-inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .dark .status-inactive {
            background-color: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        .status-pending {
            background-color: #fff7ed;
            color: #c2410c;
        }
        
        .dark .status-pending {
            background-color: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        /* Search functionality styles */
        .search-highlight {
            background-color: rgba(93, 92, 222, 0.2);
            padding: 0 2px;
            border-radius: 2px;
        }

        .dark .search-highlight {
            background-color: rgba(99, 102, 241, 0.3);
        }

        tr.hidden-row {
            display: none;
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Modal animations */
        .modal {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }
        
        .modal.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            transform: scale(0.95);
            transition: transform 0.25s ease;
        }
        
        .modal.show .modal-content {
            transform: scale(1);
        }

        /* Prevent body scrolling when modal is open */
        body.modal-open {
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <svg class="h-8 w-8 text-secondary" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    <path d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998a12.078 12.078 0 01.665-6.479L12 14zm-8 6h16"/>
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
    <div class="container mx-auto px-4 py-8">
        <!-- Course Management Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Course Management</h1>
            <p class="text-gray-600 dark:text-gray-400">View and manage all courses in the system.</p>
        </div>
        
        <!-- Filters Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Department Filter -->
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                    <select id="department" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-secondary focus:border-secondary bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">All Departments</option>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Biology">Biology</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="Physics">Physics</option>
                        <option value="English">English</option>
                    </select>
                </div>
                
                <!-- Semester Filter -->
                <div>
                    <label for="semester" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Semester</label>
                    <select id="semester" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-secondary focus:border-secondary bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">All Semesters</option>
                        <option value="Fall 2023">Fall 2023</option>
                        <option value="Spring 2024">Spring 2024</option>
                    </select>
                </div>
                
                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select id="status" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-secondary focus:border-secondary bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <div class="relative flex">
                        <input type="text" id="search" placeholder="Search courses..." class="block w-full rounded-l-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-secondary focus:border-secondary pl-10 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <button id="searchButton" class="px-4 py-2 bg-secondary text-white rounded-r-md hover:bg-opacity-90 transition-colors duration-200">
                            <span id="searchText">Search</span>
                            <svg id="searchSpinner" class="hidden animate-spin ml-1 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filter status and reset -->
            <div id="filterStatus" class="mt-4 flex items-center justify-between hidden">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="resultCount">0</span> courses found
                </p>
                <button id="resetFilters" class="text-sm text-secondary hover:text-opacity-80 flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filters
                </button>
            </div>
            
            <!-- Add New Course Button -->
            <div class="mt-4 flex justify-end">
                <a href="add_new_course.php" class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-md hover:bg-opacity-90 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add New Course
                </a>
            </div>
        </div>
        
        <!-- No Results Message -->
        <div id="noResults" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 text-center hidden">
            <svg class="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">No courses found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria</p>
            <button id="clearSearch" class="mt-4 px-4 py-2 bg-secondary text-white rounded-md hover:bg-opacity-90 transition-colors duration-200">
                Clear Search
            </button>
        </div>

        <!-- Course List Table -->
        <div id="courseTableContainer" class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Course Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Course Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Credits</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Semester</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="courseTableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Example course rows - these would be dynamically generated in PHP -->
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" data-course-id="CS101">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">CS101</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Introduction to Computer Science</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Computer Science</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">3</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Dr. John Smith</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Fall 2023</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full status-active">Active</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="view-button text-secondary hover:text-opacity-80 mr-3">View</button>
                                <button class="edit-button text-secondary hover:text-opacity-80 mr-3">Edit</button>
                                <a href="students_list.php?course=CS101" class="text-secondary hover:text-opacity-80">Students</a>
                            </td>
                        </tr>
                        
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" data-course-id="BIO201">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">BIO201</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Molecular Biology</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Biology</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">4</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Dr. Sarah Johnson</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Spring 2024</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full status-pending">Pending</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="view-button text-secondary hover:text-opacity-80 mr-3">View</button>
                                <button class="edit-button text-secondary hover:text-opacity-80 mr-3">Edit</button>
                                <a href="students_list.php?course=BIO201" class="text-secondary hover:text-opacity-80">Students</a>
                            </td>
                        </tr>
                        
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" data-course-id="MATH150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">MATH150</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Calculus I</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Mathematics</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">4</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Dr. Michael Chen</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Fall 2023</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full status-active">Active</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="view-button text-secondary hover:text-opacity-80 mr-3">View</button>
                                <button class="edit-button text-secondary hover:text-opacity-80 mr-3">Edit</button>
                                <a href="students_list.php?course=MATH150" class="text-secondary hover:text-opacity-80">Students</a>
                            </td>
                        </tr>
                        
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" data-course-id="ENG102">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">ENG102</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Introduction to Literature</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">English</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">3</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Dr. Emily Wilson</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Spring 2024</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full status-inactive">Inactive</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="view-button text-secondary hover:text-opacity-80 mr-3">View</button>
                                <button class="edit-button text-secondary hover:text-opacity-80 mr-3">Edit</button>
                                <a href="students_list.php?course=ENG102" class="text-secondary hover:text-opacity-80">Students</a>
                            </td>
                        </tr>
                        
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" data-course-id="PHYS201">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">PHYS201</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Classical Mechanics</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Physics</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">4</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Dr. Robert Thompson</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">Fall 2023</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full status-active">Active</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="view-button text-secondary hover:text-opacity-80 mr-3">View</button>
                                <button class="edit-button text-secondary hover:text-opacity-80 mr-3">Edit</button>
                                <a href="students_list.php?course=PHYS201" class="text-secondary hover:text-opacity-80">Students</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <span id="currentCount" class="font-medium">5</span> of <span id="totalCount" class="font-medium">5</span> courses
                </div>
                <div class="flex space-x-2">
                    <button id="prevButton" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50" disabled>Previous</button>
                    <button id="nextButton" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50" disabled>Next</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Course Modal -->
    <div id="viewCourseModal" class="modal fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            
            <!-- Modal panel -->
            <div class="modal-content inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="view-modal-title">
                                Course Details
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Course Code</h4>
                                        <p id="view-course-code" class="mt-1 text-sm text-gray-900 dark:text-white"></p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Course Name</h4>
                                        <p id="view-course-name" class="mt-1 text-sm text-gray-900 dark:text-white"></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</h4>
                                        <p id="view-department" class="mt-1 text-sm text-gray-900 dark:text-white"></p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Credits</h4>
                                        <p id="view-credits" class="mt-1 text-sm text-gray-900 dark:text-white"></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Instructor</h4>
                                        <p id="view-instructor" class="mt-1 text-sm text-gray-900 dark:text-white"></p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Semester</h4>
                                        <p id="view-semester" class="mt-1 text-sm text-gray-900 dark:text-white"></p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h4>
                                    <p id="view-status-container" class="mt-1">
                                        <span id="view-status" class="px-2 py-1 text-xs font-semibold rounded-full"></span>
                                    </p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</h4>
                                    <p id="view-description" class="mt-1 text-sm text-gray-900 dark:text-white">
                                        This course provides an introduction to the fundamentals of the subject. Students will learn core concepts and apply them through practical exercises and projects.
                                    </p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Schedule</h4>
                                        <p id="view-schedule" class="mt-1 text-sm text-gray-900 dark:text-white">MWF 10:00 AM - 11:30 AM</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</h4>
                                        <p id="view-location" class="mt-1 text-sm text-gray-900 dark:text-white">Science Building, Room 301</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Capacity</h4>
                                        <p id="view-capacity" class="mt-1 text-sm text-gray-900 dark:text-white">30 students</p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Prerequisites</h4>
                                    <p id="view-prerequisites" class="mt-1 text-sm text-gray-900 dark:text-white">None</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" class="view-course-close w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-secondary text-base font-medium text-white hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                    <a id="view-edit-link" href="#" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Edit This Course
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Course Modal -->
    <div id="editCourseModal" class="modal fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            
            <!-- Modal panel -->
            <div class="modal-content inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form id="editCourseForm">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="edit-modal-title">
                                    Edit Course
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="edit-course-code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Course Code</label>
                                            <input type="text" id="edit-course-code" name="course_code" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required>
                                        </div>
                                        <div>
                                            <label for="edit-course-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Course Name</label>
                                            <input type="text" id="edit-course-name" name="course_name" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="edit-department" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                                            <select id="edit-department" name="department" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary sm:text-sm dark:text-white" required>
                                                <option value="">Select Department</option>
                                                <option value="Computer Science">Computer Science</option>
                                                <option value="Biology">Biology</option>
                                                <option value="Mathematics">Mathematics</option>
                                                <option value="Physics">Physics</option>
                                                <option value="English">English</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="edit-credits" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Credits</label>
                                            <input type="number" id="edit-credits" name="credits" min="1" max="6" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="edit-instructor" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Instructor</label>
                                            <input type="text" id="edit-instructor" name="instructor" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required>
                                        </div>
                                        <div>
                                            <label for="edit-semester" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Semester</label>
                                            <select id="edit-semester" name="semester" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary sm:text-sm dark:text-white" required>
                                                <option value="">Select Semester</option>
                                                <option value="Fall 2023">Fall 2023</option>
                                                <option value="Spring 2024">Spring 2024</option>
                                                <option value="Summer 2024">Summer 2024</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="edit-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                        <select id="edit-status" name="status" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary sm:text-sm dark:text-white" required>
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                            <option value="Pending">Pending</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="edit-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                        <textarea id="edit-description" name="description" rows="3" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label for="edit-schedule" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Schedule</label>
                                            <input type="text" id="edit-schedule" name="schedule" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label for="edit-location" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                                            <input type="text" id="edit-location" name="location" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label for="edit-capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Capacity</label>
                                            <input type="number" id="edit-capacity" name="capacity" min="1" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="edit-prerequisites" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prerequisites</label>
                                        <input type="text" id="edit-prerequisites" name="prerequisites" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-secondary text-base font-medium text-white hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary sm:ml-3 sm:w-auto sm:text-sm">
                            Save Changes
                        </button>
                        <button type="button" class="edit-course-close mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="successToast" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg hidden transform transition-all duration-300 translate-y-10 opacity-0 z-50">
        Course updated successfully!
    </div>
    
    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 mt-12 py-6 border-t border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">© 2025 Online Course/Student Management System. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Search and Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const searchInput = document.getElementById('search');
            const searchButton = document.getElementById('searchButton');
            const searchText = document.getElementById('searchText');
            const searchSpinner = document.getElementById('searchSpinner');
            const departmentFilter = document.getElementById('department');
            const semesterFilter = document.getElementById('semester');
            const statusFilter = document.getElementById('status');
            const courseTableBody = document.getElementById('courseTableBody');
            const courseTableContainer = document.getElementById('courseTableContainer');
            const filterStatus = document.getElementById('filterStatus');
            const resultCount = document.getElementById('resultCount');
            const resetFilters = document.getElementById('resetFilters');
            const noResults = document.getElementById('noResults');
            const clearSearch = document.getElementById('clearSearch');
            const currentCount = document.getElementById('currentCount');
            const totalCount = document.getElementById('totalCount');
            const prevButton = document.getElementById('prevButton');
            const nextButton = document.getElementById('nextButton');
            
            // Modal elements
            const viewCourseModal = document.getElementById('viewCourseModal');
            const editCourseModal = document.getElementById('editCourseModal');
            const viewButtons = document.querySelectorAll('.view-button');
            const editButtons = document.querySelectorAll('.edit-button');
            const viewCloseButtons = document.querySelectorAll('.view-course-close');
            const editCloseButtons = document.querySelectorAll('.edit-course-close');
            const editCourseForm = document.getElementById('editCourseForm');
            const successToast = document.getElementById('successToast');
            const viewEditLink = document.getElementById('view-edit-link');
            
            // Store the original table content for resetting
            const originalTableContent = courseTableBody.innerHTML;
            const totalCourses = courseTableBody.querySelectorAll('tr').length;
            
            // Update the total count
            totalCount.textContent = totalCourses;
            
            // Course data for simulation
            const courseData = {
                'CS101': {
                    code: 'CS101',
                    name: 'Introduction to Computer Science',
                    department: 'Computer Science',
                    credits: 3,
                    instructor: 'Dr. John Smith',
                    semester: 'Fall 2023',
                    status: 'Active',
                    description: 'An introduction to the fundamental concepts of computer science including problem solving, algorithms, programming, hardware, software, and social issues.',
                    schedule: 'MWF 10:00 AM - 11:30 AM',
                    location: 'Science Building, Room 301',
                    capacity: 30,
                    prerequisites: 'None'
                },
                'BIO201': {
                    code: 'BIO201',
                    name: 'Molecular Biology',
                    department: 'Biology',
                    credits: 4,
                    instructor: 'Dr. Sarah Johnson',
                    semester: 'Spring 2024',
                    status: 'Pending',
                    description: 'Study of cell structure and function at the molecular level with emphasis on chemical components, enzyme action, gene expression, and regulation.',
                    schedule: 'TR 1:00 PM - 3:30 PM',
                    location: 'Life Sciences Building, Room 102',
                    capacity: 24,
                    prerequisites: 'BIO101, CHEM101'
                },
                'MATH150': {
                    code: 'MATH150',
                    name: 'Calculus I',
                    department: 'Mathematics',
                    credits: 4,
                    instructor: 'Dr. Michael Chen',
                    semester: 'Fall 2023',
                    status: 'Active',
                    description: 'Introduction to differential and integral calculus of functions of one variable, with applications.',
                    schedule: 'MWF 9:00 AM - 10:30 AM',
                    location: 'Mathematics Building, Room 205',
                    capacity: 35,
                    prerequisites: 'MATH120 or equivalent'
                },
                'ENG102': {
                    code: 'ENG102',
                    name: 'Introduction to Literature',
                    department: 'English',
                    credits: 3,
                    instructor: 'Dr. Emily Wilson',
                    semester: 'Spring 2024',
                    status: 'Inactive',
                    description: 'Introduction to the study of literary genres including fiction, poetry, and drama.',
                    schedule: 'TR 11:00 AM - 12:30 PM',
                    location: 'Humanities Building, Room 405',
                    capacity: 30,
                    prerequisites: 'ENG101'
                },
                'PHYS201': {
                    code: 'PHYS201',
                    name: 'Classical Mechanics',
                    department: 'Physics',
                    credits: 4,
                    instructor: 'Dr. Robert Thompson',
                    semester: 'Fall 2023',
                    status: 'Active',
                    description: 'Study of kinematics, dynamics, energy, momentum, rotation, gravitation, and oscillations.',
                    schedule: 'MWF 2:00 PM - 3:30 PM',
                    location: 'Science Building, Room 205',
                    capacity: 28,
                    prerequisites: 'PHYS101, MATH150'
                }
            };
            
            // Search function
            function searchCourses() {
                // Show spinner, hide search text
                searchText.classList.add('hidden');
                searchSpinner.classList.remove('hidden');
                
                // Get filter values
                const searchQuery = searchInput.value.toLowerCase().trim();
                const department = departmentFilter.value;
                const semester = semesterFilter.value;
                const status = statusFilter.value;
                
                // Simulate a delay for the search operation (remove in production)
                setTimeout(() => {
                    let resultsFound = 0;
                    let hasActiveFilters = searchQuery !== '' || department !== '' || semester !== '' || status !== '';
                    
                    // Process each row in the table
                    const rows = courseTableBody.querySelectorAll('tr');
                    rows.forEach(row => {
                        let showRow = true;
                        
                        // Check department filter
                        if (department !== '') {
                            const rowDepartment = row.cells[2].textContent;
                            if (rowDepartment !== department) {
                                showRow = false;
                            }
                        }
                        
                        // Check semester filter
                        if (semester !== '' && showRow) {
                            const rowSemester = row.cells[5].textContent;
                            if (rowSemester !== semester) {
                                showRow = false;
                            }
                        }
                        
                        // Check status filter
                        if (status !== '' && showRow) {
                            const rowStatus = row.cells[6].querySelector('span').textContent;
                            if (rowStatus !== status) {
                                showRow = false;
                            }
                        }
                        
                        // Check search query if not empty
                        if (searchQuery !== '' && showRow) {
                            let matchFound = false;
                            
                            // Check first 6 columns (excluding status and actions)
                            for (let i = 0; i < 6; i++) {
                                const cellText = row.cells[i].textContent.toLowerCase();
                                if (cellText.includes(searchQuery)) {
                                    matchFound = true;
                                    
                                    // Highlight the matched text if search is active
                                    if (searchQuery !== '') {
                                        const originalText = row.cells[i].textContent;
                                        const highlightedText = originalText.replace(
                                            new RegExp(searchQuery, 'gi'),
                                            match => `<span class="search-highlight">${match}</span>`
                                        );
                                        row.cells[i].innerHTML = highlightedText;
                                    }
                                }
                            }
                            
                            if (!matchFound) {
                                showRow = false;
                            }
                        }
                        
                        // Show or hide the row based on filter criteria
                        if (showRow) {
                            row.classList.remove('hidden-row');
                            resultsFound++;
                            row.classList.add('fade-in');
                            setTimeout(() => row.classList.remove('fade-in'), 300);
                        } else {
                            row.classList.add('hidden-row');
                        }
                    });
                    
                    // Update filter status and result count
                    if (hasActiveFilters) {
                        filterStatus.classList.remove('hidden');
                        resultCount.textContent = resultsFound;
                        
                        // Show "No Results" message if no courses found
                        if (resultsFound === 0) {
                            courseTableContainer.classList.add('hidden');
                            noResults.classList.remove('hidden');
                        } else {
                            courseTableContainer.classList.remove('hidden');
                            noResults.classList.add('hidden');
                        }
                    } else {
                        filterStatus.classList.add('hidden');
                        courseTableContainer.classList.remove('hidden');
                        noResults.classList.add('hidden');
                    }
                    
                    // Update the current count in pagination
                    currentCount.textContent = resultsFound;
                    
                    // Hide spinner, show search text
                    searchText.classList.remove('hidden');
                    searchSpinner.classList.add('hidden');
                }, 300); // Simulated delay of 300ms
            }
            
            // Reset filters function
            function resetAllFilters() {
                // Reset filter values
                searchInput.value = '';
                departmentFilter.value = '';
                semesterFilter.value = '';
                statusFilter.value = '';
                
                // Reset table content (remove highlights and show all rows)
                courseTableBody.innerHTML = originalTableContent;
                
                // Hide filter status
                filterStatus.classList.add('hidden');
                
                // Show table, hide no results message
                courseTableContainer.classList.remove('hidden');
                noResults.classList.add('hidden');
                
                // Reset count display
                currentCount.textContent = totalCourses;
                
                // Re-attach event listeners to new rows
                attachActionButtonListeners();
            }
            
            // Function to show modal
            function showModal(modal) {
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }
            
            // Function to hide modal
            function hideModal(modal) {
                modal.classList.remove('show');
                document.body.classList.remove('modal-open');
            }
            
            // Function to populate view course modal
            function populateViewModal(courseId) {
                const course = courseData[courseId];
                if (!course) return;
                
                document.getElementById('view-course-code').textContent = course.code;
                document.getElementById('view-course-name').textContent = course.name;
                document.getElementById('view-department').textContent = course.department;
                document.getElementById('view-credits').textContent = course.credits;
                document.getElementById('view-instructor').textContent = course.instructor;
                document.getElementById('view-semester').textContent = course.semester;
                
                const statusSpan = document.getElementById('view-status');
                statusSpan.textContent = course.status;
                statusSpan.className = `px-2 py-1 text-xs font-semibold rounded-full status-${course.status.toLowerCase()}`;
                
                document.getElementById('view-description').textContent = course.description;
                document.getElementById('view-schedule').textContent = course.schedule;
                document.getElementById('view-location').textContent = course.location;
                document.getElementById('view-capacity').textContent = `${course.capacity} students`;
                document.getElementById('view-prerequisites').textContent = course.prerequisites;
                
                // Set the edit link
                viewEditLink.setAttribute('data-course-id', courseId);
            }
            
            // Function to populate edit course modal
            function populateEditModal(courseId) {
                const course = courseData[courseId];
                if (!course) return;
                
                document.getElementById('edit-course-code').value = course.code;
                document.getElementById('edit-course-name').value = course.name;
                document.getElementById('edit-department').value = course.department;
                document.getElementById('edit-credits').value = course.credits;
                document.getElementById('edit-instructor').value = course.instructor;
                document.getElementById('edit-semester').value = course.semester;
                document.getElementById('edit-status').value = course.status;
                document.getElementById('edit-description').value = course.description;
                document.getElementById('edit-schedule').value = course.schedule;
                document.getElementById('edit-location').value = course.location;
                document.getElementById('edit-capacity').value = course.capacity;
                document.getElementById('edit-prerequisites').value = course.prerequisites;
                
                // Store the course ID for later
                editCourseForm.setAttribute('data-course-id', courseId);
            }
            
            // Function to save edited course data
            function saveEditedCourse(event) {
                event.preventDefault();
                
                const courseId = editCourseForm.getAttribute('data-course-id');
                if (!courseId || !courseData[courseId]) return;
                
                // Get form values
                const course = courseData[courseId];
                course.code = document.getElementById('edit-course-code').value;
                course.name = document.getElementById('edit-course-name').value;
                course.department = document.getElementById('edit-department').value;
                course.credits = document.getElementById('edit-credits').value;
                course.instructor = document.getElementById('edit-instructor').value;
                course.semester = document.getElementById('edit-semester').value;
                course.status = document.getElementById('edit-status').value;
                course.description = document.getElementById('edit-description').value;
                course.schedule = document.getElementById('edit-schedule').value;
                course.location = document.getElementById('edit-location').value;
                course.capacity = document.getElementById('edit-capacity').value;
                course.prerequisites = document.getElementById('edit-prerequisites').value;
                
                // Update the table row
                const row = document.querySelector(`tr[data-course-id="${courseId}"]`);
                if (row) {
                    row.cells[0].textContent = course.code;
                    row.cells[1].textContent = course.name;
                    row.cells[2].textContent = course.department;
                    row.cells[3].textContent = course.credits;
                    row.cells[4].textContent = course.instructor;
                    row.cells[5].textContent = course.semester;
                    
                    const statusSpan = row.cells[6].querySelector('span');
                    statusSpan.textContent = course.status;
                    statusSpan.className = `px-2 py-1 text-xs font-semibold rounded-full status-${course.status.toLowerCase()}`;
                }
                
                // Hide the modal
                hideModal(editCourseModal);
                
                // Show success toast
                successToast.textContent = 'Course updated successfully!';
                successToast.classList.remove('hidden', 'translate-y-10', 'opacity-0');
                
                // Auto hide toast after 3 seconds
                setTimeout(() => {
                    successToast.classList.add('translate-y-10', 'opacity-0');
                    setTimeout(() => {
                        successToast.classList.add('hidden');
                    }, 300);
                }, 3000);
            }
            
            // Attach event listeners to action buttons
            function attachActionButtonListeners() {
                // View buttons
                document.querySelectorAll('.view-button').forEach(button => {
                    button.addEventListener('click', function() {
                        const courseId = this.closest('tr').getAttribute('data-course-id');
                        populateViewModal(courseId);
                        showModal(viewCourseModal);
                    });
                });
                
                // Edit buttons
                document.querySelectorAll('.edit-button').forEach(button => {
                    button.addEventListener('click', function() {
                        const courseId = this.closest('tr').getAttribute('data-course-id');
                        populateEditModal(courseId);
                        showModal(editCourseModal);
                    });
                });
            }
            
            // Event listeners for search and filter
            searchButton.addEventListener('click', searchCourses);
            
            // Search on Enter key
            searchInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    searchCourses();
                }
            });
            
            // Filter changes
            departmentFilter.addEventListener('change', searchCourses);
            semesterFilter.addEventListener('change', searchCourses);
            statusFilter.addEventListener('change', searchCourses);
            
            // Reset filters
            resetFilters.addEventListener('click', resetAllFilters);
            clearSearch.addEventListener('click', resetAllFilters);
            
            // Modal close buttons
            viewCloseButtons.forEach(button => {
                button.addEventListener('click', () => hideModal(viewCourseModal));
            });
            
            editCloseButtons.forEach(button => {
                button.addEventListener('click', () => hideModal(editCourseModal));
            });
            
            // Modal backdrop clicks
            viewCourseModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    hideModal(viewCourseModal);
                }
            });
            
            editCourseModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    hideModal(editCourseModal);
                }
            });
            
            // Edit course form submission
            editCourseForm.addEventListener('submit', saveEditedCourse);
            
            // Edit link in view modal
            viewEditLink.addEventListener('click', function(e) {
                e.preventDefault();
                const courseId = this.getAttribute('data-course-id');
                hideModal(viewCourseModal);
                populateEditModal(courseId);
                showModal(editCourseModal);
            });
            
            // Initial setup
            attachActionButtonListeners();
        });
    </script>
</body>
</html>