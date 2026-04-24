<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Password from "primevue/password";
import Message from "primevue/message";
import Dialog from "primevue/dialog";
import { ref, computed } from "vue";
import { useToast } from "primevue/usetoast";

const page = usePage();
const toast = useToast();

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

// ⭐ Email de l'utilisateur depuis les props
const userEmail = computed(() => page.props.auth?.user?.email || "");

// Formulaire Email
const emailForm = useForm({
    email: userEmail.value,
});

// Formulaire Mot de passe
const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

// Dialog de confirmation de suppression
const showDeleteDialog = ref(false);
const deleteForm = useForm({
    password: "",
});

// État de visibilité des mots de passe
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// ⭐ Vérifier si l'email a changé
const emailHasChanged = computed(() => {
    return emailForm.email.trim() !== userEmail.value.trim();
});

// Force du mot de passe
const passwordStrength = computed(() => {
    const pwd = passwordForm.password;
    if (!pwd) return { score: 0, label: "", color: "" };

    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[a-z]/.test(pwd)) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^a-zA-Z0-9]/.test(pwd)) score++;

    const strengths = [
        { label: "Très faible", color: "#ef4444" },
        { label: "Faible", color: "#f97316" },
        { label: "Moyen", color: "#eab308" },
        { label: "Bon", color: "#10b981" },
        { label: "Excellent", color: "#059669" },
    ];

    return {
        score: Math.min(score, 5),
        label: strengths[Math.min(score - 1, 4)]?.label || "",
        color: strengths[Math.min(score - 1, 4)]?.color || "",
    };
});

const strengthPercent = computed(() => {
    return (passwordStrength.value.score / 5) * 100;
});

// Mise à jour de l'email
const updateEmail = () => {
    if (!emailHasChanged.value) return;

    emailForm.patch(route("profile.update"), {
        preserveScroll: true,
        onSuccess: (page) => {
            toast.add({
                severity: "success",
                summary: "Succès",
                detail:
                    page.props.flash?.status || "Email mis à jour avec succès",
                life: 3000,
            });
            emailForm.reset();
            emailForm.email = page.props.auth?.user?.email || "";
            emailForm.clearErrors();
        },
        onError: (errors) => {
            console.error("Erreurs validation:", errors);
        },
    });
};

// Annuler les modifications email
const cancelEmail = () => {
    emailForm.reset();
    emailForm.email = userEmail.value;
    emailForm.clearErrors();
};

// Mise à jour du mot de passe
const updatePassword = () => {
    passwordForm.put(route("password.update"), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Mot de passe mis à jour avec succès",
                life: 3000,
            });
            passwordForm.reset();
            showCurrentPassword.value = false;
            showNewPassword.value = false;
            showConfirmPassword.value = false;
        },
        onError: (errors) => {
            console.error("Erreurs:", errors);
        },
    });
};

// Suppression du compte
const deleteAccount = () => {
    deleteForm.delete(route("profile.destroy"), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Compte supprimé",
                detail: "Votre compte a été supprimé avec succès",
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail: "Mot de passe incorrect",
                life: 3000,
            });
            showDeleteDialog.value = false;
        },
    });
};
</script>

