// ================================================
// BBK STYLE - CUSTOM JAVASCRIPT
// Student Result Management System
// ================================================

// Global variables
let currentStudentData = null;

// ================================================
// INITIALIZATION
// ================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('BBK Style SRMS - Initializing...');

    // Setup search input listener
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        // Enter key listener
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchValue = this.value.trim();
                if (searchValue) {
                    searchResult();
                }
            }
        });

        // Auto-focus
        searchInput.focus();
    }

    // Smooth scroll
    document.documentElement.style.scrollBehavior = 'smooth';

    console.log('BBK Style SRMS - Initialized successfully!');
});

// ================================================
// HELPER FUNCTIONS
// ================================================

// No longer needed - removed loading modal function

// ================================================
// SEARCH FUNCTIONALITY
// ================================================

/**
 * Main search function for student results
 */
function searchResult() {
    console.log('Search initiated...');
    const searchValue = document.getElementById('searchInput').value.trim();

    if (!searchValue) {
        showMessage('Please enter a Name or Board Roll', 'warning');
        return;
    }

    // Fetch result
    fetch('get_result.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'search=' + encodeURIComponent(searchValue)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Search complete...');

        if (data.success) {
            // Check for multiple results
            if (data.multiple_results && data.students && data.students.length > 1) {
                displayMultipleResults(data.students, data.count);
            } else {
                currentStudentData = data;
                displayResult(data);
            }
        } else {
            showMessage(data.message || 'No results found. Please check your input and try again.', 'danger');
        }
    })
    .catch(error => {
        console.error('Search error:', error);

        // Hide loading modal and backdrop
        hideLoadingModal();

        showMessage('An error occurred while searching. Please try again.', 'danger');
    });
}

// ================================================
// DISPLAY FUNCTIONS
// ================================================

/**
 * Display single student result with BBK styling
 */
