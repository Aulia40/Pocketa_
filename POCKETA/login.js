document
    .getElementById("loginForm")
    .addEventListener("submit", async function (event) {

        event.preventDefault();

        const email =
            document.getElementById("email").value.trim();

        const password =
            document.getElementById("password").value;

        const message =
            document.getElementById("message");

        if (email === "" || password === "") {

            message.style.color = "red";
            message.textContent =
                "Email dan password wajib diisi.";

            return;
        }

        const formData = new FormData();

        formData.append("email", email);
        formData.append("password", password);

        message.style.color = "#5C4033";
        message.textContent = "Sedang login...";

        try {

            const response = await fetch(
                "./auth/login.php",
                {
                    method: "POST",
                    body: formData
                }
            );

            const text = await response.text();

            console.log("STATUS:", response.status);
            console.log("RESPONSE:", text);

            let result;

            try {

                result = JSON.parse(text);

            } catch (error) {

                console.error(
                    "Response PHP bukan JSON:",
                    text
                );

                message.style.color = "red";
                message.textContent =
                    "Server memberikan response yang tidak valid.";

                return;
            }

            if (response.ok && result.success) {

                message.style.color = "green";
                message.textContent =
                    result.message;

                setTimeout(function () {

                    if (result.role === "admin") {

                        window.location.href =
                            "./dashboard/admin.php";

                    } else {

                        window.location.href =
                            "./dashboard/user.php";
                    }

                }, 700);

            } else {

                message.style.color = "red";
                message.textContent =
                    result.message ||
                    "Login gagal.";

            }

        } catch (error) {

            console.error("FETCH ERROR:", error);

            message.style.color = "red";
            message.textContent =
                "Gagal terhubung ke server PHP.";
        }

    });