<template>
    <Head title="Paramètres du compte" />

    <AppLayout>
        <div class="settings-container">
            <!-- En-tête -->
            <div class="settings-header">
                <Card class="header-card">
                    <template #content>
                        <div class="header-content">
                            <div class="header-icon">
                                <i class="pi pi-cog"></i>
                            </div>
                            <div class="header-text">
                                <h1>Paramètres du compte</h1>
                                <p>
                                    Gérez votre adresse email et votre mot de
                                    passe
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Message de statut -->
            <div v-if="status" class="status-message">
                <Message severity="success" :closable="true">
                    {{ status }}
                </Message>
            </div>

            <div class="settings-content">
                <!-- Section Email -->
                <Card class="section-card">
                    <template #title>
                        <div class="card-title-section">
                            <div class="title-icon email">
                                <i class="pi pi-envelope"></i>
                            </div>
                            <div>
                                <h2>Adresse email</h2>
                                <p>
                                    Mettez à jour votre adresse email de
                                    connexion
                                </p>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <form
                            @submit.prevent="updateEmail"
                            class="settings-form"
                        >
                            <div class="form-field">
                                <label for="email">
                                    <i class="pi pi-at"></i>
                                    Adresse email
                                </label>
                                <InputText
                                    id="email"
                                    v-model="emailForm.email"
                                    type="email"
                                    placeholder="votre@email.com"
                                    :class="{
                                        'p-invalid': emailForm.errors.email,
                                    }"
                                    class="w-full"
                                />
                                <small
                                    v-if="emailForm.errors.email"
                                    class="error-message"
                                >
                                    <i class="pi pi-exclamation-circle"></i>
                                    {{ emailForm.errors.email }}
                                </small>
                            </div>

                            <div
                                v-if="mustVerifyEmail && emailHasChanged"
                                class="verification-notice"
                            >
                                <Message severity="info" :closable="false">
                                    <i class="pi pi-info-circle mr-2"></i>
                                    Un nouveau lien de vérification sera envoyé
                                    à votre nouvelle adresse email.
                                </Message>
                            </div>

                            <div class="form-actions">
                                <Button
                                    type="button"
                                    label="Annuler"
                                    icon="pi pi-times"
                                    severity="secondary"
                                    outlined
                                    :disabled="
                                        emailForm.processing || !emailHasChanged
                                    "
                                    @click="cancelEmail"
                                />
                                <Button
                                    type="submit"
                                    label="Mettre à jour l'email"
                                    icon="pi pi-check"
                                    severity="success"
                                    :loading="emailForm.processing"
                                    :disabled="!emailHasChanged"
                                />
                            </div>
                        </form>
                    </template>
                </Card>

                <!-- Section Mot de passe -->
                <Card class="section-card">
                    <template #title>
                        <div class="card-title-section">
                            <div class="title-icon password">
                                <i class="pi pi-lock"></i>
                            </div>
                            <div>
                                <h2>Mot de passe</h2>
                                <p>
                                    Changez votre mot de passe pour sécuriser
                                    votre compte
                                </p>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <form
                            @submit.prevent="updatePassword"
                            class="settings-form"
                        >
                            <!-- Mot de passe actuel -->
                            <div class="form-field">
                                <label for="current_password">
                                    <i class="pi pi-key"></i>
                                    Mot de passe actuel
                                </label>
                                <div class="password-wrapper">
                                    <InputText
                                        id="current_password"
                                        v-model="passwordForm.current_password"
                                        :type="
                                            showCurrentPassword
                                                ? 'text'
                                                : 'password'
                                        "
                                        placeholder="••••••••"
                                        :class="{
                                            'p-invalid':
                                                passwordForm.errors
                                                    .current_password,
                                        }"
                                        class="w-full password-input"
                                    />
                                    <Button
                                        type="button"
                                        :icon="
                                            showCurrentPassword
                                                ? 'pi pi-eye-slash'
                                                : 'pi pi-eye'
                                        "
                                        text
                                        rounded
                                        size="small"
                                        class="toggle-password"
                                        @click="
                                            showCurrentPassword =
                                                !showCurrentPassword
                                        "
                                    />
                                </div>
                                <small
                                    v-if="passwordForm.errors.current_password"
                                    class="error-message"
                                >
                                    <i class="pi pi-exclamation-circle"></i>
                                    {{ passwordForm.errors.current_password }}
                                </small>
                            </div>

                            <!-- Nouveau mot de passe -->
                            <div class="form-field">
                                <label for="password">
                                    <i class="pi pi-lock-open"></i>
                                    Nouveau mot de passe
                                </label>
                                <div class="password-wrapper">
                                    <InputText
                                        id="password"
                                        v-model="passwordForm.password"
                                        :type="
                                            showNewPassword
                                                ? 'text'
                                                : 'password'
                                        "
                                        placeholder="••••••••"
                                        :class="{
                                            'p-invalid':
                                                passwordForm.errors.password,
                                        }"
                                        class="w-full password-input"
                                    />
                                    <Button
                                        type="button"
                                        :icon="
                                            showNewPassword
                                                ? 'pi pi-eye-slash'
                                                : 'pi pi-eye'
                                        "
                                        text
                                        rounded
                                        size="small"
                                        class="toggle-password"
                                        @click="
                                            showNewPassword = !showNewPassword
                                        "
                                    />
                                </div>

                                <!-- Indicateur de force -->
                                <div
                                    v-if="passwordForm.password"
                                    class="strength-indicator"
                                >
                                    <div class="strength-bar">
                                        <div
                                            class="strength-fill"
                                            :style="{
                                                width: strengthPercent + '%',
                                                backgroundColor:
                                                    passwordStrength.color,
                                            }"
                                        ></div>
                                    </div>
                                    <span
                                        class="strength-label"
                                        :style="{
                                            color: passwordStrength.color,
                                        }"
                                    >
                                        {{ passwordStrength.label }}
                                    </span>
                                </div>

                                <small
                                    v-if="passwordForm.errors.password"
                                    class="error-message"
                                >
                                    <i class="pi pi-exclamation-circle"></i>
                                    {{ passwordForm.errors.password }}
                                </small>
                            </div>

                            <!-- Confirmation -->
                            <div class="form-field">
                                <label for="password_confirmation">
                                    <i class="pi pi-check-circle"></i>
                                    Confirmer le mot de passe
                                </label>
                                <div class="password-wrapper">
                                    <InputText
                                        id="password_confirmation"
                                        v-model="
                                            passwordForm.password_confirmation
                                        "
                                        :type="
                                            showConfirmPassword
                                                ? 'text'
                                                : 'password'
                                        "
                                        placeholder="••••••••"
                                        class="w-full password-input"
                                    />
                                    <Button
                                        type="button"
                                        :icon="
                                            showConfirmPassword
                                                ? 'pi pi-eye-slash'
                                                : 'pi pi-eye'
                                        "
                                        text
                                        rounded
                                        size="small"
                                        class="toggle-password"
                                        @click="
                                            showConfirmPassword =
                                                !showConfirmPassword
                                        "
                                    />
                                </div>
                                <small
                                    v-if="
                                        passwordForm.errors
                                            .password_confirmation
                                    "
                                    class="error-message"
                                >
                                    {{
                                        passwordForm.errors
                                            .password_confirmation
                                    }}
                                </small>
                            </div>

                            <div class="form-actions">
                                <Button
                                    type="submit"
                                    label="Changer le mot de passe"
                                    icon="pi pi-shield"
                                    severity="primary"
                                    class="save-password-btn"
                                    :loading="passwordForm.processing"
                                    :disabled="
                                        !passwordForm.current_password ||
                                        !passwordForm.password ||
                                        !passwordForm.password_confirmation
                                    "
                                />
                            </div>
                        </form>
                    </template>
                </Card>

                <!-- Section Suppression du compte -->
                <Card class="section-card danger-zone">
                    <template #title>
                        <div class="card-title-section">
                            <div class="title-icon danger">
                                <i class="pi pi-exclamation-triangle"></i>
                            </div>
                            <div>
                                <h2>Zone dangereuse</h2>
                                <p>Supprimez définitivement votre compte</p>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <div class="danger-content">
                            <Message
                                severity="warn"
                                :closable="false"
                                class="mb-3"
                            >
                                <i class="pi pi-alert mr-2"></i>
                                <strong>Attention :</strong> Cette action est
                                irréversible. Toutes vos données seront
                                définitivement supprimées.
                            </Message>

                            <Button
                                label="Supprimer mon compte"
                                icon="pi pi-trash"
                                severity="danger"
                                outlined
                                @click="showDeleteDialog = true"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Dialog de confirmation de suppression -->
        <Dialog
            v-model:visible="showDeleteDialog"
            header="Confirmer la suppression"
            modal
            :style="{ width: '90vw', maxWidth: '450px' }"
            class="delete-dialog"
        >
            <div class="delete-dialog-content">
                <div class="warning-icon">
                    <i class="pi pi-exclamation-triangle"></i>
                </div>
                <p class="warning-text">
                    Êtes-vous absolument sûr de vouloir supprimer votre compte ?
                    Cette action est <strong>irréversible</strong>.
                </p>

                <form @submit.prevent="deleteAccount">
                    <div class="form-field">
                        <label for="delete_password">
                            Entrez votre mot de passe pour confirmer
                        </label>
                        <Password
                            id="delete_password"
                            v-model="deleteForm.password"
                            placeholder="••••••••"
                            :feedback="false"
                            toggleMask
                            class="w-full"
                            :class="{ 'p-invalid': deleteForm.errors.password }"
                        />
                        <small
                            v-if="deleteForm.errors.password"
                            class="error-message"
                        >
                            {{ deleteForm.errors.password }}
                        </small>
                    </div>
                </form>
            </div>

            <template #footer>
                <div class="dialog-actions">
                    <Button
                        label="Annuler"
                        icon="pi pi-times"
                        severity="secondary"
                        outlined
                        @click="showDeleteDialog = false"
                        :disabled="deleteForm.processing"
                    />
                    <Button
                        label="Supprimer définitivement"
                        icon="pi pi-trash"
                        severity="danger"
                        :loading="deleteForm.processing"
                        :disabled="!deleteForm.password"
                        @click="deleteAccount"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* ============================================ */
