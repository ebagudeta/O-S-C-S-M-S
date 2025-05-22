<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Program Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#5D5CDE',
                    }
                }
            },
            darkMode: 'class'
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <!-- Check for dark mode -->
    <script>
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

    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <h1 class="text-3xl font-bold mb-6 text-primary">Academic Program Management System</h1>
        
        <div class="mb-6 grid gap-4 md:grid-cols-3">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-2">Total Colleges</h2>
                <p class="text-3xl font-bold text-primary" id="collegeCount">0</p>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-2">Total Departments</h2>
                <p class="text-3xl font-bold text-primary" id="departmentCount">0</p>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-2">Total Programs</h2>
                <p class="text-3xl font-bold text-primary" id="programCount">0</p>
            </div>
        </div>

        <!-- Program Management Tab -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 overflow-hidden">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold">Program Management</h2>
                <button id="addProgramBtn" class="bg-primary hover:bg-primary/90 text-white py-2 px-4 rounded flex items-center">
                    <i class="fas fa-plus mr-2"></i> Add Program
                </button>
            </div>
            
            <div class="p-4">
                <div class="flex flex-col md:flex-row justify-between mb-4 gap-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Filter by College:</label>
                            <select id="collegeFilter" class="w-full md:w-48 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base">
                                <option value="0">All Colleges</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Filter by Department:</label>
                            <select id="departmentFilter" class="w-full md:w-48 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base">
                                <option value="0">All Departments</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Search:</label>
                        <input type="text" id="searchInput" placeholder="Search programs..." class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base">
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="py-2 px-4 border-b text-left">ID</th>
                                <th class="py-2 px-4 border-b text-left">Program Name</th>
                                <th class="py-2 px-4 border-b text-left">Program Code</th>
                                <th class="py-2 px-4 border-b text-left">Department</th>
                                <th class="py-2 px-4 border-b text-left">College</th>
                                <th class="py-2 px-4 border-b text-left">Credit Hours</th>
                                <th class="py-2 px-4 border-b text-left">Duration (Years)</th>
                                <th class="py-2 px-4 border-b text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="programTableBody">
                            <!-- Programs will be populated here -->
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 flex justify-between items-center">
                    <div>
                        <span id="totalRecords" class="text-sm">Showing 0 records</span>
                    </div>
                    <div>
                        <button id="prevPageBtn" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 py-1 px-3 rounded mr-2 disabled:opacity-50">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span id="pageInfo" class="text-sm">Page 1 of 1</span>
                        <button id="nextPageBtn" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 py-1 px-3 rounded ml-2 disabled:opacity-50">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Add/Edit Program -->
    <div id="programModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-2xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-xl font-semibold">Add New Program</h2>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="programForm">
                <input type="hidden" id="programId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="programName" class="block text-sm font-medium mb-1">Program Name <span class="text-red-500">*</span></label>
                        <input type="text" id="programName" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base">
                    </div>
                    
                    <div>
                        <label for="programCode" class="block text-sm font-medium mb-1">Program Code <span class="text-red-500">*</span></label>
                        <input type="text" id="programCode" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base">
                    </div>
                    
                    <div>
                        <label for="departmentSelect" class="block text-sm font-medium mb-1">Department <span class="text-red-500">*</span></label>
                        <select id="departmentSelect" required class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base">
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="collegeDisplay" class="block text-sm font-medium mb-1">College</label>
                        <input type="text" id="collegeDisplay" readonly class="w-full bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-700 rounded p-2 text-base">
                    </div>
                    
                    <div>
                        <label for="creditHours" class="block text-sm font-medium mb-1">Credit Hours <span class="text-red-500">*</span></label>
                        <input type="number" id="creditHours" required min="0" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base">
                    </div>
                    
                    <div>
                        <label for="durationYears" class="block text-sm font-medium mb-1">Duration (Years) <span class="text-red-500">*</span></label>
                        <input type="number" id="durationYears" required min="0" step="0.5" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base">
                    </div>
                </div>
                
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" id="cancelBtn" class="bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 py-2 px-4 rounded">
                        Cancel
                    </button>
                    <button type="submit" class="bg-primary hover:bg-primary/90 text-white py-2 px-4 rounded">
                        <i id="saveIcon" class="fas fa-save mr-2"></i> <span id="saveText">Save Program</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <div id="confirmDialog" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
            <h2 class="text-xl font-semibold mb-4">Confirm Delete</h2>
            <p id="confirmMessage" class="mb-6">Are you sure you want to delete this program?</p>
            
            <div class="flex justify-end gap-2">
                <button id="cancelDelete" class="bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 py-2 px-4 rounded">
                    Cancel
                </button>
                <button id="confirmDelete" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 right-4 p-4 rounded-lg shadow-lg transform transition-transform duration-300 translate-y-[-100px] z-50">
        <div class="flex items-center">
            <i id="toastIcon" class="fas fa-check-circle mr-2"></i>
            <span id="toastMessage"></span>
        </div>
    </div>

    <script>
        // Data from the database screenshots
        const colleges = [
            {id: 1, name: "College of Engineering and Technology", code: "CET", description: "Engineering and technology focused college"},
            {id: 2, name: "College of Health Science", code: "CHS", description: "Health science focused college"},
            {id: 3, name: "College of Social Science", code: "CSS", description: "Social science focused college"},
            {id: 4, name: "College of Natural Science", code: "CNS", description: "Natural science focused college"}
        ];

        const departments = [
            {id: 1, name: "Information Technology", code: "IT", college_id: 1},
            {id: 2, name: "Computer Science", code: "CS", college_id: 1},
            {id: 3, name: "Comm", code: "CTM", college_id: 1},
            {id: 4, name: "Civil Engineering", code: "CE", college_id: 1},
            {id: 5, name: "Pharmacy", code: "PHR", college_id: 2},
            {id: 6, name: "Nurse", code: "NRS", college_id: 2},
            {id: 7, name: "Health Informatics", code: "HI", college_id: 2},
            {id: 8, name: "Mid Wifery", code: "MW", college_id: 2},
            {id: 9, name: "Economics", code: "ECO", college_id: 3},
            {id: 10, name: "Accounting", code: "ACC", college_id: 3},
            {id: 11, name: "Geography", code: "GEO", college_id: 3},
            {id: 12, name: "Afan Oromo", code: "AO", college_id: 3},
            {id: 13, name: "Sport Science", code: "SS", college_id: 4},
            {id: 14, name: "Biology", code: "BIO", college_id: 4},
            {id: 15, name: "Physics", code: "PHY", college_id: 4},
            {id: 16, name: "Chemistry", code: "CHM", college_id: 4}
        ];

        let programs = [
            {id: 1, name: "Computer Science", code: "CS-BSC", department_id: 2, credit_hours: 120, duration_years: 4.0},
            {id: 2, name: "Mathematics", code: "MATH-BSC", department_id: 14, credit_hours: 120, duration_years: 4.0},
            {id: 3, name: "English Literature", code: "ENG-BA", department_id: 9, credit_hours: 120, duration_years: 4.0},
            {id: 4, name: "Biology", code: "BIO-BSC", department_id: 14, credit_hours: 124, duration_years: 4.0},
            {id: 5, name: "Chemistry", code: "CHEM-BSC", department_id: 16, credit_hours: 124, duration_years: 4.0}
        ];

        // Application state
        let currentPage = 1;
        const recordsPerPage = 10;
        let filteredPrograms = [...programs];
        let selectedProgramId = null;
        let deleteConfirmCallback = null;

        // Initialize the application when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            // Update counts
            updateCounts();
            
            // Populate filters
            populateCollegeFilter();
            populateDepartmentFilter();
            
            // Load initial data
            filterAndDisplayPrograms();
            
            // Set up event listeners
            setupEventListeners();
            
            // Populate department select for the form
            populateDepartmentSelect();
        });

        function updateCounts() {
            document.getElementById('collegeCount').textContent = colleges.length;
            document.getElementById('departmentCount').textContent = departments.length;
            document.getElementById('programCount').textContent = programs.length;
        }

        function populateCollegeFilter() {
            const collegeFilter = document.getElementById('collegeFilter');
            
            colleges.forEach(college => {
                const option = document.createElement('option');
                option.value = college.id;
                option.textContent = college.name;
                collegeFilter.appendChild(option);
            });
        }

        function populateDepartmentFilter() {
            const departmentFilter = document.getElementById('departmentFilter');
            
            departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.id;
                option.textContent = dept.name;
                departmentFilter.appendChild(option);
            });
        }

        function populateDepartmentSelect() {
            const departmentSelect = document.getElementById('departmentSelect');
            // Clear existing options except the first one
            departmentSelect.innerHTML = '<option value="">Select Department</option>';
            
            // Group departments by college
            const collegeGroups = {};
            departments.forEach(dept => {
                const collegeId = dept.college_id;
                if (!collegeGroups[collegeId]) {
                    const college = colleges.find(c => c.id === collegeId);
                    collegeGroups[collegeId] = {
                        college: college,
                        departments: []
                    };
                }
                collegeGroups[collegeId].departments.push(dept);
            });
            
            // Create option groups for each college
            Object.values(collegeGroups).forEach(group => {
                const optgroup = document.createElement('optgroup');
                optgroup.label = group.college.name;
                
                group.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = `${dept.name} (${dept.code})`;
                    option.dataset.collegeId = dept.college_id;
                    optgroup.appendChild(option);
                });
                
                departmentSelect.appendChild(optgroup);
            });
        }

        function setupEventListeners() {
            // Filter change events
            document.getElementById('collegeFilter').addEventListener('change', function() {
                // If college filter changes, update department filter to show only departments from that college
                const collegeId = parseInt(this.value);
                const departmentFilter = document.getElementById('departmentFilter');
                
                // Reset department filter
                departmentFilter.innerHTML = '<option value="0">All Departments</option>';
                
                if (collegeId > 0) {
                    // Filter departments by college
                    const filteredDepts = departments.filter(dept => dept.college_id === collegeId);
                    filteredDepts.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        departmentFilter.appendChild(option);
                    });
                } else {
                    // Show all departments
                    populateDepartmentFilter();
                }
                
                filterAndDisplayPrograms();
            });
            
            document.getElementById('departmentFilter').addEventListener('change', filterAndDisplayPrograms);
            document.getElementById('searchInput').addEventListener('input', filterAndDisplayPrograms);
            
            // Pagination buttons
            document.getElementById('prevPageBtn').addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    displayPrograms();
                }
            });
            
            document.getElementById('nextPageBtn').addEventListener('click', () => {
                const totalPages = Math.ceil(filteredPrograms.length / recordsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    displayPrograms();
                }
            });
            
            // Add Program button
            document.getElementById('addProgramBtn').addEventListener('click', () => {
                openModal('add');
            });
            
            // Modal close events
            document.getElementById('closeModal').addEventListener('click', closeModal);
            document.getElementById('cancelBtn').addEventListener('click', closeModal);
            
            // Form submission
            document.getElementById('programForm').addEventListener('submit', function(e) {
                e.preventDefault();
                saveProgram();
            });
            
            // Department select change
            document.getElementById('departmentSelect').addEventListener('change', function() {
                updateCollegeDisplay();
            });
            
            // Confirmation dialog
            document.getElementById('cancelDelete').addEventListener('click', () => {
                document.getElementById('confirmDialog').classList.add('hidden');
            });
            
            document.getElementById('confirmDelete').addEventListener('click', () => {
                if (deleteConfirmCallback) {
                    deleteConfirmCallback();
                    document.getElementById('confirmDialog').classList.add('hidden');
                }
            });
        }

        function filterAndDisplayPrograms() {
            const collegeId = parseInt(document.getElementById('collegeFilter').value);
            const departmentId = parseInt(document.getElementById('departmentFilter').value);
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            // Reset to first page when filters change
            currentPage = 1;
            
            // Filter programs
            filteredPrograms = programs.filter(program => {
                // Get department and college info
                const dept = departments.find(d => d.id === program.department_id);
                const college = dept ? colleges.find(c => c.id === dept.college_id) : null;
                
                // Check college filter
                if (collegeId > 0 && dept && dept.college_id !== collegeId) {
                    return false;
                }
                
                // Check department filter
                if (departmentId > 0 && program.department_id !== departmentId) {
                    return false;
                }
                
                // Check search term
                if (searchTerm) {
                    const nameMatch = program.name.toLowerCase().includes(searchTerm);
                    const codeMatch = program.code.toLowerCase().includes(searchTerm);
                    const deptMatch = dept ? dept.name.toLowerCase().includes(searchTerm) : false;
                    const collegeMatch = college ? college.name.toLowerCase().includes(searchTerm) : false;
                    
                    return nameMatch || codeMatch || deptMatch || collegeMatch;
                }
                
                return true;
            });
            
            // Display filtered programs
            displayPrograms();
        }

        function displayPrograms() {
            const tbody = document.getElementById('programTableBody');
            tbody.innerHTML = '';
            
            // Calculate pagination
            const totalPages = Math.ceil(filteredPrograms.length / recordsPerPage);
            const startIndex = (currentPage - 1) * recordsPerPage;
            const endIndex = Math.min(startIndex + recordsPerPage, filteredPrograms.length);
            
            // Update pagination info
            document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages || 1}`;
            document.getElementById('prevPageBtn').disabled = currentPage === 1;
            document.getElementById('nextPageBtn').disabled = currentPage === totalPages || totalPages === 0;
            document.getElementById('totalRecords').textContent = `Showing ${filteredPrograms.length} records`;
            
            // Display current page records
            const recordsToDisplay = filteredPrograms.slice(startIndex, endIndex);
            
            if (recordsToDisplay.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td colspan="8" class="py-4 px-4 text-center">No programs found</td>`;
                tbody.appendChild(tr);
                return;
            }
            
            recordsToDisplay.forEach(program => {
                const dept = departments.find(d => d.id === program.department_id);
                const college = dept ? colleges.find(c => c.id === dept.college_id) : { name: 'Unknown' };
                
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-100 dark:hover:bg-gray-700';
                
                tr.innerHTML = `
                    <td class="py-2 px-4 border-b">${program.id}</td>
                    <td class="py-2 px-4 border-b">${program.name}</td>
                    <td class="py-2 px-4 border-b">${program.code}</td>
                    <td class="py-2 px-4 border-b">${dept ? dept.name : 'Unknown'}</td>
                    <td class="py-2 px-4 border-b">${college ? college.name : 'Unknown'}</td>
                    <td class="py-2 px-4 border-b">${program.credit_hours}</td>
                    <td class="py-2 px-4 border-b">${program.duration_years}</td>
                    <td class="py-2 px-4 border-b">
                        <button class="text-blue-500 hover:text-blue-700 mr-2 edit-btn" data-id="${program.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-red-500 hover:text-red-700 delete-btn" data-id="${program.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(tr);
            });
            
            // Add edit and delete event listeners
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const programId = parseInt(btn.dataset.id);
                    openModal('edit', programId);
                });
            });
            
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const programId = parseInt(btn.dataset.id);
                    confirmDelete(programId);
                });
            });
        }

        function openModal(mode, programId = null) {
            // Set modal title and button text based on mode
            document.getElementById('modalTitle').textContent = mode === 'add' ? 'Add New Program' : 'Edit Program';
            document.getElementById('saveText').textContent = mode === 'add' ? 'Save Program' : 'Update Program';
            
            // Reset form
            document.getElementById('programForm').reset();
            
            // If editing, populate form with program data
            if (mode === 'edit' && programId) {
                const program = programs.find(p => p.id === programId);
                if (program) {
                    document.getElementById('programId').value = program.id;
                    document.getElementById('programName').value = program.name;
                    document.getElementById('programCode').value = program.code;
                    document.getElementById('departmentSelect').value = program.department_id;
                    document.getElementById('creditHours').value = program.credit_hours;
                    document.getElementById('durationYears').value = program.duration_years;
                    
                    // Update college display
                    updateCollegeDisplay();
                }
            } else {
                document.getElementById('programId').value = '';
                // Clear college display
                document.getElementById('collegeDisplay').value = '';
            }
            
            // Show modal
            document.getElementById('programModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('programModal').classList.add('hidden');
        }

        function updateCollegeDisplay() {
            const departmentSelect = document.getElementById('departmentSelect');
            const collegeDisplay = document.getElementById('collegeDisplay');
            
            if (departmentSelect.value) {
                const departmentId = parseInt(departmentSelect.value);
                const department = departments.find(d => d.id === departmentId);
                
                if (department) {
                    const college = colleges.find(c => c.id === department.college_id);
                    collegeDisplay.value = college ? college.name : '';
                } else {
                    collegeDisplay.value = '';
                }
            } else {
                collegeDisplay.value = '';
            }
        }

        function saveProgram() {
            const programId = document.getElementById('programId').value;
            const name = document.getElementById('programName').value;
            const code = document.getElementById('programCode').value;
            const departmentId = parseInt(document.getElementById('departmentSelect').value);
            const creditHours = parseInt(document.getElementById('creditHours').value);
            const durationYears = parseFloat(document.getElementById('durationYears').value);
            
            if (programId) {
                // Edit existing program
                const index = programs.findIndex(p => p.id === parseInt(programId));
                if (index !== -1) {
                    programs[index] = {
                        ...programs[index],
                        name,
                        code,
                        department_id: departmentId,
                        credit_hours: creditHours,
                        duration_years: durationYears
                    };
                    
                    showToast('Program updated successfully!', 'success');
                }
            } else {
                // Add new program
                const newProgramId = Math.max(...programs.map(p => p.id), 0) + 1;
                programs.push({
                    id: newProgramId,
                    name,
                    code,
                    department_id: departmentId,
                    credit_hours: creditHours,
                    duration_years: durationYears
                });
                
                showToast('Program added successfully!', 'success');
            }
            
            // Close modal and refresh data
            closeModal();
            updateCounts();
            filterAndDisplayPrograms();
        }

        function confirmDelete(programId) {
            const program = programs.find(p => p.id === programId);
            if (!program) return;
            
            const dept = departments.find(d => d.id === program.department_id);
            const deptName = dept ? dept.name : 'Unknown Department';
            
            document.getElementById('confirmMessage').textContent = 
                `Are you sure you want to delete the program "${program.name} (${program.code})" from ${deptName}?`;
            
            deleteConfirmCallback = () => {
                deleteProgram(programId);
            };
            
            document.getElementById('confirmDialog').classList.remove('hidden');
        }

        function deleteProgram(programId) {
            const index = programs.findIndex(p => p.id === programId);
            if (index !== -1) {
                programs.splice(index, 1);
                showToast('Program deleted successfully!', 'success');
                updateCounts();
                filterAndDisplayPrograms();
            }
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');
            
            // Set message and icon
            toastMessage.textContent = message;
            
            // Set appropriate classes based on type
            toast.className = 'fixed top-4 right-4 p-4 rounded-lg shadow-lg transform transition-transform duration-300 z-50';
            
            if (type === 'success') {
                toast.classList.add('bg-green-500', 'text-white');
                toastIcon.className = 'fas fa-check-circle mr-2';
            } else if (type === 'error') {
                toast.classList.add('bg-red-500', 'text-white');
                toastIcon.className = 'fas fa-exclamation-circle mr-2';
            } else if (type === 'warning') {
                toast.classList.add('bg-yellow-500', 'text-white');
                toastIcon.className = 'fas fa-exclamation-triangle mr-2';
            } else if (type === 'info') {
                toast.classList.add('bg-blue-500', 'text-white');
                toastIcon.className = 'fas fa-info-circle mr-2';
            }
            
            // Show the toast
            toast.classList.remove('translate-y-[-100px]');
            
            // Hide after 3 seconds
            setTimeout(() => {
                toast.classList.add('translate-y-[-100px]');
            }, 3000);
        }
    </script>
</body>
</html>