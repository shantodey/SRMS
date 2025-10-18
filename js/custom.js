// ================================================
// GLOBAL VARIABLES
// ================================================
let currentStudentData = null;
let loadingModal = null;

// ================================================
// INITIALIZATION
// ================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing SRMS');


    // Setup Enter key listener for search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                console.log('Enter key pressed');
                const searchValue = this.value.trim();
                if (searchValue) {
                    searchResult();
                }
            }
        });

        // Auto-focus search input
        searchInput.focus();
        console.log('Search input listener added');
    } else {
        console.warn('Search input not found');
    }

    // Add smooth scroll behavior
    document.documentElement.style.scrollBehavior = 'smooth';
});

// ================================================
// SEARCH FUNCTIONALITY
// ================================================

/**
 * Search for student results
 * Supports fuzzy name matching, index number, board roll, etc.
 */
function searchResult() {
    console.log('searchResult() called');
    const searchValue = document.getElementById('searchInput').value.trim();
    console.log('Search value:', searchValue);

    if (!searchValue) {
        showMessage('Please enter a Name or Board Roll', 'warning');
        return;
    }

    // Show loading modal
    if (loadingModal) {
        console.log('Showing loading modal');
        loadingModal.show();
    } else {
        console.warn('Loading modal not initialized');
        showLoading('Searching...');
    }

    // Fetch result from backend
    console.log('Starting fetch to get_result.php');
    fetch('get_result.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'search=' + encodeURIComponent(searchValue)
    })
    .then(response => {
        console.log('Response received:', response);
        return response.json();
    })
    .then(data => {
        console.log('Data received:', data);

        // Hide loading modal
        if (loadingModal) {
            loadingModal.hide();
        }
        hideLoading();

        if (data.success) {
            // Check if multiple results were returned
            if (data.multiple_results && data.students && data.students.length > 1) {
                displayMultipleResults(data.students, data.count);
            } else {
                currentStudentData = data;
                displayResult(data);
            }
        } else {
            showMessage(data.message || 'Didn\'t find any information. Please check your Name or Board Roll and try again.', 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);

        // Hide loading modal
        if (loadingModal) {
            loadingModal.hide();
        }
        hideLoading();

        showMessage('An error occurred while searching. Please try again.', 'danger');
    });
}

/**
 * Alternate search function name for compatibility
 */
function searchStudent(searchValue) {
    if (!searchValue || searchValue.trim() === '') {
        showMessage('Please enter a search term', 'warning');
        return;
    }

    document.getElementById('searchInput').value = searchValue;
    searchResult();
}

// ================================================
// DISPLAY FUNCTIONS
// ================================================