/* CONTAINER */
/* ============================================ */
.settings-container {
    padding: 0.5rem;
    max-width: 900px;
    margin: 0 auto;
}

@media (min-width: 640px) {
    .settings-container {
        padding: 1rem;
    }
}

@media (min-width: 1024px) {
    .settings-container {
        padding: 1.5rem;
    }
}

/* ============================================ */
/* HEADER */
/* ============================================ */
.settings-header {
    margin-bottom: 1rem;
}

.header-card {
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
}

.dark .header-card {
    background: linear-gradient(135deg, #064e3b 0%, #1f2937 100%);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.25rem 0;
}

.header-icon {
    width: 3rem;
    height: 3rem;
    background: #10b981;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.header-icon i {
    font-size: 1.5rem;
    color: white;
}

.header-text h1 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: #1f2937;
}

.dark .header-text h1 {
    color: #f3f4f6;
}

.header-text p {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
}

@media (min-width: 640px) {
    .header-icon {
        width: 3.5rem;
        height: 3.5rem;
    }
    .header-icon i {
        font-size: 1.75rem;
    }
    .header-text h1 {
        font-size: 1.5rem;
    }
    .header-text p {
        font-size: 0.875rem;
    }
}

/* ============================================ */
/* STATUS MESSAGE */
/* ============================================ */
.status-message {
    margin-bottom: 1rem;
}

