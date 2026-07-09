// ================================================
// MOBILE MENU FUNCTIONALITY
// ================================================

// Toggle mobile sidebar
function toggleMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');

    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking overlay
function closeMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');

    sidebar.classList.remove('active');
    overlay.classList.remove('active');
}

// Close sidebar when clicking a menu item on mobile
function closeSidebarOnMobile() {
    if (window.innerWidth <= 991) {
        closeMobileSidebar();
    }
}

// ================================================
// STUDENT MANAGEMENT
// ================================================

// Function to add new student
function addStudent(formData) {
    fetch('admin/process_student.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            document.getElementById('addStudentForm').reset();
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
            if (modal) modal.hide();
            // Refresh the student list
            loadStudentList();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the student');
    });
};

// Function to add new result
function addResult(formData) {
    fetch('admin/process_result.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Refresh the results list or clear the form
            document.getElementById('addResultForm').reset();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the result');
    });
};

// Note: showSection function is defined in admin.php
// This function here is kept for reference but not used

// ================================================
// STUDENT FILTERING AND LOADING
// ================================================

// Function to load departments into dropdown
function loadDepartments() {
    fetch('admin/get_departments.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('departmentFilter');
            // Keep the "All Departments" option
            select.innerHTML = '<option value="">All Departments</option>';
            data.departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.id;
                option.textContent = `${dept.name} (${dept.code})`;
                select.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Error loading departments:', error));
}

// Function to load batches into dropdown
function loadBatches() {
    fetch('admin/get_batches.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('batchFilter');
            // Keep the "All Batches" option
            select.innerHTML = '<option value="">All Batches</option>';
            data.batches.forEach(batch => {
                const option = document.createElement('option');
                option.value = batch.id;
                option.textContent = `${batch.name} (${batch.year})`;
                select.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Error loading batches:', error));
}

// Function to load student list with filters
function loadStudentList() {
    console.log('loadStudentList() called');

    // Get filter values
    const search = document.getElementById('studentSearch')?.value || '';
    const departmentId = document.getElementById('departmentFilter')?.value || '';
    const batchId = document.getElementById('batchFilter')?.value || '';

    // Build query string
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (departmentId) params.append('department', departmentId);
    if (batchId) params.append('batch', batchId);

    console.log('Loading students with params:', params.toString());

    // Show loading state
    const tbody = document.getElementById('studentTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center" style="padding: 40px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2 text-muted">Loading students...</div>
            </td>
        </tr>
    `;

    fetch(`admin/get_students.php?${params.toString()}`)
    .then(response => response.json())
    .then(data => {
        console.log('Students loaded:', data);
        if (data.success) {
            tbody.innerHTML = '';

            if (data.students.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <div class="mt-3">No students found</div>
                            <small>Try adjusting your filters or search term</small>
                        </td>
                    </tr>
                `;
                document.getElementById('studentCount').textContent = 'No students found';
                return;
            }

            data.students.forEach(student => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong style="color: #64748b;">${student.s_no}</strong></td>
                    <td><span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 600;">${student.index_no}</span></td>
                    <td><strong>${student.student_name}</strong></td>
                    <td>${student.board_roll || 'N/A'}</td>
                    <td><span class="badge" style="background: #dcfce7; color: #166534;">${student.department_code}</span></td>
                    <td><span class="badge" style="background: #fef3c7; color: #92400e;">${student.batch_name}</span></td>
                    <td>
                        <button class="action-btn btn-view" onclick="viewStudent(${student.id})" title="View Details"><i class="bi bi-eye"></i></button>
                        <button class="action-btn btn-edit" onclick="editStudent(${student.id})" title="Edit Student"><i class="bi bi-pencil"></i></button>
                        <button class="action-btn btn-delete" onclick="deleteStudent(${student.id})" title="Delete Student"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Update count
            const countText = data.total === 1 ? '1 student found' : `${data.total} students found`;
            document.getElementById('studentCount').textContent = countText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger" style="padding: 40px;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                    <div class="mt-3">Error loading students</div>
                    <small>${error.message}</small>
                </td>
            </tr>
        `;
    });
}

// Initialize filters when manage students section is shown
function initializeStudentFilters() {
    // Load dropdowns
    loadDepartments();
    loadBatches();

    // Add event listeners for filters
    const searchInput = document.getElementById('studentSearch');
    const departmentFilter = document.getElementById('departmentFilter');
    const batchFilter = document.getElementById('batchFilter');

    // Debounce search input
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadStudentList();
            }, 500); // Wait 500ms after user stops typing
        });
    }

    // Immediate reload on filter change
    if (departmentFilter) {
        departmentFilter.addEventListener('change', () => {
            loadStudentList();
        });
    }

    if (batchFilter) {
        batchFilter.addEventListener('change', () => {
            loadStudentList();
        });
    }

    // Load initial data
    loadStudentList();
}

// Function to load dashboard statistics
function loadDashboardStats() {
    fetch('admin/get_statistics.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update statistics cards - check if elements exist first
            const totalStudents = document.querySelector('#totalStudents');
            const publishedResults = document.querySelector('#publishedResults');
            const totalDepartments = document.querySelector('#totalDepartments');
            const activeNotices = document.querySelector('#activeNotices');

            if (totalStudents) totalStudents.textContent = data.stats.total_students;
            if (publishedResults) publishedResults.textContent = data.stats.published_results;
            if (totalDepartments) totalDepartments.textContent = data.stats.total_departments;
            if (activeNotices) activeNotices.textContent = data.stats.active_notices;
        }
    })
    .catch(error => console.error('Error:', error));
}

// Handle file uploads for both students and results
function handleFileUpload(fileType) {
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.xlsx, .xls, .csv';  // Added .csv support

    fileInput.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        const allowedExtensions = ['xlsx', 'xls', 'csv'];
        const fileExtension = file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(fileExtension)) {
            alert('Invalid file type. Please upload only .xlsx, .xls, or .csv files.');
            return;
        }

        // Validate file size (5MB max)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            alert('File size exceeds 5MB. Please upload a smaller file.');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', fileType);

        // Add exam type for results upload
        if (fileType === 'results') {
            const examType = document.querySelector('#examType')?.value || 'ClassTest';
            formData.append('exam_type', examType);
            const semester = document.querySelector('#semester')?.value;
            if (semester) formData.append('semester', semester);
            const departmentId = document.querySelector('#department')?.value;
            if (departmentId) formData.append('department_id', departmentId);
            // Subject no longer sent - comes from Excel file
        }

        // Show loading state
        const uploadZone = document.querySelector(`#${fileType}UploadZone`);
        if (!uploadZone) {
            console.error(`Upload zone #${fileType}UploadZone not found`);
            alert('Error: Upload zone not found');
            return;
        }
        const originalContent = uploadZone.innerHTML;
        uploadZone.innerHTML = '<div class="spinner-border text-primary" role="status"></div><div class="mt-2">Processing...</div>';

        fetch('admin/process_excel_upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                return response.json();
            }
            throw new TypeError("Response was not JSON");
        })
        .then(data => {
            if (data.success) {
                // Show success message and preview
                try {
                    showUploadPreview(fileType, data);
                } catch (err) {
                    console.error('Error showing preview:', err);
                }

                // Show detailed results
                let message = `Upload completed!\n\n`;
                message += `✓ Success: ${data.stats?.success || 0} records\n`;
                if (data.stats?.failed > 0) {
                    message += `✗ Failed: ${data.stats.failed} records\n\n`;
                    message += `Errors:\n`;
                    data.stats.errors?.slice(0, 5)?.forEach(err => {
                        message += `- ${err}\n`;
                    });
                    if (data.stats.errors?.length > 5) {
                        message += `... and ${data.stats.errors.length - 5} more errors`;
                    }
                }
                alert(message);

                // Reset upload zone after showing preview
                setTimeout(() => {
                    uploadZone.innerHTML = originalContent;
                }, 2000);
            } else {
                alert(data.message || 'Upload failed');
                uploadZone.innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert('An error occurred during upload. Please try again.');
            if (uploadZone) {
                uploadZone.innerHTML = originalContent;
            }
        });
    };

    fileInput.click();
}

// Function to show preview of uploaded data
function showUploadPreview(type, responseData) {
    const previewDiv = document.querySelector(`#${type}Preview`);
    if (!previewDiv) {
        console.error(`Preview div #${type}Preview not found`);
        return;
    }
    if (!responseData?.data || !Array.isArray(responseData.data) || responseData.data.length === 0) {
        console.error('No valid data to preview:', responseData);
        return;
    }

    let tableHTML = '<div class="alert alert-success mt-4">';
    tableHTML += `<strong>Import Summary:</strong> ${responseData.stats?.success || 0} records imported successfully`;
    if (responseData.stats?.failed > 0) {
        tableHTML += `, ${responseData.stats.failed} failed`;
    }
    tableHTML += '</div>';

    tableHTML += '<h5 class="mt-3">Preview of Uploaded Data (First 5 rows)</h5>';
    tableHTML += '<div class="table-responsive"><table class="table table-sm table-bordered">';

    // Generate headers based on type
    const headers = type === 'students'
        ? ['Batch', 'Semester', 'Department', 'Name', 'Roll No', 'Index No', 'Board Roll']
        : ['Index No', 'Board Roll', 'Subject Code', 'Subject Name', 'Marks', 'Total Marks'];

    // Add header row
    tableHTML += '<thead class="table-light"><tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr></thead>';

    // Add data rows
    tableHTML += '<tbody>';
    responseData.data.forEach(row => {
        tableHTML += '<tr>' + row.map(cell => `<td>${cell || ''}</td>`).join('') + '</tr>';
    });
    tableHTML += '</tbody></table></div>';

    // Show errors if any
    if (responseData.stats.errors && responseData.stats.errors.length > 0) {
        tableHTML += '<div class="alert alert-warning mt-3"><strong>Errors:</strong><ul class="mb-0 mt-2">';
        responseData.stats.errors.slice(0, 10).forEach(err => {
            tableHTML += `<li>${err}</li>`;
        });
        if (responseData.stats.errors.length > 10) {
            tableHTML += `<li><em>... and ${responseData.stats.errors.length - 10} more errors</em></li>`;
        }
        tableHTML += '</ul></div>';
    }

    previewDiv.innerHTML = tableHTML;
}

// Handle results file upload when clicking the zone
function handleResultsFileClick() {
    console.log('handleResultsFileClick() called');
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.xlsx, .xls';

    fileInput.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            console.log('File selected:', file.name);
            handleResultsFileUpload(file);
        }
    };

    fileInput.click();
}

// Handle results file upload (drag or click)
function handleResultsFileUpload(file) {
    // Get exam details
    const examType = document.getElementById('examTypeSelect').value;
    const semester = document.getElementById('semesterSelect').value;
    const department = document.getElementById('departmentSelect').value;
    const testNumber = document.getElementById('testNumberInput')?.value;

    // Validate all required fields (subject no longer required - comes from Excel)
    if (!examType || !semester || !department) {
        alert('Please select exam type, semester, and department');
        return;
    }

    // Validate file type
    const allowedExtensions = ['xlsx', 'xls'];
    const fileExtension = file.name.split('.').pop().toLowerCase();

    if (!allowedExtensions.includes(fileExtension)) {
        alert('Invalid file type. Please upload only .xlsx or .xls files.');
        return;
    }

    // Validate file size (10MB max)
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
        alert('File size exceeds 10MB. Please upload a smaller file.');
        return;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', 'results');
    formData.append('exam_type', examType);
    formData.append('semester', semester);
    formData.append('department_id', department);

    if (examType === 'ClassTest' || examType === 'Assignment') {
        formData.append('test_number', testNumber || '1');
    }

    // Show loading state
    const uploadZone = document.getElementById('resultsUploadZone');
    const originalContent = uploadZone.innerHTML;
    uploadZone.innerHTML = `
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2">Processing file...</div>
        <small class="text-muted">${file.name}</small>
    `;

    // Upload file
    fetch('admin/process_excel_upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            return response.json();
        }
        throw new TypeError("Response was not JSON");
    })
    .then(data => {
        if (data.success) {
            // Show success message and preview
            try {
                showUploadPreview('results', data);
            } catch (err) {
                console.error('Error showing preview:', err);
            }

            // Show detailed results
            let message = `Upload completed!\n\n`;
            message += `✓ Success: ${data.stats?.success || 0} records\n`;
            if (data.stats?.failed > 0) {
                message += `✗ Failed: ${data.stats.failed} records\n\n`;
                message += `Errors:\n`;
                data.stats.errors?.slice(0, 5)?.forEach(err => {
                    message += `- ${err}\n`;
                });
                if (data.stats.errors?.length > 5) {
                    message += `... and ${data.stats.errors.length - 5} more errors`;
                }
            }
            alert(message);

            // Store upload info for undo feature (if manage_results.js is loaded)
            if (typeof storeLastUpload === 'function' && data.upload_info) {
                const examTitle = document.getElementById('examTypeSelect')?.options[document.getElementById('examTypeSelect')?.selectedIndex]?.text || 'Unknown';
                storeLastUpload({
                    upload_id: data.upload_info.upload_id,
                    count: data.stats.success,
                    timestamp: data.upload_info.timestamp,
                    exam_title: examTitle
                });
            }

            // Reset upload zone after showing preview
            setTimeout(() => {
                uploadZone.innerHTML = originalContent;
            }, 2000);
        } else {
            alert(data.message || 'Upload failed');
            uploadZone.innerHTML = originalContent;
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        alert('An error occurred during upload. Please try again.');
        uploadZone.innerHTML = originalContent;
    });
}

