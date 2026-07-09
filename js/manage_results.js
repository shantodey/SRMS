// ================================================
// MANAGE RESULTS - Separate JavaScript Module
// For Teachers Only - Edit/Delete Results & Undo Uploads
// ================================================

// Track last upload for undo functionality
let lastUploadInfo = null;

// Load results with filters
function loadResultsList() {
    console.log('loadResultsList() called');

    // Get filter values
    const examType = document.getElementById('resultsExamTypeFilter')?.value || '';
    const semester = document.getElementById('resultsSemesterFilter')?.value || '';
    const department = document.getElementById('resultsDepartmentFilter')?.value || '';
    const subject = document.getElementById('resultsSubjectFilter')?.value || '';
    const search = document.getElementById('resultsSearch')?.value || '';

    // Build query string
    const params = new URLSearchParams();
    if (examType) params.append('exam_type', examType);
    if (semester) params.append('semester', semester);
    if (department) params.append('department', department);
    if (subject) params.append('subject', subject);
    if (search) params.append('search', search);

    console.log('Loading results with params:', params.toString());

    // Show loading state
    const tbody = document.getElementById('resultsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="10" class="text-center" style="padding: 40px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2 text-muted">Loading results...</div>
            </td>
        </tr>
    `;

    fetch(`admin/get_results.php?${params.toString()}`)
    .then(response => response.json())
    .then(data => {
        console.log('Results loaded:', data);
        if (data.success) {
            tbody.innerHTML = '';

            if (data.results.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center text-muted" style="padding: 40px;">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <div class="mt-3">No results found</div>
                            <small>Try adjusting your filters or search term</small>
                        </td>
                    </tr>
                `;
                const countElement = document.getElementById('resultsCount');
                if (countElement) {
                    countElement.textContent = 'No results found';
                }
                return;
            }

            data.results.forEach((result, index) => {
                const tr = document.createElement('tr');

                // Calculate percentage
                const percentage = result.total_marks > 0
                    ? ((result.marks_obtained / result.total_marks) * 100).toFixed(2)
                    : '0.00';

                // Grade badge color
                const gradeColors = {
                    'A+': 'success', 'A': 'success', 'A-': 'success',
                    'B+': 'info', 'B': 'info', 'B-': 'info',
                    'C+': 'warning', 'C': 'warning', 'C-': 'warning',
                    'D': 'secondary', 'F': 'danger'
                };
                const gradeClass = gradeColors[result.grade] || 'secondary';

                tr.innerHTML = `
                    <td><strong style="color: #64748b;">${index + 1}</strong></td>
                    <td><span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 600;">${result.index_no}</span></td>
                    <td><strong>${result.student_name}</strong></td>
                    <td>${result.exam_title}</td>
                    <td><span class="badge" style="background: #dcfce7; color: #166534;">${result.subject_code}</span></td>
                    <td><strong>${result.marks_obtained}</strong> / ${result.total_marks}</td>
                    <td><strong>${percentage}%</strong></td>
                    <td><span class="badge bg-${gradeClass}">${result.grade}</span></td>
                    <td><small class="text-muted">${new Date(result.created_at).toLocaleDateString()}</small></td>
                    <td>
                        <button class="action-btn btn-edit" onclick="editResult(${result.id})" title="Edit Result">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteResult(${result.id}, '${result.student_name}')" title="Delete Result">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Update count
            const countText = data.total === 1 ? '1 result found' : `${data.total} results found`;
            const countElement = document.getElementById('resultsCount');
            if (countElement) {
                countElement.textContent = countText;
            }
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center text-danger" style="padding: 40px;">
                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                        <div class="mt-3">Error loading results</div>
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
                <td colspan="10" class="text-center text-danger" style="padding: 40px;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                    <div class="mt-3">Error loading results</div>
                    <small>${error.message}</small>
                </td>
            </tr>
        `;
    });
}

// Initialize result filters
function initializeResultFilters() {
    console.log('initializeResultFilters() called');

    // Load dropdowns
    loadDepartmentsForResults();
    loadSubjectsForResults();

    // Add event listeners for filters
    const examTypeFilter = document.getElementById('resultsExamTypeFilter');
    const semesterFilter = document.getElementById('resultsSemesterFilter');
    const departmentFilter = document.getElementById('resultsDepartmentFilter');
    const subjectFilter = document.getElementById('resultsSubjectFilter');
    const searchInput = document.getElementById('resultsSearch');

    // Debounce search input
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadResultsList();
            }, 500);
        });
    }

    // Immediate reload on filter change
    [examTypeFilter, semesterFilter, departmentFilter, subjectFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('change', () => {
                loadResultsList();
            });
        }
    });

    // Load initial data
    loadResultsList();
}

