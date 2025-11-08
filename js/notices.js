// ================================================
// NOTICE SYSTEM - Desktop Cards & Mobile Buttons with Modal
// ================================================

let noticesData = [];
let currentNoticeIndex = null;
let lastFocusedElement = null;

// Notice icons (cycling through different icons)
const noticeIcons = ['bi-megaphone-fill', 'bi-bell-fill', 'bi-info-circle-fill', 'bi-star-fill', 'bi-trophy-fill'];

// ================================================
// INITIALIZATION
// ================================================
document.addEventListener('DOMContentLoaded', function() {
    loadNotices();
    setupModalAccessibility();
});

// ================================================
// LOAD NOTICES FROM API
// ================================================
async function loadNotices() {
    try {
        const response = await fetch('api/get_notices.php?limit=3');
        const data = await response.json();

        if (data.success && data.notices && data.notices.length > 0) {
            noticesData = data.notices;
            renderDesktopNotices();
            renderMobileNotices();
        } else {
            // Show empty state if no notices
            document.querySelector('.bbk-notices-empty').style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading notices:', error);
        // Fallback: hide notices section on error
        document.getElementById('noticesSection').style.display = 'none';
    }
}

// ================================================
// RENDER DESKTOP NOTICES (CARDS WITH READ MORE)
// ================================================
function renderDesktopNotices() {
    const container = document.querySelector('.bbk-notices-desktop');
    container.innerHTML = '';

    noticesData.forEach((notice, index) => {
        const card = document.createElement('div');
        card.className = 'bbk-card';
        card.innerHTML = `
            <div class="bbk-card-icon">
                <i class="bi ${noticeIcons[index % noticeIcons.length]}"></i>
            </div>
            <div class="bbk-card-body">
                <h3 class="bbk-card-title">${notice.title}</h3>
                <p class="bbk-card-text">${notice.excerpt}</p>
                <a href="#" class="bbk-card-read-more" onclick="openNoticeModal(${index}); return false;">
                    Read more <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="bbk-card-footer">
                <div class="bbk-card-date">
                    <i class="bi bi-calendar-event"></i>
                    <span>${formatDate(notice.publish_date)}</span>
                </div>
                <div>
                    <i class="bi bi-person"></i> ${notice.publisher_name}
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

// ================================================
// RENDER MOBILE NOTICES (COMPACT BUTTONS)
// ================================================
function renderMobileNotices() {
    const container = document.querySelector('.bbk-notices-button-group');
    container.innerHTML = '';

    noticesData.forEach((notice, index) => {
        const button = document.createElement('button');
        button.className = 'bbk-notice-button';
        button.onclick = () => openNoticeModal(index);
        button.setAttribute('aria-label', `View notice: ${notice.title}`);

        button.innerHTML = `
            <div class="bbk-notice-button-icon">
                <i class="bi ${noticeIcons[index % noticeIcons.length]}"></i>
            </div>
            <div class="bbk-notice-button-content">
                <div class="bbk-notice-button-title">${notice.title}</div>
                <div class="bbk-notice-button-date">
                    <i class="bi bi-calendar3"></i>
                    ${formatDate(notice.publish_date)}
                </div>
            </div>
            <div class="bbk-notice-button-arrow">
                <i class="bi bi-chevron-right"></i>
            </div>
        `;
        container.appendChild(button);
    });
}

// ================================================
// MODAL FUNCTIONS
// ================================================

function openNoticeModal(index) {
    currentNoticeIndex = index;
    const notice = noticesData[index];

    // Store last focused element for accessibility
    lastFocusedElement = document.activeElement;

    // Populate modal
    document.getElementById('noticeModalTitle').textContent = notice.title;
    document.getElementById('noticeModalContent').innerHTML = notice.content;

    // Format publisher info
    const publisherTypeLabel = notice.publisher_type === 'teacher' ? 'Teacher' : 'Administrator';
    document.getElementById('noticeModalPublisher').textContent = `${notice.publisher_name} (${publisherTypeLabel})`;

    document.getElementById('noticeModalDate').textContent = formatDateTime(notice.publish_date, notice.created_at);

    // Show modal
    const modal = document.getElementById('noticeModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Focus first focusable element in modal
    setTimeout(() => {
        const closeButton = modal.querySelector('.bbk-notice-modal-close');
        if (closeButton) closeButton.focus();
    }, 100);
}

function closeNoticeModal() {
    const modal = document.getElementById('noticeModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    currentNoticeIndex = null;

    // Return focus to triggering element
    if (lastFocusedElement) {
        lastFocusedElement.focus();
        lastFocusedElement = null;
    }
}

// ================================================
// MODAL ACCESSIBILITY
// ================================================
function setupModalAccessibility() {
    const modal = document.getElementById('noticeModal');

    // ESC key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeNoticeModal();
        }
    });

    // Focus trap
    modal.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            trapFocus(e, modal);
        }
    });
}

function trapFocus(e, container) {
    const focusableElements = container.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (e.shiftKey) {
        if (document.activeElement === firstElement) {
            lastElement.focus();
            e.preventDefault();
        }
    } else {
        if (document.activeElement === lastElement) {
            firstElement.focus();
            e.preventDefault();
        }
    }
}

// ================================================
// MODAL ACTION FUNCTIONS
// ================================================

function printNotice() {
    if (currentNoticeIndex === null) return;

    const notice = noticesData[currentNoticeIndex];
    const printWindow = window.open('', '_blank');

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${notice.title}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 2rem; max-width: 800px; margin: 0 auto; }
                h1 { color: #2c3e50; margin-bottom: 1rem; }
                .meta { color: #7f8c8d; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #ecf0f1; }
                .content { line-height: 1.8; color: #2c3e50; }
                @media print { body { padding: 1rem; } }
            </style>
        </head>
        <body>
            <h1>${notice.title}</h1>
            <div class="meta">
                <p><strong>Published by:</strong> ${notice.publisher_name}</p>
                <p><strong>Date:</strong> ${formatDateTime(notice.publish_date, notice.created_at)}</p>
            </div>
            <div class="content">${notice.content}</div>
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

function copyNoticeContent() {
    if (currentNoticeIndex === null) return;

    const notice = noticesData[currentNoticeIndex];
    const textContent = `${notice.title}\n\nPublished by: ${notice.publisher_name}\nDate: ${formatDateTime(notice.publish_date, notice.created_at)}\n\n${stripHTML(notice.content)}`;

    // Try modern clipboard API first
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(textContent).then(() => {
            showToast('success', 'Notice copied to clipboard!');
        }).catch(() => {
            fallbackCopy(textContent);
        });
    } else {
        fallbackCopy(textContent);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        document.execCommand('copy');
        showToast('success', 'Notice copied to clipboard!');
    } catch (err) {
        showToast('error', 'Failed to copy notice');
    }

    document.body.removeChild(textarea);
}

// ================================================
// SEARCH INTERACTION - HIDE NOTICES
// ================================================

// Hide notices when search is active
function hideNoticesOnSearch() {
    const noticesSection = document.getElementById('noticesSection');
    noticesSection.classList.add('hidden');
}

// Show notices when returning from search
function showNoticesOnReturn() {
    const noticesSection = document.getElementById('noticesSection');
    noticesSection.classList.remove('hidden');
}

// Hook into existing search functions
const originalSearchResult = window.searchResult;
if (typeof originalSearchResult === 'function') {
    window.searchResult = function() {
        hideNoticesOnSearch();
        return originalSearchResult.apply(this, arguments);
    };
}

const originalHideResult = window.hideResult;
if (typeof originalHideResult === 'function') {
    window.hideResult = function() {
        showNoticesOnReturn();
        return originalHideResult.apply(this, arguments);
    };
}

const originalHideMultipleResults = window.hideMultipleResults;
if (typeof originalHideMultipleResults === 'function') {
    window.hideMultipleResults = function() {
        showNoticesOnReturn();
        return originalHideMultipleResults.apply(this, arguments);
    };
}

// ================================================
// UTILITY FUNCTIONS
// ================================================

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function formatDateTime(dateString, timeString) {
    const date = new Date(dateString + ' ' + (timeString || '00:00:00'));
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('en-US', options);
}

function stripHTML(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

// Export functions to global scope
window.openNoticeModal = openNoticeModal;
window.closeNoticeModal = closeNoticeModal;
window.printNotice = printNotice;
window.copyNoticeContent = copyNoticeContent;
// ================================================
// VIEW ALL NOTICES MODAL
// ================================================

let allNoticesData = [];
let currentOffset = 0;
const noticesPerLoad = 9;
let totalNoticesAvailable = 0;
let isLoadingNotices = false;

/**
 * Load total notices count for the button
 */
async function loadTotalNoticesCount() {
    try {
        const response = await fetch('api/get_notices.php?limit=1&offset=0');
        const data = await response.json();
        
        if (data.success) {
            totalNoticesAvailable = data.total;
            const countElement = document.getElementById('totalNoticesCount');
            if (countElement) {
                countElement.textContent = `${data.total} notices available`;
            }
        }
    } catch (error) {
        console.error('Error loading notices count:', error);
    }
}

/**
 * Open All Notices Modal
 */
async function openAllNoticesModal() {
    const modal = document.getElementById('allNoticesModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Reset state
    allNoticesData = [];
    currentOffset = 0;
    
    // Load first batch
    await loadNoticesBatch();
}

/**
 * Close All Notices Modal
 */
function closeAllNoticesModal() {
    const modal = document.getElementById('allNoticesModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

/**
 * Load a batch of notices
 */
async function loadNoticesBatch() {
    if (isLoadingNotices) return;
    
    isLoadingNotices = true;
    const bodyElement = document.getElementById('allNoticesBody');
    
    // Show loading
    if (currentOffset === 0) {
        bodyElement.innerHTML = '<div class="bbk-all-notices-loading"><i class="bi bi-hourglass-split"></i> Loading notices...</div>';
    }
    
    try {
        const response = await fetch(`api/get_notices.php?limit=${noticesPerLoad}&offset=${currentOffset}`);
        const data = await response.json();
        
        if (data.success && data.notices && data.notices.length > 0) {
            allNoticesData = [...allNoticesData, ...data.notices];
            totalNoticesAvailable = data.total;
            
            renderAllNotices();
            
            // Show/hide Load More button
            const loadMoreSection = document.getElementById('loadMoreSection');
            if (data.has_more) {
                loadMoreSection.style.display = 'block';
            } else {
                loadMoreSection.style.display = 'none';
            }
            
            currentOffset += noticesPerLoad;
        } else if (currentOffset === 0) {
            // No notices at all
            bodyElement.innerHTML = `
                <div class="bbk-all-notices-empty">
                    <i class="bi bi-inbox"></i>
                    <p>No notices available at the moment</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading notices:', error);
        bodyElement.innerHTML = `
            <div class="bbk-all-notices-empty">
                <i class="bi bi-exclamation-triangle"></i>
                <p>Error loading notices. Please try again.</p>
            </div>
        `;
    } finally {
        isLoadingNotices = false;
    }
}

/**
 * Render all notices in grid
 */
function renderAllNotices() {
    const bodyElement = document.getElementById('allNoticesBody');
    
    const gridHTML = `
        <div class="bbk-all-notices-grid">
            ${allNoticesData.map((notice, index) => {
                const iconClass = noticeIcons[index % noticeIcons.length];
                return `
                    <div class="bbk-notice-card-modal" onclick="openNoticeModalFromAll(${index})">
                        <div class="bbk-notice-card-modal-icon">
                            <i class="bi ${iconClass}"></i>
                        </div>
                        <div class="bbk-notice-card-modal-body">
                            <h4 class="bbk-notice-card-modal-title">${notice.title}</h4>
                            <p class="bbk-notice-card-modal-excerpt">${notice.excerpt}</p>
                            <a href="" class="bbk-card-read-more" onclick="openNoticeModal(${index}); return true;">
                                Read more <i class="bi bi-arrow-right"></i>
                            </a>
                            <div class="bbk-notice-card-modal-footer">
                                <div class="bbk-notice-card-modal-date">
                                    <i class="bi bi-calendar3"></i>
                                    ${formatDate(notice.publish_date)}
                                </div>
                                <div>
                                    <i class="bi bi-person"></i> ${notice.publisher_name}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
    
    bodyElement.innerHTML = gridHTML;
}

/**
 * Open specific notice modal from all notices view
 */
function openNoticeModalFromAll(index) {
    // Use the existing notice modal
    const notice = allNoticesData[index];
    currentNoticeIndex = noticesData.findIndex(n => n.id === notice.id);
    
    // If notice not in main array, add it temporarily
    if (currentNoticeIndex === -1) {
        noticesData.push(notice);
        currentNoticeIndex = noticesData.length - 1;
    }
    
    // Open the notice detail modal
    openNoticeModal(currentNoticeIndex);
    
    // Close all notices modal
    closeAllNoticesModal();
}

/**
 * Load more notices
 */
async function loadMoreNotices() {
    await loadNoticesBatch();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadTotalNoticesCount();
});

// Export functions to global scope
window.openAllNoticesModal = openAllNoticesModal;
window.closeAllNoticesModal = closeAllNoticesModal;
window.loadMoreNotices = loadMoreNotices;
window.openNoticeModalFromAll = openNoticeModalFromAll;