// Initialize drag and drop zones
function initializeDropZones() {
    const dropZones = document.querySelectorAll('.upload-zone');

    dropZones.forEach(zone => {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            // Only add visual feedback if zone is enabled
            if (zone.style.pointerEvents !== 'none') {
                zone.classList.add('upload-zone-drag');
            }
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('upload-zone-drag');
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('upload-zone-drag');

            // Check if zone is enabled
            if (zone.style.pointerEvents === 'none') {
                alert('Please select all required fields first (Exam Type, Semester, Department, etc.)');
                return;
            }

            const file = e.dataTransfer.files[0];
            if (file) {
                const fileType = zone.id.replace('UploadZone', '');

                // For results upload, check if exam details are filled
                if (fileType === 'results') {
                    handleResultsFileUpload(file);
                } else {
                    handleFileUpload(fileType);
                }
            }
        });

        // Handle click to upload
        zone.addEventListener('click', (e) => {
            // Check if zone is enabled
            if (zone.style.pointerEvents === 'none') {
                alert('Please select all required fields first (Exam Type, Semester, Department, etc.)');
                return;
            }

            const fileType = zone.id.replace('UploadZone', '');

            // For results upload, need special handling
            if (fileType === 'results') {
                handleResultsFileClick();
            } else {
                handleFileUpload(fileType);
            }
        });

        // Also handle button click inside the zone
        const buttons = zone.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent double firing

                // Check if zone is enabled
                if (zone.style.pointerEvents === 'none') {
                    alert('Please select all required fields first (Exam Type, Semester, Department, etc.)');
                    return;
                }

                const fileType = zone.id.replace('UploadZone', '');

                // For results upload, need special handling
                if (fileType === 'results') {
                    handleResultsFileClick();
                } else {
                    handleFileUpload(fileType);
                }
            });
        });
    });
}

