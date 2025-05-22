<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Course - OCSMS</title>
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
        
        input:focus, select:focus, textarea:focus {
            outline: 2px solid #5D5CDE;
            outline-offset: -1px;
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
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="course_list.php" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-secondary dark:hover:text-secondary">
                <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Course List
            </a>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Add New Course</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Fill in the details to add a new course to the system.</p>
            </div>
            
            <form action="course_list.php" method="post" class="px-6 py-4 space-y-6">
                <!-- Course Code and Name -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="course_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Course Code*</label>
                        <input type="text" name="course_code" id="course_code" required placeholder="e.g. CS101" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                    </div>
                    <div>
                        <label for="course_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Course Name*</label>
                        <input type="text" name="course_name" id="course_name" required placeholder="e.g. Introduction to Computer Science" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                    </div>
                </div>
                
                <!-- Department and Credits -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department*</label>
                        <select name="department" id="department" required class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                            <option value="">Select Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Biology">Biology</option>
                            <option value="Physics">Physics</option>
                            <option value="Chemistry">Chemistry</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Business">Business</option>
                            <option value="Arts">Arts</option>
                            <option value="Humanities">Humanities</option>
                        </select>
                    </div>
                    <div>
                        <label for="credits" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Credits*</label>
                        <input type="number" name="credits" id="credits" required min="1" max="6" value="3" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                    </div>
                </div>
                
                <!-- Instructor and Semester -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="instructor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Instructor*</label>
                        <input type="text" name="instructor" id="instructor" required placeholder="e.g. Dr. John Smith" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                    </div>
                    <div>
                        <label for="semester" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Semester*</label>
                        <select name="semester" id="semester" required class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                            <option value="">Select Semester</option>
                            <option value="Fall 2023">Fall 2023</option>
                            <option value="Spring 2024">Spring 2024</option>
                            <option value="Summer 2024">Summer 2024</option>
                            <option value="Fall 2024">Fall 2024</option>
                        </select>
                    </div>
                </div>
                
                <!-- Status and Capacity -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status*</label>
                        <select name="status" id="status" required class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                    <div>
                        <label for="capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Capacity</label>
                        <input type="number" name="capacity" id="capacity" min="1" max="500" value="30" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                    </div>
                </div>
                
                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Course Description</label>
                    <textarea name="description" id="description" rows="4" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base" placeholder="Enter a description of the course..."></textarea>
                </div>
                
                <!-- Prerequisites -->
                <div>
                    <label for="prerequisites" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prerequisites</label>
                    <input type="text" name="prerequisites" id="prerequisites" placeholder="e.g. CS100, MATH101" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Separate multiple prerequisites with commas</p>
                </div>
                
                <!-- Schedule Information -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Days</label>
                        <select name="days" id="days" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                            <option value="">Select Days</option>
                            <option value="MWF">Monday, Wednesday, Friday</option>
                            <option value="TR">Tuesday, Thursday</option>
                            <option value="MW">Monday, Wednesday</option>
                            <option value="F">Friday only</option>
                        </select>
                    </div>
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time</label>
                        <input type="time" name="start_time" id="start_time" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time</label>
                        <input type="time" name="end_time" id="end_time" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base">
                    </div>
                </div>
                
                <!-- Submit and Cancel Buttons -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <a href="course_list.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-gray-400 text-sm font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-secondary text-white rounded-md hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary text-sm font-medium">
                        Add Course
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 mt-12 py-6 border-t border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">© 2025 Online Course/Student Management System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                // Basic validation
                const courseCode = document.getElementById('course_code').value.trim();
                const courseName = document.getElementById('course_name').value.trim();
                const department = document.getElementById('department').value;
                const instructor = document.getElementById('instructor').value.trim();
                const semester = document.getElementById('semester').value;
                
                // Check if required fields are filled
                if (!courseCode || !courseName || !department || !instructor || !semester) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return;
                }
                
                // Course code validation (alphanumeric with optional hyphen)
                const codeRegex = /^[A-Z0-9]+-?[A-Z0-9]+$/;
                if (!codeRegex.test(courseCode)) {
                    e.preventDefault();
                    alert('Course code should be in a valid format (e.g., CS101, BIO-201).');
                    return;
                }
                
                // Time validation if both start and end times are provided
                const startTime = document.getElementById('start_time').value;
                const endTime = document.getElementById('end_time').value;
                
                if (startTime && endTime) {
                    if (startTime >= endTime) {
                        e.preventDefault();
                        alert('End time must be after start time.');
                        return;
                    }
                }
                
                // Successful validation - could add additional code here if needed
                // For example, to show a loading indicator before form submission
            });
            
            // Optional: Preview mode to show how the course will appear in the list
            const updatePreview = function() {
                // Code for live preview if needed
            };
            
            // Add event listeners to form fields if preview functionality is desired
            // const formFields = form.querySelectorAll('input, select, textarea');
            // formFields.forEach(field => {
            //     field.addEventListener('change', updatePreview);
            //     field.addEventListener('keyup', updatePreview);
            // });
        });
    </script>
</body>
</html>