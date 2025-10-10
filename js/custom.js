// Function to search and display results
function showResult() {
    const searchValue = document.getElementById('searchInput').value.trim();
    
    if (!searchValue) {
        showToast('error', 'Please enter an Index Number or Board Roll');
        return;
    }

    // Show loading state
    const loader = document.getElementById('resultLoader');
    const searchSection = document.getElementById('searchSection');
    const resultSection = document.getElementById('resultSection');
    
    loader.style.display = 'block';
    searchSection.style.display = 'none';
    resultSection.style.display = 'none';
    
    // Fetch results from server
    fetch(`get_result.php?search=${encodeURIComponent(searchValue)}`)
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update student info with animation
            updateStudentInfo(data.student);
            // Update results table with fade effect
            updateResultsTable(data.results);
            // Update statistics with counter animation
            updateStats(data.stats);
            // Show result section with fade in
            resultSection.style.display = 'block';
            resultSection.classList.add('fade-in');
            // Show success message
            showToast('success', 'Results loaded successfully');
        } else {
            throw new Error(data.message || 'No results found');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', error.message || 'An error occurred while fetching results');
        hideResult();
    })
    .finally(() => {
        loader.style.display = 'none';
        searchSection.style.display = 'block';
    });
}

// Function to update student information
function updateStudentInfo(student) {
    document.querySelector('[data-student-name]').textContent = student.student_name;
    document.querySelector('[data-index-no]').textContent = student.index_no;
    document.querySelector('[data-board-roll]').textContent = student.board_roll;
    document.querySelector('[data-roll-no]').textContent = student.roll_no;
    document.querySelector('[data-department]').textContent = student.department_code;
}

// Function to update results table
function updateResultsTable(results) {
    const tbody = document.querySelector('#resultsTable tbody');
    tbody.innerHTML = '';
    
    results.forEach(result => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${result.subject_code}</strong></td>
            <td>${result.subject_name}</td>
            <td class="text-center">${result.marks_obtained}</td>
            <td class="text-center">${result.total_marks}</td>
            <td class="text-center">${result.percentage.toFixed(2)}%</td>
            <td class="text-center">
                <span class="badge bg-${getGradeColor(result.grade)} grade-badge">${result.grade}</span>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Toast notification system
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} fade-in`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Animate number counting
function animateValue(element, start, end, duration) {
    const range = end - start;
    const increment = end > start ? 1 : -1;
    const stepTime = Math.abs(Math.floor(duration / range));
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        element.textContent = current;
        if (current === end) {
            clearInterval(timer);
        }
    }, stepTime);
}

// Function to update statistics with animation
function updateStats(stats) {
    const elements = {
        totalSubjects: document.querySelector('[data-total-subjects]'),
        totalMarks: document.querySelector('[data-total-marks]'),
        average: document.querySelector('[data-average]'),
        overallGrade: document.querySelector('[data-overall-grade]')
    };

    // Animate total subjects
    animateValue(elements.totalSubjects, 0, stats.total_subjects, 500);
    // Animate total marks
    animateValue(elements.totalMarks, 0, stats.total_marks, 1000);
    // Animate average percentage
    animateValue(
        elements.average,
        0,
        parseFloat(stats.average_percentage),
        800,
        (value) => `${value.toFixed(2)}%`
    );
    
    // Update overall grade with fade effect
    elements.overallGrade.style.opacity = '0';
    setTimeout(() => {
        elements.overallGrade.textContent = stats.overall_grade;
        elements.overallGrade.style.opacity = '1';
    }, 300);
}

// Function to get appropriate color for grade badges
function getGradeColor(grade) {
    switch(grade) {
        case 'A+':
        case 'A':
            return 'success';
        case 'A-':
            return 'info';
        case 'B':
            return 'primary';
        case 'C':
            return 'warning';
        default:
            return 'danger';
    }
}

// Hide result with animation
function hideResult() {
    const resultSection = document.getElementById('resultSection');
    resultSection.classList.add('fade-out');
    setTimeout(() => {
        resultSection.style.display = 'none';
        resultSection.classList.remove('fade-out');
        document.getElementById('searchSection').style.display = 'block';
    }, 300);
}

// Print result functionality
function printResult() {
    const printArea = document.getElementById('resultSection').cloneNode(true);
    printArea.classList.add('print-friendly');
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Student Result</title>
                <link href="css/print.css" rel="stylesheet">
            </head>
            <body>
                ${printArea.outerHTML}
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

// Export result to PDF
function exportToPDF() {
    const element = document.getElementById('resultSection');
    const opt = {
        margin: 1,
        filename: 'student-result.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
}

// Event listener for search input
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        showResult();
    }
});