/* ============================================ */
/* SETTINGS CONTENT */
/* ============================================ */
.settings-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (min-width: 640px) {
    .settings-content {
        gap: 1.25rem;
    }
}

/* ============================================ */
/* SECTION CARDS */
/* ============================================ */
.section-card {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #f3f4f6;
}

.dark .section-card {
    border-color: #374151;
}

.card-title-section {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.title-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.title-icon.email {
    background: #dbeafe;
    color: #2563eb;
}

.title-icon.password {
    background: #d1fae5;
    color: #059669;
}

.title-icon.danger {
    background: #fee2e2;
    color: #dc2626;
}

.dark .title-icon.email {
    background: #1e3a8a;
    color: #93c5fd;
}

.dark .title-icon.password {
    background: #064e3b;
    color: #6ee7b7;
}

.dark .title-icon.danger {
    background: #7f1d1d;
    color: #fca5a5;
}

.card-title-section h2 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    color: #1f2937;
}

.dark .card-title-section h2 {
    color: #f3f4f6;
}

.card-title-section p {
    font-size: 0.7rem;
    color: #6b7280;
    margin: 0;
}

@media (min-width: 640px) {
    .title-icon {
        width: 3rem;
        height: 3rem;
    }
    .card-title-section h2 {
        font-size: 1.125rem;
    }
    .card-title-section p {
        font-size: 0.75rem;
    }
}