/**
 * Display single student result
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
            <small class="d-block opacity-75">Student Name</small>
            <strong class="fs-6">${data.student.student_name}</strong>
        </div>
        <div class="col-md-2 col-6">
            <small class="d-block opacity-75">Index Number</small>
            <strong class="fs-6">${data.student.index_no}</strong>
        </div>
        <div class="col-md-2 col-6">
            <small class="d-block opacity-75">Board Roll</small>
            <strong class="fs-6">${data.student.board_roll}</strong>
        </div>
        <div class="col-md-3 col-6">
            <small class="d-block opacity-75">Department</small>
            <strong class="fs-6">${data.student.department_name} (${data.student.department_code})</strong>
        </div>
        <div class="col-md-2 col-6">
            <small class="d-block opacity-75">Batch</small>
            <strong class="fs-6">${data.student.batch_name}</strong>
        </div>
    `;

    // Populate results table with semester grouping
    const tableBody = document.getElementById('resultTableBody');
    tableBody.innerHTML = '';

    // Check if we have semester-based data or flat results
    const hasSemesters = data.semesters && data.semesters.length > 0;
    const resultsToShow = data.all_subjects || [];

    if (resultsToShow && resultsToShow.length > 0) {
        // If we have semesters, group results by semester
        if (hasSemesters) {
            data.semesters.forEach((semester) => {
                // Add semester header row
                const semesterHeader = `
                    <tr class="table-info">
                        <td colspan="6" class="fw-bold">
                            <i class="bi bi-bookmark-fill me-2"></i>Semester ${semester.semester_number}
                            <span class="float-end">
                                <span class="badge bg-primary me-2">${semester.total_subjects} Subjects</span>
                                <span class="badge bg-success">${semester.percentage}% (${semester.grade})</span>
                            </span>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += semesterHeader;

                // Add subjects for this semester
                semester.subjects.forEach(result => {
                    const percentage = result.percentage;
                    const grade = result.grade;
                    const badgeClass = getGradeBadgeClass(grade);

                    const row = `
                        <tr>
                            <td><strong>${result.subject_code}</strong></td>
                            <td>${result.subject_name}</td>
                            <td class="text-center">${result.marks_obtained}</td>
                            <td class="text-center">${result.total_marks}</td>
                            <td class="text-center">${percentage}%</td>
                            <td class="text-center">
                                <span class="badge ${badgeClass} grade-badge">${grade}</span>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            });
        } else {
            // Display flat results (backward compatibility)
            resultsToShow.forEach(result => {
                const percentage = result.percentage;
                const grade = result.grade;
                const badgeClass = getGradeBadgeClass(grade);

                const row = `
                    <tr>
                        <td><strong>${result.subject_code}</strong></td>
                        <td>${result.subject_name}</td>
                        <td class="text-center">${result.marks_obtained}</td>
                        <td class="text-center">${result.total_marks}</td>
                        <td class="text-center">${percentage}%</td>
                        <td class="text-center">
                            <span class="badge ${badgeClass} grade-badge">${grade}</span>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }
    } else {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No results available</td></tr>';
    }

    // Populate summary cards
    const summaryCards = document.getElementById('summaryCards');
    const hasSemestersForSummary = data.semesters && data.semesters.length > 0;

    summaryCards.innerHTML = `
        ${hasSemestersForSummary ? `
        <div class="col-md-3 col-6">
            <div class="card bg-secondary text-white text-center">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2">Total Semesters</h6>
                    <h2 class="mb-0">${data.summary.total_semesters || 0}</h2>
                </div>
            </div>
        </div>
        ` : ''}
        <div class="col-md-${hasSemestersForSummary ? '3' : '3'} col-6">
            <div class="card bg-primary text-white text-center">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2">Total Subjects</h6>
                    <h2 class="mb-0">${data.summary.total_subjects || 0}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-${hasSemestersForSummary ? '3' : '3'} col-6">
            <div class="card bg-success text-white text-center">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2">Total Marks</h6>
                    <h2 class="mb-0">${data.summary.total_marks_obtained || 0}/${data.summary.total_marks_possible || 0}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-${hasSemestersForSummary ? '3' : '3'} col-6">
            <div class="card bg-info text-white text-center">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2">${hasSemestersForSummary ? 'Cumulative GPA' : 'Average'}</h6>
                    <h2 class="mb-0">${hasSemestersForSummary ? (data.summary.cumulative_gpa || '0.00') : (data.summary.average_percentage || 0) + '%'}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-${hasSemestersForSummary ? '3' : '3'} col-6">
            <div class="card bg-warning text-white text-center">
                <div class="card-body">
                    <h6 class="opacity-75 mb-2">Overall Grade</h6>
                    <h2 class="mb-0">${data.summary.overall_grade || 'N/A'}</h2>
                </div>
            </div>
        </div>
    `;
}

/**
 * Display multiple search results for user selection
 */
function displayMultipleResults(students, count) {
    console.log(`Displaying ${count} students for selection`);

    // Hide search and result sections, show multiple results
    document.getElementById('searchSection').style.display = 'none';
    document.getElementById('resultSection').style.display = 'none';
    document.getElementById('multipleResultsSection').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Update message
    document.getElementById('multipleResultsMessage').textContent =
        `Found ${count} matching student${count > 1 ? 's' : ''}. Click "View Result" to see details.`;

    // Populate table
    const tableBody = document.getElementById('multipleResultsTable');
    tableBody.innerHTML = '';

    students.forEach(student => {
        const row = `
            <tr>
                <td><strong>${student.student_name}</strong></td>
                <td>${student.index_no || '-'}</td>
                <td>${student.board_roll || '-'}</td>
                <td><span class="badge bg-info">${student.department_code || 'N/A'}</span></td>
                <td><span class="badge bg-secondary">${student.batch_name || 'N/A'}</span></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-gradient" onclick="selectStudent('${student.index_no || student.board_roll}')">
                        <i class="bi bi-eye me-1"></i>View Result
                    </button>
                </td>
            </tr>
        `;
        tableBody.innerHTML += row;
    });
}

/**
 * Select a specific student from multiple results
 */
