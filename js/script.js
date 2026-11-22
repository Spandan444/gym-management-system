/**
 * GYM MANAGEMENT SYSTEM - FRONTEND SCRIPT
 * Course: B.Tech DBMS Mini-Project
 * Vanilla JavaScript (No frameworks)
 */

document.addEventListener("DOMContentLoaded", function () {
    // 1. Auto-dismiss flash alerts after 5 seconds
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = "opacity 0.4s ease, transform 0.4s ease";
            alert.style.opacity = "0";
            alert.style.transform = "translateY(-10px)";
            setTimeout(function () {
                alert.remove();
            }, 400);
        }, 5000);
    });

    // 2. Global Instant Search for Tables
    const searchInputs = document.querySelectorAll(".table-search input, #globalSearchInput");
    searchInputs.forEach(function (input) {
        input.addEventListener("keyup", function () {
            const filter = this.value.toLowerCase().trim();
            const tables = document.querySelectorAll(".data-table");
            
            tables.forEach(function (table) {
                const tbody = table.querySelector("tbody");
                if (!tbody) return;
                const rows = tbody.querySelectorAll("tr");
                
                rows.forEach(function (row) {
                    const text = row.textContent.toLowerCase();
                    if (text.indexOf(filter) > -1) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            });
        });
    });

    // 3. Close modal when clicking outside modal dialog
    window.addEventListener("click", function (event) {
        if (event.target.classList.contains("modal")) {
            event.target.classList.remove("active");
        }
    });

    // 4. Close modal on Escape key press
    window.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            const activeModal = document.querySelector(".modal.active");
            if (activeModal) {
                activeModal.classList.remove("active");
            }
        }
    });

    // 5. Automatic Membership End-Date & Suggested Amount Calculation
    const planSelect = document.getElementById("plan_name");
    const startDateInput = document.getElementById("start_date");
    const endDateInput = document.getElementById("end_date");
    const amountInput = document.getElementById("amount");

    if (planSelect && startDateInput && endDateInput) {
        function updateMembershipDatesAndPrice() {
            const plan = planSelect.value;
            const startDateVal = startDateInput.value;
            if (!startDateVal) return;

            const start = new Date(startDateVal);
            if (isNaN(start.getTime())) return;

            let end = new Date(start);
            let suggestedAmount = 0;

            if (plan === "Monthly") {
                end.setMonth(end.getMonth() + 1);
                suggestedAmount = 1800;
            } else if (plan === "Quarterly") {
                end.setMonth(end.getMonth() + 3);
                suggestedAmount = 4800;
            } else if (plan === "Half Yearly") {
                end.setMonth(end.getMonth() + 6);
                suggestedAmount = 8500;
            } else if (plan === "Yearly") {
                end.setFullYear(end.getFullYear() + 1);
                suggestedAmount = 15000;
            }

            // Format YYYY-MM-DD
            const yyyy = end.getFullYear();
            const mm = String(end.getMonth() + 1).padStart(2, "0");
            const dd = String(end.getDate()).padStart(2, "0");
            endDateInput.value = `${yyyy}-${mm}-${dd}`;

            if (amountInput && (!amountInput.value || amountInput.value == 0)) {
                amountInput.value = suggestedAmount.toFixed(2);
            }
        }

        planSelect.addEventListener("change", updateMembershipDatesAndPrice);
        startDateInput.addEventListener("change", updateMembershipDatesAndPrice);
    }
});

/**
 * Open a Modal by element ID
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add("active");
    }
}

/**
 * Close a Modal by element ID
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove("active");
    }
}

/**
 * Confirmation dialog before critical delete operations
 */
function confirmDelete(deleteUrl, itemName) {
    const name = itemName ? `"${itemName}"` : "this record";
    if (confirm(`Are you sure you want to permanently delete ${name}? This action cannot be undone.`)) {
        window.location.href = deleteUrl;
    }
}

/**
 * Print a targeted printable section (e.g. Receipt)
 */
function printReceipt(elemId) {
    const content = document.getElementById(elemId).innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Payment Receipt - GYM MANAGEMENT</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body{font-family: Arial, sans-serif; padding: 25px; color: #1e293b;}');
    printWindow.document.write('.receipt-box{max-width: 500px; margin: 0 auto; border: 1px solid #e2e8f0; padding: 25px; border-radius: 8px;}');
    printWindow.document.write('.receipt-title{text-align: center; border-bottom: 2px solid #ff6b35; padding-bottom: 10px; margin-bottom: 20px;}');
    printWindow.document.write('table{width: 100%; border-collapse: collapse; margin-top: 15px;}');
    printWindow.document.write('td{padding: 8px; border-bottom: 1px solid #f1f5f9;}');
    printWindow.document.write('.total{font-weight: bold; font-size: 16px; color: #ff6b35;}');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 300);
}