// Load departments for results filter
function loadDepartmentsForResults() {
    fetch('admin/get_departments.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('resultsDepartmentFilter');
            if (select) {
                select.innerHTML = '<option value="">All Departments</option>';
                data.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = `${dept.name} (${dept.code})`;
                    select.appendChild(option);
                });
            }
        }
    })
    .catch(error => console.error('Error loading departments:', error));
}

// Load subjects for results filter
function loadSubjectsForResults() {
    const department = document.getElementById('resultsDepartmentFilter')?.value;
    const semester = document.getElementById('resultsSemesterFilter')?.value;
    const subjectSelect = document.getElementById('resultsSubjectFilter');

    if (!subjectSelect) return;

    if (!department || !semester) {
        subjectSelect.innerHTML = '<option value="">Select Dept & Sem first</option>';
        return;
    }

    fetch(`get_subjects.php?department_id=${department}&semester=${semester}`)
    .then(response => response.json())
    .then(subjects => {
        subjectSelect.innerHTML = '<option value="">All Subjects</option>';
        subjects.forEach(subject => {
            const option = document.createElement('option');
            option.value = subject.id;
            option.textContent = `${subject.subject_name} (${subject.subject_code})`;
            subjectSelect.appendChild(option);
        });
    })
    .catch(error => console.error('Error loading subjects:', error));
}

// Edit result - Open modal with current data
function editResult(resultId) {
    console.log('editResult() called for ID:', resultId);

    fetch(`admin/get_result.php?id=${resultId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const result = data.result;
            const modal = new bootstrap.Modal(document.getElementById('editResultModal'));

            document.getElementById('editResultId').value = result.id;
            document.getElementById('editResultStudent').value = `${result.student_name} (${result.index_no})`;
            document.getElementById('editResultExam').value = result.exam_title;
            document.getElementById('editResultSubject').value = `${result.subject_name} (${result.subject_code})`;
            document.getElementById('editResultMarks').value = result.marks_obtained;
            document.getElementById('editResultTotalMarks').value = result.total_marks;

            modal.show();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while loading result details');
    });
}

// Delete result
function deleteResult(resultId, studentName) {
    if (!confirm(`Are you sure you want to delete the result for ${studentName}?\n\nThis action cannot be undone.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('result_id', resultId);

    fetch('admin/delete_result.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Result deleted successfully!');
            loadResultsList();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the result');
    });
}

// Undo last upload
function undoLastUpload() {
    if (!lastUploadInfo) {
        alert('No recent upload to undo.');
        return;
    }

    const message = `Are you sure you want to undo the last upload?\n\n` +
                   `Uploaded: ${lastUploadInfo.count} results\n` +
                   `Time: ${new Date(lastUploadInfo.timestamp).toLocaleString()}\n` +
                   `Exam: ${lastUploadInfo.exam_title}\n\n` +
                   `This will delete all ${lastUploadInfo.count} results from this upload.`;

    if (!confirm(message)) {
        return;
    }

    const formData = new FormData();
    formData.append('upload_id', lastUploadInfo.upload_id);

    fetch('admin/undo_last_upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully deleted ${data.deleted_count} results!`);
            lastUploadInfo = null;
            document.getElementById('undoUploadBtn').style.display = 'none';
            loadResultsList();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while undoing the upload');
    });
}

// Store last upload info (called after successful upload)
function storeLastUpload(uploadData) {
    lastUploadInfo = {
        upload_id: uploadData.upload_id,
        count: uploadData.count,
        timestamp: uploadData.timestamp,
        exam_title: uploadData.exam_title
    };

    // Show undo button
    const undoBtn = document.getElementById('undoUploadBtn');
    if (undoBtn) {
        undoBtn.style.display = 'inline-block';
    }

    console.log('Last upload stored:', lastUploadInfo);
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', function() {
    // Edit Result Form Submit
    const editResultForm = document.getElementById('editResultForm');
    if (editResultForm) {
        editResultForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('admin/update_result.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Result updated successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editResultModal'));
                    if (modal) modal.hide();
                    loadResultsList();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the result');
            });
        });
    }

    // Undo button click handler
    const undoBtn = document.getElementById('undoUploadBtn');
    if (undoBtn) {
        undoBtn.addEventListener('click', undoLastUpload);
    }
});