function displayResult(data) {
    // Hide search, show result
    document.getElementById('searchSection').style.display = 'none';
    document.getElementById('resultSection').style.display = 'block';
    document.getElementById('multipleResultsSection').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Populate student info
    const studentInfo = document.getElementById('studentInfo');
    studentInfo.innerHTML = `
        <div class="col-md-3 col-6">
            <small class="d-block opacity-75" style="color: rgba(255,255,255,0.8);">Student Name</small>
            <strong class="fs-6" style="color: white;">${data.student.student_name}</strong>
        </div>
        <div class="col-md-2 col-6">
            <small class="d-block opacity-75" style="color: rgba(255,255,255,0.8);">Index Number</small>
            <strong class="fs-6" style="color: white;">${data.student.index_no}</strong>
        </div>
        <div class="col-md-2 col-6">
            <small class="d-block opacity-75" style="color: rgba(255,255,255,0.8);">Board Roll</small>
            <strong class="fs-6" style="color: white;">${data.student.board_roll}</strong>
        </div>
        <div class="col-md-3 col-6">
            <small class="d-block opacity-75" style="color: rgba(255,255,255,0.8);">Department</small>
            <strong class="fs-6" style="color: white;">${data.student.department_name} (${data.student.department_code})</strong>
        </div>
        <div class="col-md-2 col-6">
            <small class="d-block opacity-75" style="color: rgba(255,255,255,0.8);">Batch</small>
            <strong class="fs-6" style="color: white;">${data.student.batch_name}</strong>
        </div>
    `;

    // Populate results table
    const tableBody = document.getElementById('resultTableBody');
    tableBody.innerHTML = '';

    const hasSemesters = data.semesters && data.semesters.length > 0;
    const resultsToShow = data.all_subjects || [];

    if (resultsToShow && resultsToShow.length > 0) {
        if (hasSemesters) {
            // Display with semester grouping
            data.semesters.forEach((semester) => {
                const semesterHeader = `
                    <tr style="background: linear-gradient(135deg, #5eb3f6, #87c5f9);">
                        <td colspan="6" class="fw-bold" style="color: white !important; padding: 15px;">
                            <i class="bi bi-bookmark-fill me-2"></i>Semester ${semester.semester_number}
                            <span class="float-end">
                                <span class="bbk-badge bbk-badge-info me-2">${semester.total_subjects} Subjects</span>
                                <span class="bbk-badge bbk-badge-success">${semester.percentage}% (${semester.grade})</span>
                            </span>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += semesterHeader;

                semester.subjects.forEach(result => {
                    const badgeClass = getGradeBadgeClass(result.grade);
                    const row = `
                        <tr>
                            <td><strong>${result.subject_code}</strong></td>
                            <td>${result.subject_name}</td>
                            <td class="text-center">${result.marks_obtained}</td>
                            <td class="text-center">${result.total_marks}</td>
                            <td class="text-center">${result.percentage}%</td>
                            <td class="text-center">
                                <span class="bbk-badge ${badgeClass}">${result.grade}</span>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            });
        } else {
            // Display flat results
            resultsToShow.forEach(result => {
                const badgeClass = getGradeBadgeClass(result.grade);
                const row = `
                    <tr>
                        <td><strong>${result.subject_code}</strong></td>
                        <td>${result.subject_name}</td>
                        <td class="text-center">${result.marks_obtained}</td>
                        <td class="text-center">${result.total_marks}</td>
                        <td class="text-center">${result.percentage}%</td>
                        <td class="text-center">
                            <span class="bbk-badge ${badgeClass}">${result.grade}</span>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }
    } else {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No results available</td></tr>';
    }

    // Populate summary cards with BBK styling
    const summaryCards = document.getElementById('summaryCards');
    const hasSemestersForSummary = data.semesters && data.semesters.length > 0;

    summaryCards.innerHTML = `
        ${hasSemestersForSummary ? `
        <div class="col-md-3 col-6">
            <div class="card text-white text-center" style="background: linear-gradient(135deg, #6c757d, #5a6268); border: none; border-radius: 15px;">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2" style="color: white;">Total Semesters</h6>
                    <h2 class="mb-0" style="color: white;">${data.summary.total_semesters || 0}</h2>
                </div>
            </div>
        </div>
        ` : ''}
        <div class="col-md-3 col-6">
            <div class="card text-white text-center" style="background: linear-gradient(135deg, #5eb3f6, #87c5f9); border: none; border-radius: 15px;">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2" style="color: white;">Total Subjects</h6>
                    <h2 class="mb-0" style="color: white;">${data.summary.total_subjects || 0}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-white text-center" style="background: linear-gradient(135deg, #28a745, #20c997); border: none; border-radius: 15px;">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2" style="color: white;">Total Marks</h6>
                    <h2 class="mb-0" style="color: white;">${data.summary.total_marks_obtained || 0}/${data.summary.total_marks_possible || 0}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-white text-center" style="background: linear-gradient(135deg, #17a2b8, #138496); border: none; border-radius: 15px;">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2" style="color: white;">${hasSemestersForSummary ? 'Cumulative GPA' : 'Average'}</h6>
                    <h2 class="mb-0" style="color: white;">${hasSemestersForSummary ? (data.summary.cumulative_gpa || '0.00') : (data.summary.average_percentage || 0) + '%'}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-white text-center" style="background: linear-gradient(135deg, #f9a826, #e89615); border: none; border-radius: 15px;">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2" style="color: white;">Overall Grade</h6>
                    <h2 class="mb-0" style="color: white;">${data.summary.overall_grade || 'N/A'}</h2>
                </div>
            </div>
        </div>
    `;
}

/**
 * Display multiple search results
 */
function displayMultipleResults(students, count) {
    console.log(`Displaying ${count} students for selection`);

    document.getElementById('searchSection').style.display = 'none';
    document.getElementById('resultSection').style.display = 'none';
    document.getElementById('multipleResultsSection').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });

    document.getElementById('multipleResultsMessage').textContent =
        `Found ${count} matching student${count > 1 ? 's' : ''}. Click "View Result" to see details.`;

    const tableBody = document.getElementById('multipleResultsTable');
    tableBody.innerHTML = '';

    students.forEach(student => {
        const row = `
            <tr>
                <td><strong>${student.student_name}</strong></td>
                <td>${student.index_no || '-'}</td>
                <td>${student.board_roll || '-'}</td>
                <td><span class="bbk-badge bbk-badge-info">${student.department_code || 'N/A'}</span></td>
                <td><span class="bbk-badge" style="background: #e9ecef; color: #495057;">${student.batch_name || 'N/A'}</span></td>
                <td class="text-center">
                    <button class="btn btn-login btn-sm" onclick="selectStudent('${student.index_no || student.board_roll}')">
                        <i class="bi bi-eye me-1"></i>View Result
                    </button>
                </td>
            </tr>
        `;
        tableBody.innerHTML += row;
    });
}

/**
 * Select specific student from multiple results
 */
function selectStudent(identifier) {
    console.log(`Selected student: ${identifier}`);
    clearMessage();

    fetch('get_result.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'search=' + encodeURIComponent(identifier)
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading modal and backdrop
        hideLoadingModal();

        if (data.success && !data.multiple_results) {
            currentStudentData = data;
            document.getElementById('multipleResultsSection').style.display = 'none';
            displayResult(data);
        } else {
            showMessage('Error loading student result. Please try again.', 'danger');
        }
    })
    .catch(error => {
        // Hide loading modal and backdrop
        hideLoadingModal();

        console.error('Error:', error);
        showMessage('An error occurred while loading the result.', 'danger');
    });
}

/**
 * Get badge class for grades
 */
function getGradeBadgeClass(grade) {
    if (grade === 'A+' || grade === 'A') return 'bbk-badge-success';
    if (grade === 'A-') return 'bbk-badge-info';
    if (grade === 'B+' || grade === 'B' || grade === 'B-') return 'bbk-badge-info';
    if (grade === 'C+' || grade === 'C' || grade === 'C-') return 'bbk-badge-warning';
    if (grade === 'D' || grade === 'F') return 'bbk-badge-danger';
    return 'bbk-badge-info';
}

// ================================================
// NAVIGATION FUNCTIONS
// ================================================

/**
 * Hide result and return to search
 */
function hideResult() {
    document.getElementById('searchSection').style.display = 'block';
    document.getElementById('resultSection').style.display = 'none';
    document.getElementById('multipleResultsSection').style.display = 'none';
    document.getElementById('searchInput').value = '';
    currentStudentData = null;
    clearMessage();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Hide multiple results and return to search
 */
function hideMultipleResults() {
    document.getElementById('searchSection').style.display = 'block';
    document.getElementById('multipleResultsSection').style.display = 'none';
    document.getElementById('resultSection').style.display = 'none';
    document.getElementById('searchInput').value = '';
    currentStudentData = null;
    clearMessage();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ================================================
// MESSAGE FUNCTIONS
// ================================================

/**
 * Show message in search section
 */
function showMessage(message, type) {
    const messageDiv = document.getElementById('searchMessage');
    if (messageDiv) {
        // BBK style alert
        const alertClass = type === 'danger' ? 'alert-danger' : type === 'warning' ? 'alert-warning' : 'alert-info';
        messageDiv.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
}

/**
 * Clear message
 */
function clearMessage() {
    const messageDiv = document.getElementById('searchMessage');
    if (messageDiv) {
        messageDiv.innerHTML = '';
    }
}

/**
 * Show toast notification (BBK style)
 */
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        color: #2c3e50;
        padding: 1rem 1.5rem;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease;
        border-left: 4px solid ${type === 'success' ? '#28a745' : '#dc3545'};
    `;

    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"
           style="color: ${type === 'success' ? '#28a745' : '#dc3545'}; font-size: 1.5rem;"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(toast);

    // Auto remove
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ================================================
// PRINT & EXPORT FUNCTIONS
// ================================================

/**
 * Print current result
 */
function printResult() {
    if (!currentStudentData) {
        showMessage('No result data available to print', 'warning');
        return;
    }
    window.print();
}

/**
 * Download PDF
 */
function downloadPDF() {
    if (!currentStudentData) {
        showMessage('No result data available to export', 'warning');
        return;
    }

    const searchValue = currentStudentData.student.index_no || currentStudentData.student.board_roll;
    window.location.href = 'download_result_pdf.php?search=' + encodeURIComponent(searchValue);
}

// ================================================
// CONSOLE INFO
// ================================================
console.log('%c SRMS - BBK Style ', 'background: #5eb3f6; color: white; font-size: 14px; font-weight: bold; padding: 5px; border-radius: 5px;');
console.log('%c Student Result Management System ', 'background: #f9a826; color: white; font-size: 12px; padding: 3px; border-radius: 3px;');

// CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
