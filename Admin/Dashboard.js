document.addEventListener("click", e => {

    // OPEN MODAL
    const modalBtn = e.target.closest(".modal-btn, .delete-btn, .edit-btn");
    if (modalBtn) {
        const modalId = modalBtn.dataset.modal;
        const modal = document.getElementById(modalId);
        if (!modal) return;

        if (modalBtn.classList.contains("delete-btn") && modalBtn.dataset.modal === "deleteSection") {
            document.getElementById("deleteSectionId").value = modalBtn.dataset.id;
        }

        if (modalBtn.classList.contains("edit-btn") && modalBtn.dataset.modal === "editSection") {
            const row = modalBtn.closest("tr");

            document.getElementById("editS_ID").value = modalBtn.dataset.id;
            document.getElementById("editSectionName").value = row.children[1].innerText;
            document.getElementById("editDay").value = row.children[2].innerText;
            document.getElementById("editStart").value = row.children[3].innerText;
            document.getElementById("editEnd").value = row.children[4].innerText;
            document.getElementById("editRoom").value = row.children[5].innerText;
        }

        if (modalBtn.classList.contains("delete-btn") && modalBtn.dataset.modal === "deleteTeacher") {
            document.getElementById("deleteTeacherId").value = modalBtn.dataset.id;
        }

        if (modalBtn.classList.contains("edit-btn") && modalBtn.dataset.modal === "editTeacher") {
            const row = modalBtn.closest("tr");

            document.getElementById("editU_ID").value = modalBtn.dataset.id;
            document.getElementById("editName").value = row.children[0].innerText;      // Name
            document.getElementById("editPhone").value = row.children[1].innerText;     // Phone
            document.getElementById("editEmail").value = row.children[2].innerText;     // Email
            document.getElementById("editPassword").value = row.children[4].innerText;  // Password
        }

        if (modalBtn.classList.contains("edit-btn") && modalBtn.dataset.modal === "editStudent") {
            const row = modalBtn.closest("tr");
            document.getElementById("editStudentId").value = modalBtn.dataset.id;
            document.getElementById("editStudentName").value = row.children[0].innerText;
            document.getElementById("editStudentPhone").value = row.children[1].innerText;
            document.getElementById("editStudentEmail").value = row.children[2].innerText;
            document.getElementById("editStudentPassword").value = row.children[4].innerText;
        }

        if (modalBtn.classList.contains("delete-btn") && modalBtn.dataset.modal === "deleteStudent") {
            document.getElementById("deleteStudentId").value = modalBtn.dataset.id;
        }

        modal.classList.add("active");
    }

    // CLOSE MODAL (click outside)
    if (e.target.classList.contains("modal")) {
        e.target.classList.remove("active");
    }
});

function showToast(message, type = "success") {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    
    // Choose icon based on type
    const icon = type === "success" ? "✅" : "❌";
    
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <span class="toast-message">${message}</span>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('active'), 100);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        toast.classList.remove('active');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}