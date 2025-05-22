<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Students - OCSMS</title>
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
        <!-- Course Info Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white" id="course-title">Course Students</h1>
                    <p class="text-gray-600 dark:text-gray-400" id="course-subtitle">Loading course information...</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="course_list.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="h-5 w-5 mr-2 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Courses
                    </a>
                    <button id="enroll-student-btn" class="ml-3 inline-flex items-center px-4 py-2 bg-secondary text-white rounded-md hover:bg-opacity-90 transition-colors duration-200">
                        <svg class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Enroll Student
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Course Details Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Course Information</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-900 dark:text-white"><span class="font-medium">Code:</span> <span id="course-code">-</span></p>
                        <p class="text-sm text-gray-900 dark:text-white"><span class="font-medium">Name:</span> <span id="course-name">-</span></p>
                        <p class="text-sm text-gray-900 dark:text-white"><span class="font-medium">Department:</span> <span id="course-department">-</span></p>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduling</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-900 dark:text-white"><span class="font-medium">Semester:</span> <span id="course-semester">-</span></p>
                        <p class="text-sm text-gray-900 dark:text-white"><span class="font-medium">Schedule:</span> <span id="course-schedule">-</span></p>
                        <p class="text-sm text-gray-900 dark:text-white"><span class="font-medium">Location:</span> <span id="course-location">-</span></p>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Enrollment</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-900 dark:text-white"><span class="font-medium">Instructor:</span> <span id="course-instructor">-</span></p>
                        <p class="text-sm text-gray-900 dark:text-white"><span class="font-medium">Students:</span> <span id="students-count">0</span> / <span id="course-capacity">-</span></p>
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="font-medium">Status:</span> 
                            <span id="course-status-container">
                                <span id="course-status" class="px-2 py-0.5 text-xs font-semibold rounded-full">-</span>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="status-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select id="status-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-secondary focus:border-secondary bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                
                <div>
                    <label for="grade-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grade</label>
                    <select id="grade-filter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-secondary focus:border-secondary bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">All Grades</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="F">F</option>
                        <option value="Not Graded">Not Graded</option>
                    </select>
                </div>
                
                <div>
                    <label for="student-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <div class="relative flex">
                        <input type="text" id="student-search" placeholder="Search students..." class="block w-full rounded-l-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-secondary focus:border-secondary pl-10 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <button id="search-button" class="px-4 py-2 bg-secondary text-white rounded-r-md hover:bg-opacity-90 transition-colors duration-200">
                            <span id="search-text">Search</span>
                            <svg id="search-spinner" class="hidden animate-spin ml-1 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filter status and reset -->
            <div id="filter-status" class="mt-4 flex items-center justify-between hidden">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="result-count">0</span> students found
                </p>
                <button id="reset-filters" class="text-sm text-secondary hover:text-opacity-80 flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- No Results Message -->
        <div id="no-results" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 text-center hidden">
            <svg class="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">No students found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria</p>
            <button id="clear-search" class="mt-4 px-4 py-2 bg-secondary text-white rounded-md hover:bg-opacity-90 transition-colors duration-200">
                Clear Search
            </button>
        </div>

        <!-- No Students Enrolled Message -->
        <div id="no-students-enrolled" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 text-center hidden">
            <svg class="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">No students enrolled</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">There are currently no students enrolled in this course</p>
            <button id="enroll-first-student-btn" class="mt-4 px-4 py-2 bg-secondary text-white rounded-md hover:bg-opacity-90 transition-colors duration-200">
                Enroll First Student
            </button>
        </div>

        <!-- Students List Table -->
        <div id="students-table-container" class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Student ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Enrollment Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Grade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="students-table-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Student rows will be dynamically added here -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <span id="current-count" class="font-medium">0</span> of <span id="total-count" class="font-medium">0</span> students
                </div>
                <div class="flex space-x-2">
                    <button id="prev-button" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50" disabled>Previous</button>
                    <button id="next-button" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50" disabled>Next</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Enroll Student Modal -->
    <div id="enroll-student-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="enroll-student-form">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Enroll Student
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="student-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Student</label>
                                        <select id="student-select" name="student_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary sm:text-sm dark:text-white" required>
                                            <option value="">Select a student</option>
                                            <!-- Available students will be added here -->
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="enrollment-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enrollment Date</label>
                                        <input type="date" id="enrollment-date" name="enrollment_date" class="mt-1 focus:ring-secondary focus:border-secondary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white" required>
                                    </div>
                                    
                                    <div>
                                        <label for="enrollment-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                        <select id="enrollment-status" name="status" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-secondary focus:border-secondary sm:text-sm dark:text-white" required>
                                            <option value="Active">Active</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-secondary text-base font-medium text-white hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary sm:ml-3 sm:w-auto sm:text-sm">
                            Enroll
                        </button>
                        <button type="button" id="enroll-cancel-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="success-toast" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg hidden transform transition-all duration-300 translate-y-10 opacity-0 z-50">
        Action completed successfully!
    </div>
    
    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 mt-12 py-6 border-t border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">© 2025 Online Course/Student Management System. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get course ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const courseId = urlParams.get('course');
            
            // Elements
            const courseTitle = document.getElementById('course-title');
            const courseSubtitle = document.getElementById('course-subtitle');
            const courseCode = document.getElementById('course-code');
            const courseName = document.getElementById('course-name');
            const courseDepartment = document.getElementById('course-department');
            const courseSemester = document.getElementById('course-semester');
            const courseSchedule = document.getElementById('course-schedule');
            const courseLocation = document.getElementById('course-location');
            const courseInstructor = document.getElementById('course-instructor');
            const courseCapacity = document.getElementById('course-capacity');
            const courseStatus = document.getElementById('course-status');
            const studentsCount = document.getElementById('students-count');
            const studentTableBody = document.getElementById('students-table-body');
            const studentsTableContainer = document.getElementById('students-table-container');
            const noStudentsEnrolled = document.getElementById('no-students-enrolled');
            const currentCount = document.getElementById('current-count');
            const totalCount = document.getElementById('total-count');
            
            // Search and filter elements
            const studentSearch = document.getElementById('student-search');
            const searchButton = document.getElementById('search-button');
            const statusFilter = document.getElementById('status-filter');
            const gradeFilter = document.getElementById('grade-filter');
            const filterStatus = document.getElementById('filter-status');
            const resultCount = document.getElementById('result-count');
            const resetFilters = document.getElementById('reset-filters');
            const clearSearch = document.getElementById('clear-search');
            const noResults = document.getElementById('no-results');
            
            // Modal elements
            const enrollStudentBtn = document.getElementById('enroll-student-btn');
            const enrollFirstStudentBtn = document.getElementById('enroll-first-student-btn');
            const enrollStudentModal = document.getElementById('enroll-student-modal');
            const enrollCancelBtn = document.getElementById('enroll-cancel-btn');
            const enrollStudentForm = document.getElementById('enroll-student-form');
            const studentSelect = document.getElementById('student-select');
            const enrollmentDate = document.getElementById('enrollment-date');
            const successToast = document.getElementById('success-toast');
            
            // Course data (simulated)
            const courses = {
                'CS101': {
                    code: 'CS101',
                    name: 'Introduction to Computer Science',
                    department: 'Computer Science',
                    credits: 3,
                    instructor: 'Dr. John Smith',
                    semester: 'Fall 2023',
                    status: 'Active',
                    description: 'An introduction to the fundamental concepts of computer science.',
                    schedule: 'MWF 10:00 AM - 11:30 AM',
                    location: 'Science Building, Room 301',
                    capacity: 30
                },
                'BIO201': {
                    code: 'BIO201',
                    name: 'Molecular Biology',
                    department: 'Biology',
                    credits: 4,
                    instructor: 'Dr. Sarah Johnson',
                    semester: 'Spring 2024',
                    status: 'Pending',
                    description: 'Study of cell structure and function at the molecular level.',
                    schedule: 'TR 1:00 PM - 3:30 PM',
                    location: 'Life Sciences Building, Room 102',
                    capacity: 24
                },
                'MATH150': {
                    code: 'MATH150',
                    name: 'Calculus I',
                    department: 'Mathematics',
                    credits: 4,
                    instructor: 'Dr. Michael Chen',
                    semester: 'Fall 2023',
                    status: 'Active',
                    description: 'Introduction to differential and integral calculus.',
                    schedule: 'MWF 9:00 AM - 10:30 AM',
                    location: 'Mathematics Building, Room 205',
                    capacity: 35
                }
            };
            
            // Student data for the selected course (simulated)
            const studentsByCourse = {
                'CS101': [
                    { id: 'S12345', name: 'John Doe', email: 'john.doe@student.edu', enrollmentDate: '2023-08-15', status: 'Active', grade: 'B' },
                    { id: 'S12346', name: 'Jane Smith', email: 'jane.smith@student.edu', enrollmentDate: '2023-08-20', status: 'Active', grade: 'A' },
                    { id: 'S12347', name: 'Michael Johnson', email: 'michael.j@student.edu', enrollmentDate: '2023-08-10', status: 'Active', grade: 'Not Graded' }
                ],
                'BIO201': [
                    { id: 'S12348', name: 'Emily Davis', email: 'emily.d@student.edu', enrollmentDate: '2023-12-05', status: 'Pending', grade: 'Not Graded' },
                    { id: 'S12349', name: 'Robert Wilson', email: 'robert.w@student.edu', enrollmentDate: '2023-12-10', status: 'Pending', grade: 'Not Graded' }
                ],
                'MATH150': [
                    { id: 'S12350', name: 'Sarah Brown', email: 'sarah.b@student.edu', enrollmentDate: '2023-08-18', status: 'Active', grade: 'A' },
                    { id: 'S12351', name: 'David Lee', email: 'david.l@student.edu', enrollmentDate: '2023-08-22', status: 'Inactive', grade: 'F' },
                    { id: 'S12352', name: 'Jessica Wang', email: 'jessica.w@student.edu', enrollmentDate: '2023-08-15', status: 'Active', grade: 'B' },
                    { id: 'S12353', name: 'Thomas Jackson', email: 'thomas.j@student.edu', enrollmentDate: '2023-08-30', status: 'Active', grade: 'C' }
                ]
            };
            
            // Available students for enrollment (simulated)
            const availableStudents = [
                { id: 'S12354', name: 'Lisa Martinez', email: 'lisa.m@student.edu' },
                { id: 'S12355', name: 'James Johnson', email: 'james.j@student.edu' },
                { id: 'S12356', name: 'Michelle Kim', email: 'michelle.k@student.edu' },
                { id: 'S12357', name: 'Kevin Patel', email: 'kevin.p@student.edu' },
                { id: 'S12358', name: 'Jennifer Lee', email: 'jennifer.l@student.edu' }
            ];
            
            // Load course information
            function loadCourseInfo() {
                // Check if course ID is valid
                if (!courseId || !courses[courseId]) {
                    showErrorState();
                    return;
                }
                
                const course = courses[courseId];
                
                // Update course title and subtitle
                courseTitle.textContent = `${course.code}: ${course.name} - Students`;
                courseSubtitle.textContent = `Manage students enrolled in ${course.name}`;
                
                // Update course details
                courseCode.textContent = course.code;
                courseName.textContent = course.name;
                courseDepartment.textContent = course.department;
                courseSemester.textContent = course.semester;
                courseSchedule.textContent = course.schedule;
                courseLocation.textContent = course.location;
                courseInstructor.textContent = course.instructor;
                courseCapacity.textContent = course.capacity;
                
                // Update course status
                courseStatus.textContent = course.status;
                courseStatus.className = `px-2 py-0.5 text-xs font-semibold rounded-full status-${course.status.toLowerCase()}`;
                
                // Load students
                const students = studentsByCourse[courseId] || [];
                loadStudents(students);
                
                // Update enrollment count
                studentsCount.textContent = students.length;
            }
            
            // Show error state when course is not found
            function showErrorState() {
                courseTitle.textContent = 'Course Not Found';
                courseSubtitle.textContent = 'The requested course could not be found';
                
                // Hide student table and show error message
                studentsTableContainer.classList.add('hidden');
                
                // Create and show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 text-center';
                errorDiv.innerHTML = `
                    <svg class="h-12 w-12 mx-auto text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">Course Not Found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The course ID "${courseId}" does not exist or you don't have permission to view it.</p>
                    <a href="course_list.php" class="mt-4 inline-block px-4 py-2 bg-secondary text-white rounded-md hover:bg-opacity-90 transition-colors duration-200">
                        Back to Course List
                    </a>
                `;
                
                // Insert error message after header
                const header = document.querySelector('.bg-white.dark\\:bg-gray-800.rounded-lg.shadow-md.p-6.mb-6');
                header.insertAdjacentElement('afterend', errorDiv);
            }
            
            // Load students for the course
            function loadStudents(students) {
                // Clear the table
                studentTableBody.innerHTML = '';
                
                // Update counts
                const totalStudents = students.length;
                totalCount.textContent = totalStudents;
                currentCount.textContent = totalStudents;
                
                // Check if there are no students
                if (totalStudents === 0) {
                    studentsTableContainer.classList.add('hidden');
                    noStudentsEnrolled.classList.remove('hidden');
                    return;
                }
                
                // Show table, hide no students message
                studentsTableContainer.classList.remove('hidden');
                noStudentsEnrolled.classList.add('hidden');
                
                // Add students to table
                students.forEach(student => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50 dark:hover:bg-gray-700';
                    row.setAttribute('data-student-id', student.id);
                    
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${student.id}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">${student.name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">${student.email}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">${student.enrollmentDate}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full status-${student.status.toLowerCase()}">${student.status}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">${student.grade}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="edit-grade-btn text-secondary hover:text-opacity-80 mr-3">Edit Grade</button>
                            <button class="unenroll-btn text-red-500 hover:text-opacity-80">Unenroll</button>
                        </td>
                    `;
                    
                    studentTableBody.appendChild(row);
                });
                
                // Attach event listeners to action buttons
                attachActionListeners();
            }
            
            // Populate available students for enrollment
            function populateAvailableStudents() {
                // Clear the select
                studentSelect.innerHTML = '<option value="">Select a student</option>';
                
                // Add available students
                availableStudents.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = `${student.name} (${student.id})`;
                    studentSelect.appendChild(option);
                });
                
                // Set today's date as default
                const today = new Date().toISOString().split('T')[0];
                enrollmentDate.value = today;
            }
            
            // Attach event listeners to action buttons
            function attachActionListeners() {
                // Edit grade buttons
                document.querySelectorAll('.edit-grade-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const studentId = this.closest('tr').getAttribute('data-student-id');
                        const studentName = this.closest('tr').cells[1].textContent;
                        
                        // Simple prompt for grade (in a real app, you'd use a modal)
                        const newGrade = prompt(`Enter new grade for ${studentName}:`, 'A');
                        
                        if (newGrade !== null) {
                            // Update grade in the table
                            this.closest('tr').cells[5].textContent = newGrade;
                            
                            // Show success toast
                            showSuccessToast(`Grade updated for ${studentName}`);
                        }
                    });
                });
                
                // Unenroll buttons
                document.querySelectorAll('.unenroll-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const studentId = this.closest('tr').getAttribute('data-student-id');
                        const studentName = this.closest('tr').cells[1].textContent;
                        
                        if (confirm(`Are you sure you want to unenroll ${studentName} from this course?`)) {
                            // Remove the row
                            const row = this.closest('tr');
                            row.remove();
                            
                            // Update student count
                            const currentStudents = parseInt(studentsCount.textContent, 10) - 1;
                            studentsCount.textContent = currentStudents;
                            currentCount.textContent = currentStudents;
                            totalCount.textContent = currentStudents;
                            
                            // Check if there are no students left
                            if (currentStudents === 0) {
                                studentsTableContainer.classList.add('hidden');
                                noStudentsEnrolled.classList.remove('hidden');
                            }
                            
                            // Show success toast
                            showSuccessToast(`${studentName} has been unenrolled`);
                        }
                    });
                });
            }
            
            // Search function
            function searchStudents() {
                const searchText = studentSearch.value.toLowerCase().trim();
                const statusValue = statusFilter.value;
                const gradeValue = gradeFilter.value;
                
                // Get all rows
                const rows = studentTableBody.querySelectorAll('tr');
                let visibleRows = 0;
                
                // Check if there are any filtering criteria
                const hasFilters = searchText !== '' || statusValue !== '' || gradeValue !== '';
                
                rows.forEach(row => {
                    let visible = true;
                    
                    // Check search text
                    if (searchText !== '') {
                        let found = false;
                        // Check in first 3 columns (ID, Name, Email)
                        for (let i = 0; i < 3; i++) {
                            if (row.cells[i].textContent.toLowerCase().includes(searchText)) {
                                found = true;
                                break;
                            }
                        }
                        visible = found;
                    }
                    
                    // Check status filter
                    if (visible && statusValue !== '') {
                        const status = row.cells[4].querySelector('span').textContent;
                        visible = status === statusValue;
                    }
                    
                    // Check grade filter
                    if (visible && gradeValue !== '') {
                        const grade = row.cells[5].textContent;
                        visible = grade === gradeValue;
                    }
                    
                    // Show or hide row
                    if (visible) {
                        row.classList.remove('hidden-row');
                        visibleRows++;
                    } else {
                        row.classList.add('hidden-row');
                    }
                });
                
                // Update filter status and counts
                if (hasFilters) {
                    filterStatus.classList.remove('hidden');
                    resultCount.textContent = visibleRows;
                    currentCount.textContent = visibleRows;
                    
                    // Show no results message if needed
                    if (visibleRows === 0) {
                        studentsTableContainer.classList.add('hidden');
                        noResults.classList.remove('hidden');
                    } else {
                        studentsTableContainer.classList.remove('hidden');
                        noResults.classList.add('hidden');
                    }
                } else {
                    filterStatus.classList.add('hidden');
                    // Reset count to total
                    const totalStudents = studentsByCourse[courseId]?.length || 0;
                    currentCount.textContent = totalStudents;
                    
                    // Show table if there are students
                    if (totalStudents > 0) {
                        studentsTableContainer.classList.remove('hidden');
                        noResults.classList.add('hidden');
                    } else {
                        studentsTableContainer.classList.add('hidden');
                        noStudentsEnrolled.classList.remove('hidden');
                    }
                }
            }
            
            // Reset filters
            function resetFilters() {
                studentSearch.value = '';
                statusFilter.value = '';
                gradeFilter.value = '';
                
                // Reset table
                const rows = studentTableBody.querySelectorAll('tr');
                rows.forEach(row => row.classList.remove('hidden-row'));
                
                // Hide filter status
                filterStatus.classList.add('hidden');
                
                // Reset count to total
                const totalStudents = studentsByCourse[courseId]?.length || 0;
                currentCount.textContent = totalStudents;
                
                // Show table if there are students
                if (totalStudents > 0) {
                    studentsTableContainer.classList.remove('hidden');
                    noResults.classList.add('hidden');
                } else {
                    studentsTableContainer.classList.add('hidden');
                    noStudentsEnrolled.classList.remove('hidden');
                }
            }
            
            // Show modal
            function showModal(modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
            
            // Hide modal
            function hideModal(modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
            
            // Show success toast
            function showSuccessToast(message) {
                successToast.textContent = message;
                successToast.classList.remove('hidden', 'translate-y-10', 'opacity-0');
                
                setTimeout(() => {
                    successToast.classList.add('translate-y-10', 'opacity-0');
                    setTimeout(() => {
                        successToast.classList.add('hidden');
                    }, 300);
                }, 3000);
            }
            
            // Handle enroll student form submission
            function handleEnrollStudent(e) {
                e.preventDefault();
                
                const studentId = studentSelect.value;
                const enrollDate = enrollmentDate.value;
                const status = document.getElementById('enrollment-status').value;
                
                // Validate form
                if (!studentId || !enrollDate) {
                    alert('Please fill all required fields');
                    return;
                }
                
                // Find the selected student
                const student = availableStudents.find(s => s.id === studentId);
                if (!student) return;
                
                // Create new student enrollment
                const newStudent = {
                    id: student.id,
                    name: student.name,
                    email: student.email,
                    enrollmentDate: enrollDate,
                    status: status,
                    grade: 'Not Graded'
                };
                
                // Add to course students
                if (!studentsByCourse[courseId]) {
                    studentsByCourse[courseId] = [];
                }
                studentsByCourse[courseId].push(newStudent);
                
                // Update students table
                loadStudents(studentsByCourse[courseId]);
                
                // Update student count
                studentsCount.textContent = studentsByCourse[courseId].length;
                
                // Hide modal
                hideModal(enrollStudentModal);
                
                // Show success toast
                showSuccessToast(`${student.name} has been enrolled successfully`);
            }
            
            // Event listeners
            searchButton.addEventListener('click', searchStudents);
            studentSearch.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') searchStudents();
            });
            statusFilter.addEventListener('change', searchStudents);
            gradeFilter.addEventListener('change', searchStudents);
            resetFilters.addEventListener('click', resetFilters);
            clearSearch.addEventListener('click', resetFilters);
            
            // Modal event listeners
            enrollStudentBtn.addEventListener('click', function() {
                populateAvailableStudents();
                showModal(enrollStudentModal);
            });
            
            enrollFirstStudentBtn.addEventListener('click', function() {
                populateAvailableStudents();
                showModal(enrollStudentModal);
            });
            
            enrollCancelBtn.addEventListener('click', function() {
                hideModal(enrollStudentModal);
            });
            
            enrollStudentForm.addEventListener('submit', handleEnrollStudent);
            
            // Initialize page
            loadCourseInfo();
        });
    </script>
</body>
</html>