function selectStudent(identifier) {
    console.log(`Selected student: ${identifier}`);

    // Clear any previous messages
    clearMessage();

    // Show loading
    if (loadingModal) {
        loadingModal.show();
    } else {
        showLoading('Loading student result...');
    }

    // Fetch specific student result
    fetch('get_result.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'search=' + encodeURIComponent(identifier)
    })
    .then(response => response.json())
    .then(data => {
        if (loadingModal) {
            loadingModal.hide();
        }
        hideLoading();

        if (data.success && !data.multiple_results) {
            currentStudentData = data;
            // Hide multiple results section
            document.getElementById('multipleResultsSection').style.display = 'none';
            displayResult(data);
        } else {
            showMessage('Error loading student result. Please try again.', 'danger');
        }
    })
    .catch(error => {
        if (loadingModal) {
            loadingModal.hide();
        }
        hideLoading();
        console.error('Error:', error);
        showMessage('An error occurred while loading the result.', 'danger');
    });
}

/**
 * Get appropriate Bootstrap badge class for grade
 */
function getGradeBadgeClass(grade) {
    if (grade === 'A+' || grade === 'A') return 'bg-success';
    if (grade === 'A-') return 'bg-info';
    if (grade === 'B+' || grade === 'B' || grade === 'B-') return 'bg-primary';
    if (grade === 'C+' || grade === 'C' || grade === 'C-') return 'bg-warning';
    if (grade === 'D' || grade === 'F') return 'bg-danger';
    return 'bg-secondary';
}

/**
 * Get appropriate Bootstrap color class for grade (alternate function name)
 */
function getGradeColor(grade) {
    const gradeColors = {
        'A+': 'success',
        'A': 'success',
        'A-': 'info',
        'B+': 'primary',
        'B': 'primary',
        'B-': 'primary',
        'C+': 'warning',
        'C': 'warning',
        'C-': 'warning',
        'D': 'danger',
        'F': 'danger'
    };
    return gradeColors[grade] || 'secondary';
}

// ================================================
// NAVIGATION FUNCTIONS
// ================================================

/**
 * Hide result and show search
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
 * Hide multiple results and show search
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
        messageDiv.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
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
 * Show toast notification (enhanced notifications)
 */
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(toast);

    // Fade in
    setTimeout(() => toast.classList.add('show'), 10);

    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ================================================
// LOADING FUNCTIONS
// ================================================

let loadingOverlay = null;

/**
 * Show loading overlay
 */
function showLoading(message = 'Loading...') {
    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">${message}</p>
            </div>
        `;
        document.body.appendChild(loadingOverlay);
    } else {
        loadingOverlay.querySelector('p').textContent = message;
    }
    loadingOverlay.style.display = 'flex';
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
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
 * Export result to PDF
 */
function exportToPDF() {
    if (!currentStudentData) {
        showMessage('No result data available to export', 'warning');
        return;
    }

    const searchValue = currentStudentData.student.index_no || currentStudentData.student.board_roll;
    window.location.href = 'download_result_pdf.php?search=' + encodeURIComponent(searchValue);
}

/**
 * Download PDF (alias for exportToPDF)
 */
function downloadPDF() {
    exportToPDF();
}

// ================================================
// UTILITY FUNCTIONS
// ================================================

/**
 * Clear search input
 */
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
}

/**
 * Format date to readable string
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Format percentage
 */
function formatPercentage(value) {
    return parseFloat(value).toFixed(2) + '%';
}

/**
 * Animate number counting
 */
function animateValue(element, start, end, duration, formatter = null) {
    if (!element) return;

    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = formatter ? formatter(current) : Math.round(current);
    }, 16);
}

/**
 * Fade in animation
 */
function fadeIn(element, duration = 300) {
    if (!element) return;

    element.style.opacity = '0';
    element.style.display = 'block';

    setTimeout(() => {
        element.style.transition = `opacity ${duration}ms ease-in`;
        element.style.opacity = '1';
    }, 10);
}

/**
 * Fade out animation
 */
function fadeOut(element, duration = 300) {
    if (!element) return;

    element.style.transition = `opacity ${duration}ms ease-out`;
    element.style.opacity = '0';

    setTimeout(() => {
        element.style.display = 'none';
    }, duration);
}

// ================================================
// CONSOLE INFORMATION
// ================================================
console.log('%c SRMS - Student Result Management System ', 'background: #4A90E2; color: white; font-size: 14px; font-weight: bold; padding: 5px;');
console.log('%c Custom JavaScript Loaded Successfully ', 'background: #4CAF50; color: white; font-size: 12px; padding: 3px;');