// Function to load notices
function loadNotices() {
    console.log('loadNotices() called');
    fetch('admin/get_notices.php')
    .then(response => response.json())
    .then(data => {
        console.log('Notices loaded:', data);
        if (data.success) {
            const tbody = document.querySelector('#noticesTable tbody');
            tbody.innerHTML = '';

            if (data.notices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No notices found</td></tr>';
                return;
            }

            data.notices.forEach(notice => {
                const tr = document.createElement('tr');
                const statusBadge = notice.status === 'published'
                    ? '<span class="badge bg-success">Published</span>'
                    : '<span class="badge bg-warning">Draft</span>';

                tr.innerHTML = `
                    <td>${notice.title}</td>
                    <td>${new Date(notice.publish_date).toLocaleDateString()}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="action-btn btn-edit" onclick="editNotice(${notice.id})"><i class="bi bi-pencil"></i></button>
                        <button class="action-btn btn-delete" onclick="deleteNotice(${notice.id})"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    })
    .catch(error => console.error('Error:', error));
}

// View student details in modal
function viewStudent(id) {
    fetch('admin/get_student.php?id=' + id)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const student = data.student;
            const modal = new bootstrap.Modal(document.getElementById('viewStudentModal'));

            document.getElementById('viewStudentDetails').innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Name:</strong><br>${student.student_name}
                    </div>
                    <div class="col-md-6">
                        <strong>Index No:</strong><br>${student.index_no}
                    </div>
                    <div class="col-md-6">
                        <strong>Roll No:</strong><br>${student.roll_no || 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Board Roll:</strong><br>${student.board_roll || 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Department:</strong><br>${student.department_name} (${student.department_code})
                    </div>
                    <div class="col-md-6">
                        <strong>Batch:</strong><br>${student.batch_name}
                    </div>
                    <div class="col-md-6">
                        <strong>Semester:</strong><br>${student.semester}
                    </div>
                    <div class="col-md-6">
                        <strong>Results Uploaded:</strong><br><span class="badge bg-info">${student.result_count} subjects</span>
                    </div>
                </div>
            `;
            modal.show();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while loading student details');
    });
}

function editStudent(id) {
    fetch('admin/get_student.php?id=' + id)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const student = data.student;
            const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));

            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editStudentName').value = student.student_name;
            document.getElementById('editStudentIndex').value = student.index_no;
            document.getElementById('editStudentRoll').value = student.roll_no || '';
            document.getElementById('editStudentBoardRoll').value = student.board_roll || '';
            document.getElementById('editStudentBatch').value = student.batch_id;
            document.getElementById('editStudentDepartment').value = student.department_id;
            document.getElementById('editStudentSemester').value = student.semester;

            modal.show();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while loading student details');
    });
}

