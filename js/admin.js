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

// Function to load notices
function loadNotices() {
    fetch('admin/get_notices.php')
    .then(response => response.json())
    .then(data => {
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

    // Initialize drop zones
    initializeDropZones();
});

