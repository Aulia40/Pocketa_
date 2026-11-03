// PILIH ROLE
function selectRole(role) {

    document.getElementById("role").value = role;

    const buttons = document.querySelectorAll(".role-btn");

    buttons.forEach(button => {
        button.classList.remove("active");
    });

    if (role === "user") {
        buttons[0].classList.add("active");
    } else {
        buttons[1].classList.add("active");
    }

    const adminGroup =
        document.getElementById("adminCodeGroup");

    const adminCode =
        document.getElementById("adminCode");

    adminGroup.style.display =
        role === "admin" ? "block" : "none";

    adminCode.required = role === "admin";
}


// REGISTER KE MYSQL
document.getElementById("registerForm")
    .addEventListener("submit", async function(event) {

    event.preventDefault();

    const nama =
        document.getElementById("nama").value.trim();

    const email =
        document.getElementById("email").value.trim();

    const password =
        document.getElementById("password").value;

    const confirmPassword =
        document.getElementById("confirmPassword").value;

    const role =
        document.getElementById("role").value;

    const message =
        document.getElementById("message");

    if (password !== confirmPassword) {

        message.style.color = "red";
        message.textContent = "Password tidak cocok!";
        return;
    }

    if (password.length < 8) {

        message.style.color = "red";
        message.textContent =
            "Password minimal 8 karakter!";
        return;
    }

    // Pendaftaran admin memerlukan backend khusus
    if (role === "admin") {

        message.style.color = "red";
        message.textContent =
            "Pendaftaran admin belum diaktifkan. " +
            "Silakan daftar sebagai pengguna terlebih dahulu.";

        return;
    }

    const formData = new FormData();

    formData.append("nama", nama);
    formData.append("email", email);
    formData.append("password", password);

    message.style.color = "#5C4033";
    message.textContent = "Sedang mendaftarkan akun...";

    try {

       const response = await fetch("./auth/register.php", {
                method: "POST",
                body: formData
            });

        const responseText = await response.text();

        console.log("STATUS:", response.status);
        console.log("RESPONSE:", responseText);

        let result;

    try {
         result = JSON.parse(responseText);
    } catch (error) {
      console.error("PHP tidak mengembalikan JSON:", responseText);

        message.style.color = "red";
        message.textContent =
             "Server PHP merespons tetapi bukan JSON. Cek Console.";

        return;
    }

    if (response.ok && result.success) {

        message.style.color = "green";
        message.textContent = result.message;

        document.getElementById("registerForm").reset();

        setTimeout(() => {
            window.location.href = "login.html";
        }, 1500);

    } else {

        message.style.color = "red";
        message.textContent =
             result.message || "Registrasi gagal.";

    }

    } catch (error) {

        console.error(error);

        message.style.color = "red";
        message.textContent =
            "Gagal terhubung ke server PHP.";

    }

});