function deleteStudent(id) {
    if (confirm('Are you sure you want to delete this student? This will also delete all their results and cannot be undone.')) {
        const formData = new FormData();
        formData.append('student_id', id);

        fetch('admin/delete_student.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Student deleted successfully!');
                loadStudentList(); // Reload the student list
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the student');
        });
    }
}

function editNotice(id) {
    fetch('admin/get_notice.php?id=' + id)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notice = data.notice;
            const modal = new bootstrap.Modal(document.getElementById('editNoticeModal'));

            document.getElementById('editNoticeId').value = notice.id;
            document.getElementById('editNoticeTitle').value = notice.title;
            document.getElementById('editNoticeContent').value = notice.content;
            document.getElementById('editNoticeDate').value = notice.publish_date;
            document.getElementById('editNoticeStatus').value = notice.status;

            modal.show();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while loading notice details');
    });
}

function deleteNotice(id) {
    if (confirm('Are you sure you want to delete this notice? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('notice_id', id);

        fetch('admin/delete_notice.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Notice deleted successfully!');
                loadNotices(); // Reload the notices list
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the notice');
        });
    }
}

function editBatch(id, name, year) {
    const newName = prompt('Edit batch name:', name);
    const newYear = prompt('Edit batch year:', year);

    if (newName && newYear) {
        const formData = new FormData();
        formData.append('batch_id', id);
        formData.append('batch_name', newName);
        formData.append('batch_year', newYear);

        fetch('admin/update_batch.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Batch updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the batch');
        });
    }
}

