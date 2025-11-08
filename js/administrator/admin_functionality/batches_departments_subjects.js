        // ================================================
        // SUBJECT MANAGEMENT FUNCTIONS
        // ================================================

        // Load subjects in quick view (for batches page)
        function loadSubjectsQuick() {
            const filter = document.getElementById('subjectQuickFilter').value;
            const listContainer = document.getElementById('subjectsQuickList');
            
            listContainer.innerHTML = '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm"></div></div>';
            
            let url = 'admin/manage_subjects.php?action=list';
            if (filter) url += '&department=' + filter;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        let html = '';
                        data.data.forEach(subject => {
                            html += `
                                <div class="list-group-item d-flex justify-content-between align-items-center" style="padding: 8px 12px;">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: 0.85rem; font-weight: 600;">${subject.subject_code}</div>
                                        <div style="font-size: 0.75rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${subject.subject_name}</div>
                                        <div style="font-size: 0.7rem; color: #94a3b8;">${subject.department_code} | Sem ${subject.semester} | ${subject.total_marks}m</div>
                                    </div>
                                    <div style="display: flex; gap: 4px;">
                                        <button class="action-btn btn-edit" style="padding: 4px 8px; font-size: 0.75rem;" onclick="editSubjectQuick(${subject.id}, '${subject.subject_code}', '${escapeHtml(subject.subject_name)}', ${subject.department_id}, ${subject.semester}, ${subject.total_marks})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="action-btn btn-delete" style="padding: 4px 8px; font-size: 0.75rem;" onclick="deleteSubject(${subject.id})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        listContainer.innerHTML = html;
                    } else {
                        listContainer.innerHTML = '<div class="text-center text-muted py-3"><small>No subjects found</small></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    listContainer.innerHTML = '<div class="text-center text-danger py-3"><small>Error loading subjects</small></div>';
                });
        }

        // Add subject (quick form on batches page)
        document.addEventListener('DOMContentLoaded', function() {
            const addSubjectQuickForm = document.getElementById('addSubjectQuickForm');
            if (addSubjectQuickForm) {
                addSubjectQuickForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'create');
                    
                    fetch('admin/manage_subjects.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            this.reset();
                            loadSubjectsQuick();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while adding the subject');
                    });
                });
            }
        });

        // Edit subject (quick)
        function editSubjectQuick(id, code, name, deptId, semester, marks) {
            const newCode = prompt('Subject Code:', code);
            if (!newCode) return;
            
            const newName = prompt('Subject Name:', name);
            if (!newName) return;
            
            const newMarks = prompt('Total Marks:', marks);
            if (!newMarks) return;
            
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('subject_id', id);
            formData.append('subject_code', newCode);
            formData.append('subject_name', newName);
            formData.append('department_id', deptId);
            formData.append('semester', semester);
            formData.append('total_marks', newMarks);
            
            fetch('admin/manage_subjects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadSubjectsQuick();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        // Delete subject
        function deleteSubject(id) {
            if (!confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('subject_id', id);
            
            fetch('admin/manage_subjects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadSubjectsQuick();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
// Load subjects when batches page is shown
document.addEventListener('DOMContentLoaded', function() {
    // Hook into existing showSection calls
    const navLinks = document.querySelectorAll('.nav-menu a[onclick*="batches"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            setTimeout(() => {
                if (typeof loadSubjectsQuick === 'function') {
                    loadSubjectsQuick();
                }
            }, 100);
        });
    });
});