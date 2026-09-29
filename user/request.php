<?php

require_once __DIR__ . "/../config/security.php";
require_once __DIR__ . "/../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "user"
) {
    header("Location: ../login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$error = "";
$user = null;
$services = [];

try {
    /*
     * Get logged-in client details
     */
    $userStmt = $pdo->prepare(
        "SELECT
            id,
            full_name,
            email,
            phone
         FROM users
         WHERE id = :id
           AND role = 'user'
         LIMIT 1"
    );

    $userStmt->execute([
        ":id" => $userId
    ]);

    $user = $userStmt->fetch();

    if (!$user) {
        session_unset();
        session_destroy();

        header("Location: ../login.php");
        exit;
    }

    /*
     * Get active services
     */
    $serviceStmt = $pdo->query(
        "SELECT
            id,
            name,
            slug,
            description
         FROM services
         WHERE is_active = 1
         ORDER BY id ASC"
    );

    $services = $serviceStmt->fetchAll();

} catch (Throwable $e) {
    error_log("Client request page error: " . $e->getMessage());
    $error = "Unable to load the service request form.";
}

$csrf = csrfToken();

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/css/client.css">
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Submit Service Request | KHAN SOLUTIONS</title>

    <meta
        name="description"
        content="Submit a service request to KHAN SOLUTIONS."
    >

    <style>
.page {
    max-width: 900px;
    margin: 0 auto;
    padding: 10px 0 35px;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 22px;
    flex-wrap: wrap;
}

.brand {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: .4px;
}

.brand span {
    color: #60a5fa;
}

.top-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.top-actions a {
    display: inline-block;
    text-decoration: none;
    background: #1e293b;
    color: #e2e8f0;
    border: 1px solid #334155;
    padding: 9px 13px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
}

.top-actions a:hover {
    background: #334155;
}

.heading {
    margin-bottom: 18px;
}

.heading h1 {
    margin-bottom: 7px;
    font-size: 25px;
}

.heading p {
    color: #94a3b8;
    line-height: 1.6;
    font-size: 14px;
}

.form-card {
    background: #111827;
    border: 1px solid #1f2937;
    border-radius: 14px;
    padding: 22px;
}

.section {
    margin-top: 24px;
}

.section:first-child {
    margin-top: 0;
}

.section-title {
    color: #f8fafc;
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 14px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.field.full {
    grid-column: 1 / -1;
}

.field label {
    color: #cbd5e1;
    font-size: 13px;
    font-weight: 600;
}

input,
select,
textarea {
    width: 100%;
    border: 1px solid #334155;
    border-radius: 8px;
    background: #0b1220;
    color: #f8fafc;
    padding: 12px;
    font: inherit;
    outline: none;
}

input::placeholder,
textarea::placeholder {
    color: #64748b;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .15);
}

input[readonly] {
    color: #94a3b8;
    background: #111827;
}

textarea {
    min-height: 150px;
    resize: vertical;
    line-height: 1.5;
}

.hint {
    color: #64748b;
    font-size: 12px;
    line-height: 1.5;
}

.file-box {
    border: 1px dashed #334155;
    border-radius: 10px;
    padding: 14px;
    background: #0b1220;
}

.file-box input {
    border: 0;
    padding: 0;
    background: transparent;
}

.actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid #1f2937;
}

.actions button {
    border: 0;
    border-radius: 8px;
    padding: 11px 18px;
    background: #2563eb;
    color: #fff;
    font-weight: 700;
    font-size: 14px;
}

.actions button:hover {
    background: #1d4ed8;
}

.actions button:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.message {
    display: none;
    padding: 12px 14px;
    border-radius: 9px;
    margin-top: 18px;
    font-size: 14px;
    line-height: 1.5;
}

.message.success {
    display: block;
    background: #14532d;
    color: #bbf7d0;
    border: 1px solid #166534;
}

.message.error {
    display: block;
    background: #7f1d1d;
    color: #fecaca;
    border: 1px solid #991b1b;
}

@media (max-width: 700px) {
    .page {
        padding: 5px 0 25px;
    }

    .topbar {
        align-items: flex-start;
    }

    .top-actions {
        width: 100%;
    }

    .top-actions a {
        flex: 1;
        text-align: center;
    }

    .form-card {
        padding: 17px;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .field.full {
        grid-column: auto;
    }

    .actions {
        flex-direction: column;
    }

    .actions button {
        width: 100%;
    }

    .heading h1 {
        font-size: 22px;
    }
}
</style>
</head>

<body>

<div class="page">

    <div class="topbar">
        <div class="brand">
            KHAN <span>SOLUTIONS</span>
        </div>

        <div class="top-actions">
            <a href="dashboard.php">My Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <main class="card">

        <div class="heading">
            <h1>Submit Service Request</h1>
            <p>
                Tell us what you need and our team will review
                your request.
            </p>
        </div>

        <?php if ($error !== ""): ?>

            <div class="message error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form
            id="clientRequestForm"
            enctype="multipart/form-data"
            novalidate
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrf) ?>"
            >

            <!-- Client information -->
            <section class="section">

                <h2 class="section-title">
                    Client Information
                </h2>

                <div class="grid">

                    <div class="field">
                        <label for="request-name">
                            Full Name
                        </label>

                        <input
                            id="request-name"
                            name="name"
                            type="text"
                            value="<?= e($user["full_name"]) ?>"
                            readonly
                        >
                    </div>

                    <div class="field">
                        <label for="request-email">
                            Email
                        </label>

                        <input
                            id="request-email"
                            name="email"
                            type="email"
                            value="<?= e($user["email"]) ?>"
                            readonly
                        >
                    </div>

                    <div class="field">
                        <label for="request-phone">
                            Phone
                        </label>

                        <input
                            id="request-phone"
                            name="phone"
                            type="text"
                            value="<?= e($user["phone"] ?? "") ?>"
                            readonly
                        >
                    </div>

                </div>

            </section>

            <!-- Request -->
            <section class="section">

                <h2 class="section-title">
                    Request Details
                </h2>

                <div class="grid">

                    <div class="field">
                        <label for="service-type">
                            Service
                        </label>

                        <select
                            id="service-type"
                            name="service"
                            required
                        >
                            <option value="">
                                Select a service
                            </option>

                            <?php foreach ($services as $service): ?>

                                <option
                                    value="<?= e($service["name"]) ?>"
                                >
                                    <?= e($service["name"]) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="field">
                        <label for="request-urgency">
                            Urgency
                        </label>

                        <select
                            id="request-urgency"
                            name="urgency"
                            required
                        >
                            <option value="normal">
                                Normal
                            </option>

                            <option value="urgent">
                                Urgent
                            </option>

                            <option value="emergency">
                                Emergency
                            </option>
                        </select>
                    </div>

                    <div class="field full">
                        <label for="request-title">
                            Request Title
                        </label>

                        <input
                            id="request-title"
                            name="title"
                            type="text"
                            maxlength="200"
                            placeholder="e.g. Home electrical wiring"
                            required
                        >
                    </div>

                    <div class="field full">
                        <label for="request-message">
                            Describe Your Requirement
                        </label>

                        <textarea
                            id="request-message"
                            name="message"
                            maxlength="5000"
                            placeholder="Explain clearly what you need..."
                            required
                        ></textarea>
                    </div>

                    <div class="field full">
                        <label for="request-location">
                            Location
                        </label>

                        <input
                            id="request-location"
                            name="location"
                            type="text"
                            maxlength="255"
                            placeholder="Where should the service be provided?"
                            required
                        >
                    </div>

                    <div class="field full">

                        <label for="request-attachment">
                            Attachment
                        </label>

                        <div class="file-box">

                            <input
                                id="request-attachment"
                                name="attachment"
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                            >

                            <div class="hint">
                                Optional. Maximum 10 MB.
                                JPG, JPEG, PNG, PDF, DOC or DOCX.
                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <div
                id="formMessage"
                class="message"
                role="alert"
            ></div>

            <div class="actions">

                <button
                    type="submit"
                    id="submitRequest"
                >
                    Submit Request
                </button>

            </div>

        </form>

    </main>

</div>

<script>
const form = document.getElementById("clientRequestForm");
const button = document.getElementById("submitRequest");
const message = document.getElementById("formMessage");

form.addEventListener("submit", async function (event) {

    event.preventDefault();

    message.className = "message";
    message.textContent = "";

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    button.disabled = true;
    button.textContent = "Submitting...";

    try {

        const formData = new FormData(form);

        const response = await fetch(
            "../api/service-request.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                "Unable to submit your request."
            );
        }

        message.className = "message success";
        message.textContent =
            "Request #" +
            data.request_id +
            " submitted successfully. Thank you!";

        form.reset();

        setTimeout(function () {
            window.location.href = "dashboard.php";
        }, 1200);

    } catch (error) {

        message.className = "message error";
        message.textContent =
            error.message ||
            "Unable to submit your request.";

    } finally {

        button.disabled = false;
        button.textContent = "Submit Request";
    }
});
</script>

</body>
</html>
