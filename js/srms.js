
// Function to search and display results
function showResult() {
    const searchValue = document.getElementById('searchInput').value.trim();
    
    if (!searchValue) {
        alert('Please enter an Index Number or Board Roll');
        return;
    }

    // Show loading state
    document.getElementById('searchSection').style.display = 'none';
    document.getElementById('resultSection').style.display = 'block';
    
    // Fetch results from server
    fetch(`get_result.php?search=${encodeURIComponent(searchValue)}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update student info
            updateStudentInfo(data.student);
            // Update results table
            updateResultsTable(data.results);
            // Update statistics
            updateStats(data.stats);
        } else {
            alert(data.message);
            hideResult();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching results');
        hideResult();
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

// Function to update statistics
function updateStats(stats) {
    document.querySelector('[data-total-subjects]').textContent = stats.total_subjects;
    document.querySelector('[data-total-marks]').textContent = stats.total_marks;
    document.querySelector('[data-average]').textContent = `${stats.average_percentage}%`;
    document.querySelector('[data-overall-grade]').textContent = stats.overall_grade;
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

// Event listener for search input
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        showResult();
    }
});