function deleteBatch(id) {
    if (confirm('Are you sure you want to delete this batch? Students enrolled in this batch will prevent deletion.')) {
        const formData = new FormData();
        formData.append('batch_id', id);

        fetch('admin/delete_batch.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Batch deleted successfully!');
                location.reload(); // Reload to show updated list
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the batch');
        });
    }
}

function editDepartment(id, name, code) {
    const newName = prompt('Edit department name:', name);
    const newCode = prompt('Edit department code:', code);

    if (newName && newCode) {
        const formData = new FormData();
        formData.append('dept_id', id);
        formData.append('dept_name', newName);
        formData.append('dept_code', newCode);

        fetch('admin/update_department.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Department updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the department');
        });
    }
}

function deleteDepartment(id) {
    if (confirm('Are you sure you want to delete this department? Students enrolled in this department will prevent deletion.')) {
        const formData = new FormData();
        formData.append('department_id', id);

        fetch('admin/delete_department.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Department deleted successfully!');
                location.reload(); // Reload to show updated list
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the department');
        });
    }
}

function exportStudents() {
    const deptId = document.getElementById('studentReportDept').value;
    const url = 'admin/export_students.php' + (deptId ? '?department_id=' + deptId : '');
    window.location.href = url;
}

function exportResults() {
    const batchId = document.getElementById('resultReportBatch').value;
    const url = 'admin/export_results.php' + (batchId ? '?batch_id=' + batchId : '');
    window.location.href = url;
}

function showAddStudentModal() {
    const modal = new bootstrap.Modal(document.getElementById('addStudentModal'));
    modal.show();
}