/* ============================================ */
/* FORM */
/* ============================================ */
.settings-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-field label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: #4b5563;
}

.dark .form-field label {
    color: #9ca3af;
}

.form-field label i {
    color: #10b981;
    font-size: 0.875rem;
}

.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

.password-input {
    padding-right: 2.5rem !important;
}

.toggle-password {
    position: absolute;
    right: 0.25rem;
    color: #9ca3af !important;
    width: 2rem !important;
    height: 2rem !important;
}

.toggle-password:hover {
    color: #10b981 !important;
    background: transparent !important;
}

/* Force du mot de passe */
.strength-indicator {
    margin-top: 0.25rem;
}

.strength-bar {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    transition:
        width 0.3s,
        background-color 0.3s;
}

.strength-label {
    display: block;
    font-size: 0.65rem;
    margin-top: 0.125rem;
    font-weight: 500;
}

/* Message d'erreur */
.error-message {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: #dc2626 !important;
    font-size: 0.7rem;
}

.verification-notice {
    margin: 0.5rem 0;
}

/* Actions */
.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.form-actions .p-button {
    flex: 1 1 auto;
}

@media (min-width: 480px) {
    .form-actions .p-button {
        flex: 0 1 auto;
    }
}

.save-password-btn {
    background: #10b981 !important;
    border-color: #10b981 !important;
}

.save-password-btn:hover {
    background: #059669 !important;
    border-color: #059669 !important;
}

/* ============================================ */
/* DANGER ZONE */
/* ============================================ */
.danger-zone {
    border: 1px solid #fecaca !important;
}

.dark .danger-zone {
    border-color: #7f1d1d !important;
}

.danger-content {
    padding: 0.5rem 0;
}

/* ============================================ */
/* DELETE DIALOG */
/* ============================================ */
.delete-dialog-content {
    padding: 0.5rem;
    text-align: center;
}

.warning-icon {
    width: 3.5rem;
    height: 3.5rem;
    background: #fef3c7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.dark .warning-icon {
    background: #78350f;
}

.warning-icon i {
    font-size: 2rem;
    color: #d97706;
}

.dark .warning-icon i {
    color: #fbbf24;
}

.warning-text {
    font-size: 0.875rem;
    color: #4b5563;
    margin-bottom: 1.25rem;
}

.dark .warning-text {
    color: #d1d5db;
}

.warning-text strong {
    color: #dc2626;
}

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* ============================================ */
/* PRIMEVUE OVERRIDES */
/* ============================================ */
:deep(.p-card) {
    border-radius: 1rem;
}

:deep(.p-card .p-card-body) {
    padding: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-card .p-card-body) {
        padding: 1.25rem;
    }
}

:deep(.p-card .p-card-title) {
    padding-bottom: 1rem;
    margin-bottom: 0;
}

:deep(.p-card .p-card-content) {
    padding: 0;
}

:deep(.p-inputtext) {
    width: 100%;
    font-size: 0.875rem;
}

:deep(.p-password) {
    width: 100%;
}

:deep(.p-password-input) {
    width: 100%;
}

:deep(.p-invalid) {
    border-color: #dc2626 !important;
}

:deep(.p-message) {
    font-size: 0.8rem;
}

:deep(.p-dialog .p-dialog-header) {
    padding: 1rem 1rem 0.5rem 1rem;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 0 1rem 1rem 1rem;
}

:deep(.p-dialog .p-dialog-footer) {
    padding: 0.5rem 1rem 1rem 1rem;
}
</style>
