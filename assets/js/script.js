document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    // Auto-hide alerts after 5s
    document.querySelectorAll('.alert-dismissible').forEach(el => {
        setTimeout(() => { try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch(e) {} }, 5000);
    });

    // Sidebar toggle (mobile)
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            sidebar.classList.toggle('show');
            if (sidebar.classList.contains('show')) {
                createBackdrop();
            } else {
                removeBackdrop();
            }
        });
    }

    function createBackdrop() {
        if (document.getElementById('sidebarBackdrop')) return;
        const bd = document.createElement('div');
        bd.id = 'sidebarBackdrop';
        bd.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:1035;backdrop-filter:blur(2px)';
        bd.addEventListener('click', function () {
            sidebar.classList.remove('show');
            this.remove();
        });
        document.body.appendChild(bd);
    }

    function removeBackdrop() {
        const bd = document.getElementById('sidebarBackdrop');
        if (bd) bd.remove();
    }

    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'هل أنت متأكد من تنفيذ هذا الإجراء؟')) {
                e.preventDefault();
            }
        });
    });

    // Select all checkbox
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.select-item').forEach(cb => cb.checked = this.checked);
        });
    }

    // Table search
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const q = this.value.toLowerCase();
            const table = document.querySelector(this.dataset.table);
            if (!table) return;
            table.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // Add data-label attributes to table cells for mobile responsive
    document.querySelectorAll('.table:not(.table-bordered) thead th').forEach((th, idx) => {
        const label = th.textContent.trim();
        document.querySelectorAll('.table:not(.table-bordered) tbody tr').forEach(row => {
            const cell = row.children[idx];
            if (cell) cell.setAttribute('data-label', label);
        });
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
        });
    });
});

function showLoading() {
    const el = document.querySelector('.spinner-wrapper');
    if (el) el.classList.add('show');
}
function hideLoading() {
    const el = document.querySelector('.spinner-wrapper');
    if (el) el.classList.remove('show');
}
function printElement(id) {
    const content = document.getElementById(id);
    if (!content) return;
    const w = window.open('', '', 'width=800,height=600');
    w.document.write(`<!DOCTYPE html><html dir="rtl"><head><title>طباعة</title><link href="${window.location.origin}/Orion/assets/vendor/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet"><link href="${window.location.origin}/Orion/assets/css/style.css" rel="stylesheet"></head><body><div class="container mt-4">${content.innerHTML}</div></body></html>`);
    w.document.close();
    w.print();
}
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const blob = new Blob(['\ufeff' + table.outerHTML], { type: 'application/vnd.ms-excel' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = (filename || 'export') + '.xls';
    a.click();
    URL.revokeObjectURL(a.href);
}