// Add event listeners to forms
document.addEventListener('DOMContentLoaded', function() {
    // Add Student Form Submit
    const addStudentForm = document.getElementById('addStudentForm');
    if (addStudentForm) {
        addStudentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            addStudent(formData);
        });
    }

    // Edit Student Form Submit
    const editStudentForm = document.getElementById('editStudentForm');
    if (editStudentForm) {
        editStudentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('admin/update_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Student updated successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editStudentModal'));
                    if (modal) modal.hide();
                    loadStudentList();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the student');
            });
        });
    }

    // Add Result Form Submit
    const addResultForm = document.getElementById('addResultForm');
    if (addResultForm) {
        addResultForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            addResult(formData);
        });
    }

    // Edit Notice Form Submit
    const editNoticeForm = document.getElementById('editNoticeForm');
    if (editNoticeForm) {
        editNoticeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('admin/update_notice.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notice updated successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editNoticeModal'));
                    if (modal) modal.hide();
                    loadNotices();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the notice');
            });
        });
    }

    // Notice Form Submit
    const noticeForm = document.getElementById('noticeForm');
    if (noticeForm) {
        noticeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('admin/process_notice.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notice saved successfully!');
                    noticeForm.reset();
                    loadNotices();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the notice');
            });
        });
    }

    // Grade Scale Form Submit
    const gradeScaleForm = document.getElementById('gradeScaleForm');
    if (gradeScaleForm) {
        gradeScaleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('admin/update_grade_scale.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Grade scale updated successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating grade scale');
            });
        });
    }

    // Batch Form Submit
    const addBatchForm = document.getElementById('addBatchForm');
    if (addBatchForm) {
        addBatchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('admin/process_batch.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Batch added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding batch');
            });
        });
    }

    // Department Form Submit
    const addDepartmentForm = document.getElementById('addDepartmentForm');
    if (addDepartmentForm) {
        addDepartmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('admin/process_department.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Department added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding department');
            });
        });
    }

    // Account Settings Form Submit (Password Change)
    const accountSettingsForm = document.getElementById('accountSettingsForm');
    if (accountSettingsForm) {
        accountSettingsForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const currentPassword = this.elements['current_password'].value;
            const newPassword = this.elements['new_password'].value;
            const confirmPassword = this.elements['confirm_password'].value;

            // Client-side validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all password fields');
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return;
            }

            if (newPassword.length < 6) {
                alert('New password must be at least 6 characters long');
                return;
            }

            const formData = new FormData(this);

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

            fetch('admin/update_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;

                if (data.success) {
                    alert(data.message);
                    this.reset();

                    // Optional: Redirect to login after password change
                    if (confirm('Your password has been changed. Do you want to logout and login again with the new password?')) {
                        window.location.href = 'admin/logout.php';
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                console.error('Error:', error);
                alert('An error occurred while updating password');
            });
        });
    }

    // Initialize drop zones
    initializeDropZones();

    // Teacher Form Submit
    const addTeacherForm = document.getElementById('addTeacherForm');
    if (addTeacherForm) {
        addTeacherForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Validate passwords match
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');

            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

            // Submit via AJAX
            fetch('admin/create_teacher.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;

                if (data.success) {
                    alert(data.message);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addTeacherModal'));
                    if (modal) modal.hide();
                    addTeacherForm.reset();
                } else {
                    alert('Error: ' + (data.message || 'Failed to create teacher account'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                alert('An error occurred. Please try again.');
            });
        });
    }
});

// ====================================
// TEACHER MANAGEMENT FUNCTIONS
// ====================================

function showAddTeacherModal() {
    const modal = new bootstrap.Modal(document.getElementById('addTeacherModal'));
    modal.show();
}

