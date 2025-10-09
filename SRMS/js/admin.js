
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
    // ... existing code ...
    
    // If showing manage students section, load student list
    if (sectionId === 'manageStudents') {
        loadStudentList();
    }
    
    // If showing manage results section, load results list
    if (sectionId === 'importResults') {
        loadResultsList();
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
});

