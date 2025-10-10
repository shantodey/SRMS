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
            // Refresh the student list or clear the form
            document.getElementById('addStudentForm').reset();
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

// Modify your existing showSection function to load dynamic data
function showSection(sectionId) {
    // If it's dashboard, load statistics
    if (sectionId === 'dashboard') {
        loadDashboardStats();
    }
    
    // If showing manage students section, load student list
    if (sectionId === 'manageStudents') {
        loadStudentList();
    }
    
    // If showing manage results section, load results list
    if (sectionId === 'importResults') {
        loadResultsList();
    }
    
    // If showing notices section, load notices
    if (sectionId === 'manageNotices') {
        loadNotices();
    }
}

// Function to load student list
function loadStudentList() {
    fetch('admin/get_students.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#studentTable tbody');
            tbody.innerHTML = '';
            
            data.students.forEach(student => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${student.index_no}</strong></td>
                    <td>${student.student_name}</td>
                    <td>${student.roll_no}</td>
                    <td><span class="badge bg-primary">${student.department_code}</span></td>
                    <td>${student.batch_year}</td>
                    <td>
                        <button class="action-btn btn-view" onclick="viewStudent(${student.id})"><i class="bi bi-eye"></i></button>
                        <button class="action-btn btn-edit" onclick="editStudent(${student.id})"><i class="bi bi-pencil"></i></button>
                        <button class="action-btn btn-delete" onclick="deleteStudent(${student.id})"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    })
    .catch(error => console.error('Error:', error));
}

// Function to load dashboard statistics
function loadDashboardStats() {
    fetch('admin/get_statistics.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update statistics cards
            document.querySelector('#totalStudents').textContent = data.stats.total_students;
            document.querySelector('#publishedResults').textContent = data.stats.published_results;
            document.querySelector('#totalDepartments').textContent = data.stats.total_departments;
            document.querySelector('#activeNotices').textContent = data.stats.active_notices;
        }
    })
    .catch(error => console.error('Error:', error));
}

// Handle file uploads for both students and results
function handleFileUpload(fileType) {
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.xlsx, .xls';

    fileInput.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        const allowedExtensions = ['xlsx', 'xls'];
        const fileExtension = file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(fileExtension)) {
            alert('Invalid file type. Please upload only .xlsx or .xls files.');
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

        // Show loading state
        const uploadZone = document.querySelector(`#${fileType}UploadZone`);
        const originalContent = uploadZone.innerHTML;
        uploadZone.innerHTML = '<div class="spinner-border text-primary" role="status"></div><div class="mt-2">Processing...</div>';

        fetch('admin/process_excel_upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and preview
                showUploadPreview(fileType, data);

                // Show detailed results
                let message = `Upload completed!\n\n`;
                message += `✓ Success: ${data.stats.success} records\n`;
                if (data.stats.failed > 0) {
                    message += `✗ Failed: ${data.stats.failed} records\n\n`;
                    message += `Errors:\n`;
                    data.stats.errors.slice(0, 5).forEach(err => {
                        message += `- ${err}\n`;
                    });
                    if (data.stats.errors.length > 5) {
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
            console.error('Error:', error);
            alert('An error occurred during upload: ' + error.message);
            uploadZone.innerHTML = originalContent;
        });
    };

    fileInput.click();
}

// Function to show preview of uploaded data
function showUploadPreview(type, responseData) {
    const previewDiv = document.querySelector(`#${type}Preview`);
    if (!previewDiv || !responseData.data || responseData.data.length === 0) return;

    let tableHTML = '<div class="alert alert-success mt-4">';
    tableHTML += `<strong>Import Summary:</strong> ${responseData.stats.success} records imported successfully`;
    if (responseData.stats.failed > 0) {
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

// Initialize drag and drop zones
function initializeDropZones() {
    const dropZones = document.querySelectorAll('.upload-zone');
    
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('upload-zone-drag');
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('upload-zone-drag');
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('upload-zone-drag');
            
            const file = e.dataTransfer.files[0];
            if (file) {
                const fileType = zone.id.replace('UploadZone', '');
                const formData = new FormData();
                formData.append('file', file);
                formData.append('type', fileType);
                
                // Trigger the upload process
                handleFileUpload(fileType);
            }
        });

        // Handle click to upload
        zone.addEventListener('click', () => {
            const fileType = zone.id.replace('UploadZone', '');
            handleFileUpload(fileType);
        });
    });
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

    // Add Result Form Submit
    const addResultForm = document.getElementById('addResultForm');
    if (addResultForm) {
        addResultForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            addResult(formData);
        });
    }

    // Initialize drop zones
    initializeDropZones();
});