// Load all teachers (admin only)
function loadTeachers() {
    const tbody = document.getElementById('teachersTableBody');

    // Show loading state
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center" style="padding: 40px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2 text-muted">Loading teachers...</div>
            </td>
        </tr>
    `;

    fetch('admin/get_teachers.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            tbody.innerHTML = '';

            if (data.teachers.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <div class="mt-3">No teachers found</div>
                            <small>Click "Add Teacher" to create the first teacher account</small>
                        </td>
                    </tr>
                `;
                document.getElementById('teacherCount').textContent = 'No teachers found';
                return;
            }

            data.teachers.forEach(teacher => {
                const tr = document.createElement('tr');
                const statusBadge = teacher.status === 'active'
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';

                const createdDate = new Date(teacher.created_at).toLocaleDateString();

                // Display profile picture or initials
                let photoHTML = '';
                if (teacher.profile_picture && teacher.profile_picture !== null) {
                    photoHTML = `<img src="uploads/teacher_profiles/${teacher.profile_picture}"
                                     alt="${teacher.full_name}"
                                     class="teacher-profile-img"
                                     onerror="this.outerHTML='<div class=\\'teacher-avatar-placeholder\\'>${teacher.first_name.charAt(0)}${teacher.last_name.charAt(0)}</div>'">`;
                } else {
                    photoHTML = `<div class="teacher-avatar-placeholder">${teacher.first_name.charAt(0)}${teacher.last_name.charAt(0)}</div>`;
                }

                tr.innerHTML = `
                    <td><strong style="color: #64748b;">${teacher.s_no}</strong></td>
                    <td>${photoHTML}</td>
                    <td><strong>${teacher.full_name}</strong></td>
                    <td>${teacher.email}</td>
                    <td>${statusBadge}</td>
                    <td>${createdDate}</td>
                    <td>
                        <button class="action-btn btn-view" onclick="generateTempPassword(${teacher.id}, '${teacher.full_name}')" title="Generate Temporary Password">
                            <i class="bi bi-lock-fill"></i>
                        </button>
                        <button class="action-btn btn-edit" onclick="resetTeacherPassword(${teacher.id}, '${teacher.full_name}')" title="Reset Password">
                            <i class="bi bi-key"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteTeacher(${teacher.id}, '${teacher.full_name}')" title="Delete Teacher">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Update count
            const countText = data.total === 1 ? '1 teacher' : `${data.total} teachers`;
            document.getElementById('teacherCount').textContent = countText;
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger" style="padding: 40px;">
                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                        <div class="mt-3">Error loading teachers</div>
                        <small>${data.message}</small>
                    </td>
                </tr>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger" style="padding: 40px;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                    <div class="mt-3">Error loading teachers</div>
                    <small>${error.message}</small>
                </td>
            </tr>
        `;
    });
}

// Generate temporary password for teacher
function generateTempPassword(teacherId, teacherName) {
    if (!confirm(`Generate a new temporary password for ${teacherName}?\n\nThis will replace their current password.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('teacher_id', teacherId);

    fetch('admin/generate_temp_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create a nice modal-like alert with the password
            const message = `
✅ Temporary Password Generated Successfully!

Teacher: ${data.teacher_name}
Email: ${data.teacher_email}

🔑 NEW PASSWORD: ${data.temp_password}

📋 Please copy this password and share it with the teacher.
⚠️ This password will NOT be shown again!

The teacher should login and change this password immediately.
            `.trim();

            alert(message);

            // Optional: Copy to clipboard
            if (navigator.clipboard) {
                navigator.clipboard.writeText(data.temp_password).then(() => {
                    console.log('Password copied to clipboard');
                });
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while generating password');
    });
}

// Reset teacher password (manual entry)
function resetTeacherPassword(teacherId, teacherName) {
    const newPassword = prompt(`Reset password for ${teacherName}:\n\nEnter new password (minimum 6 characters):`);

    if (!newPassword) return;

    if (newPassword.length < 6) {
        alert('Password must be at least 6 characters long!');
        return;
    }

    if (!confirm(`Are you sure you want to reset the password for ${teacherName}?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('teacher_id', teacherId);
    formData.append('new_password', newPassword);

    fetch('admin/reset_teacher_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message + '\n\nNew Password: ' + newPassword + '\n\nPlease share this with the teacher.');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while resetting password');
    });
}

// Delete teacher account
function deleteTeacher(teacherId, teacherName) {
    if (!confirm(`Are you sure you want to delete ${teacherName}'s account?\n\nThis action cannot be undone and will permanently remove:\n- Teacher login access\n- Profile picture\n\nNotices created by this teacher will remain.`)) {
        return;
    }

    // Double confirmation for safety
    if (!confirm(`Final confirmation: Delete ${teacherName}?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('teacher_id', teacherId);

    fetch('admin/delete_teacher.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadTeachers(); // Reload the teacher list
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the teacher');
    });
}

