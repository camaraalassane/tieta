<template>
    <app-layout>
        <div class="service-container">
            <!-- ⭐ En-tête avec couleurs dynamiques -->
            <div class="header-card-wrapper">
                <Card class="header-card">
                    <template #content>
                        <div class="header-content">
                            <div class="header-left">
                                <div class="header-icon">
                                    <i class="pi pi-building text-white"></i>
                                </div>
                                <div class="header-info">
                                    <h1 class="header-title">
                                        {{
                                            isEditing
                                                ? "Modifier le service"
                                                : service.nom
                                        }}
                                    </h1>
                                    <p class="header-subtitle">
                                        <i
                                            class="pi pi-check-circle theme-text-primary"
                                        ></i>
                                        {{
                                            isEditing
                                                ? "Modifiez les informations du service"
                                                : "Détails du service"
                                        }}
                                    </p>
                                </div>
                            </div>
                            <!-- ⭐ Bouton primaire dynamique -->
                            <Button
                                v-if="canManage && !isEditing"
                                label="Modifier"
                                icon="pi pi-pencil"
                                size="small"
                                @click="enableEditing"
                                class="edit-btn"
                            />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Mode édition -->
            <form
                v-if="isEditing"
                @submit.prevent="updateService"
                class="edit-form"
            >
                <Card>
                    <template #title>
                        <div class="card-title-section">
                            <i class="pi pi-info-circle text-emerald-500"></i>
                            <span>Modifier les informations</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="edit-fields">
                            <div class="field">
                                <label
                                    >Nom du service
                                    <span class="required">*</span></label
                                >
                                <InputText
                                    v-model="form.nom"
                                    required
                                    placeholder="Ex: DTTIA"
                                    :class="{ 'p-invalid': errors.nom }"
                                />
                                <small v-if="errors.nom" class="error">{{
                                    errors.nom
                                }}</small>
                            </div>
                            <div class="field">
                                <label>Description</label>
                                <Textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Description du service..."
                                />
                            </div>
                            <div class="field">
                                <label>Logo</label>
                                <div class="logo-upload">
                                    <input
                                        type="file"
                                        @change="handleLogoUpload"
                                        accept="image/*"
                                        class="file-input"
                                    />
                                    <img
                                        v-if="form.logo_preview"
                                        :src="form.logo_preview"
                                        class="logo-preview"
                                    />
                                    <img
                                        v-else-if="service.logo_url"
                                        :src="service.logo_url"
                                        class="logo-preview"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
                <div class="edit-actions">
                    <!-- ⭐ Bouton Annuler - Outlined dynamique -->
                    <Button
                        label="Annuler"
                        icon="pi pi-times"
                        size="small"
                        outlined
                        @click="cancelEditing"
                        class="btn-outlined-primary"
                    />
                    <!-- ⭐ Bouton Enregistrer - Primaire dynamique -->
                    <Button
                        type="submit"
                        label="Enregistrer"
                        icon="pi pi-save"
                        size="small"
                        :loading="processing"
                        class="btn-primary"
                    />
                </div>
            </form>

            <!-- Mode consultation -->
            <template v-else>
                <!-- Stats -->
                <div class="stats-grid">
                    <Card class="stat-card stat-active">
                        <template #content>
                            <div class="stat-content">
                                <div>
                                    <span class="stat-label">Statut</span>
                                    <span class="stat-value">{{
                                        service.is_active ? "Actif" : "Inactif"
                                    }}</span>
                                </div>
                                <div class="stat-icon bg-emerald-100">
                                    <i
                                        class="pi pi-check-circle text-emerald-500"
                                    ></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                    <Card class="stat-card stat-blue">
                        <template #content>
                            <div class="stat-content">
                                <div>
                                    <span class="stat-label">Personnel</span>
                                    <span class="stat-value">{{
                                        service.personnel?.length || 0
                                    }}</span>
                                </div>
                                <div class="stat-icon bg-blue-100">
                                    <i class="pi pi-users text-blue-500"></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                    <Card class="stat-card stat-purple">
                        <template #content>
                            <div class="stat-content">
                                <div>
                                    <span class="stat-label">Slug</span>
                                    <span class="stat-value-small">{{
                                        service.slug
                                    }}</span>
                                </div>
                                <div class="stat-icon bg-purple-100">
                                    <i
                                        class="pi pi-hashtag text-purple-500"
                                    ></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Infos + Logo -->
                <div class="info-logo-grid">
                    <Card class="info-card">
                        <template #title>
                            <div class="card-title-section">
                                <i
                                    class="pi pi-info-circle text-emerald-500"
                                ></i>
                                <span>Informations générales</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="info-list">
                                <div class="info-row">
                                    <span class="info-label">Nom</span
                                    ><span class="info-value">{{
                                        service.nom
                                    }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Gérant</span>
                                    <span class="info-value">
                                        <span class="gerant-badge">
                                            {{
                                                service.gerant?.name?.charAt(
                                                    0,
                                                ) || "G"
                                            }}
                                        </span>
                                        {{ service.gerant?.name }}
                                        {{ service.gerant?.prenom || "" }}
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Description</span
                                    ><span class="info-value desc">{{
                                        service.description ||
                                        "Aucune description"
                                    }}</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="logo-card">
                        <template #title>
                            <div class="card-title-section">
                                <i class="pi pi-image text-emerald-500"></i>
                                <span>Logo</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="logo-display">
                                <img
                                    v-if="service.logo_url"
                                    :src="service.logo_url"
                                    :alt="service.nom"
                                    class="logo-img"
                                />
                                <div v-else class="logo-placeholder">
                                    <i class="pi pi-building text-white"></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Personnel -->
                <Card class="personnel-card">
                    <template #title>
                        <div class="card-title-section justify-between">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users text-emerald-500"></i>
                                <span>Personnel du service</span>
                            </div>
                            <Link
                                v-if="canManage"
                                :href="
                                    route(
                                        'services.personnel.index',
                                        service.id,
                                    )
                                "
                                class="manage-link"
                            >
                                <i class="pi pi-user-plus"></i> Gérer
                            </Link>
                        </div>
                    </template>
                    <template #content>
                        <div class="table-wrapper">
                            <table class="personnel-table">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th class="hide-mobile">Email</th>
                                        <th>Rôle</th>
                                        <th class="hide-mobile">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="member in service.personnel"
                                        :key="member.id"
                                    >
                                        <td>
                                            <div class="member-name">
                                                <span class="member-avatar">{{
                                                    member.name?.charAt(0) ||
                                                    "U"
                                                }}</span>
                                                {{ member.name }}
                                                {{ member.prenom || "" }}
                                            </div>
                                        </td>
                                        <td class="hide-mobile">
                                            {{ member.email }}
                                        </td>
                                        <td>
                                            <Tag
                                                :value="
                                                    member.pivot
                                                        ?.role_in_service ===
                                                    'gerant'
                                                        ? 'Gérant'
                                                        : 'Admin'
                                                "
                                                :severity="
                                                    member.pivot
                                                        ?.role_in_service ===
                                                    'gerant'
                                                        ? 'success'
                                                        : 'info'
                                                "
                                                size="small"
                                            />
                                        </td>
                                        <td class="hide-mobile">
                                            <span
                                                :class="
                                                    member.pivot?.is_active
                                                        ? 'text-green-600'
                                                        : 'text-red-600'
                                                "
                                                class="status-dot"
                                            >
                                                {{
                                                    member.pivot?.is_active
                                                        ? "Actif"
                                                        : "Inactif"
                                                }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !service.personnel ||
                                            service.personnel.length === 0
                                        "
                                    >
                                        <td colspan="4" class="empty-cell">
                                            Aucun personnel
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </Card>

                <!-- Galerie d'images -->
                <Card class="gallery-card">
                    <template #title>
                        <div class="card-title-section justify-between">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-images text-emerald-500"></i>
                                <span
                                    >Galerie d'images ({{
                                        service.images?.length || 0
                                    }}/5)</span
                                >
                            </div>
                            <span
                                v-if="(service.images?.length || 0) >= 5"
                                class="max-reached"
                                >Maximum atteint</span
                            >
                        </div>
                    </template>
                    <template #content>
                        <div class="images-gallery">
                            <div
                                v-for="img in service.images"
                                :key="img.id"
                                class="gallery-item"
                            >
                                <img
                                    :src="img.url"
                                    :alt="img.nom"
                                    class="gallery-image"
                                />
                                <div class="gallery-overlay">
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        rounded
                                        size="small"
                                        @click="deleteImage(img.id)"
                                        v-tooltip.top="'Supprimer'"
                                        :loading="deletingImage === img.id"
                                    />
                                </div>
                                <span class="gallery-name">{{ img.nom }}</span>
                            </div>
                            <div
                                v-if="(service.images?.length || 0) < 5"
                                class="gallery-item gallery-add"
                                @click="$refs.imageInput.click()"
                            >
                                <input
                                    type="file"
                                    @change="uploadImage"
                                    accept="image/jpeg,image/png,image/jpg"
                                    ref="imageInput"
                                    hidden
                                />
                                <div class="add-content">
                                    <i class="pi pi-plus-circle"></i>
                                    <span>Ajouter</span>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="
                                !service.images || service.images.length === 0
                            "
                            class="empty-gallery"
                        >
                            <i class="pi pi-images"></i>
                            <p>Aucune image. Cliquez sur "Ajouter".</p>
                        </div>
                    </template>
                </Card>
            </template>
        </div>
    </app-layout>
</template>

<script setup>
import { ref, reactive } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import Button from "primevue/button";
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Tag from "primevue/tag";
import axios from "axios";

const props = defineProps({
    service: { type: Object, required: true },
    isSuperAdmin: Boolean,
    isGerant: Boolean,
    canManage: Boolean,
});

const toast = useToast();
const isEditing = ref(false);
const processing = ref(false);
const errors = ref({});
const deletingImage = ref(null);

const form = reactive({
    nom: props.service.nom,
    description: props.service.description || "",
    logo: null,
    logo_preview: null,
});

const enableEditing = () => {
    isEditing.value = true;
};
const cancelEditing = () => {
    isEditing.value = false;
    errors.value = {};
    form.logo_preview = null;
};
const handleLogoUpload = (e) => {
    const f = e.target.files[0];
    if (f) {
        form.logo = f;
        form.logo_preview = URL.createObjectURL(f);
    }
};

const updateService = () => {
    processing.value = true;
    const data = new FormData();
    data.append("nom", form.nom);
    data.append("description", form.description);
    if (form.logo) data.append("logo", form.logo);
    data.append("_method", "PUT");
    router.post(route("services.update", props.service.id), data, {
        onSuccess: () => {
            isEditing.value = false;
            processing.value = false;
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Service mis à jour.",
                life: 3000,
            });
        },
        onError: (err) => {
            errors.value = err;
            processing.value = false;
        },
    });
};

const uploadImage = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append("image", file);
    try {
        await axios.post(route("services.images.store", props.service.id), fd, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        toast.add({
            severity: "success",
            summary: "Succès",
            detail: "Image ajoutée.",
            life: 3000,
        });
        router.reload();
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: err.response?.data?.message || "Erreur upload.",
            life: 3000,
        });
    }
};

const deleteImage = async (id) => {
    deletingImage.value = id;
    try {
        await axios.delete(
            route("services.images.destroy", {
                service: props.service.id,
                image: id,
            }),
        );
        toast.add({
            severity: "success",
            summary: "Succès",
            detail: "Image supprimée.",
            life: 3000,
        });
        router.reload();
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Erreur suppression.",
            life: 3000,
        });
    } finally {
        deletingImage.value = null;
    }
};
</script>

<style scoped>
/* ============================================ */
/* CONTAINER */
/* ============================================ */
.service-container {
    padding: 0.5rem;
}
@media (min-width: 640px) {
    .service-container {
        padding: 1rem;
    }
}
@media (min-width: 1024px) {
    .service-container {
        padding: 1rem 2rem;
    }
}

/* ============================================ */
/* ⭐ HEADER - DYNAMIQUE */
/* ============================================ */
.header-card-wrapper {
    margin-bottom: 1rem;
}
.header-card {
    border: none !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    background: linear-gradient(135deg, var(--color-primary-light), white);
}
.dark .header-card {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), #1f2937);
}
.header-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
}
@media (min-width: 640px) {
    .header-content {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}
.header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.header-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: var(--color-primary);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.header-icon i {
    color: white;
}
@media (min-width: 640px) {
    .header-icon {
        width: 3rem;
        height: 3rem;
    }
}
.header-icon i {
    font-size: 1.25rem;
}
@media (min-width: 640px) {
    .header-icon i {
        font-size: 1.5rem;
    }
}
.header-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
}
.dark .header-title {
    color: #f3f4f6;
}
@media (min-width: 640px) {
    .header-title {
        font-size: 1.5rem;
    }
}
.header-subtitle {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0.25rem 0 0 0;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* ============================================ */
/* ⭐ STATS - DYNAMIQUE */
/* ============================================ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    margin-bottom: 1rem;
}
@media (min-width: 640px) {
    .stats-grid {
        gap: 1rem;
    }
}
.stat-card {
    border: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border-left: 4px solid;
}
.stat-active {
    border-color: var(--color-primary);
}
.stat-blue {
    border-color: #3b82f6;
}
.stat-purple {
    border-color: #8b5cf6;
}
.stat-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.stat-label {
    font-size: 0.65rem;
    color: #6b7280;
    text-transform: uppercase;
}
.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    display: block;
}
.stat-active .stat-value {
    color: var(--color-primary-dark);
}
.stat-blue .stat-value {
    color: #2563eb;
}
.stat-purple .stat-value {
    color: #7c3aed;
}
.stat-value-small {
    font-size: 0.7rem;
    font-weight: 600;
    display: block;
    color: #7c3aed;
    word-break: break-all;
}
.stat-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ============================================ */
/* INFO + LOGO GRID */
/* ============================================ */
.info-logo-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1rem;
}
@media (min-width: 1024px) {
    .info-logo-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
    }
}

.card-title-section {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}
.justify-between {
    justify-content: space-between;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.info-row {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
}
.dark .info-row {
    border-color: #374151;
}
@media (min-width: 640px) {
    .info-row {
        flex-direction: row;
        align-items: center;
    }
}
.info-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    width: 80px;
    flex-shrink: 0;
}
.dark .info-label {
    color: #9ca3af;
}
.info-value {
    font-size: 0.85rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.dark .info-value {
    color: #f3f4f6;
}
.desc {
    color: #6b7280;
    font-size: 0.8rem;
    line-height: 1.5;
}
.gerant-badge {
    width: 1.5rem;
    height: 1.5rem;
    background: linear-gradient(
        135deg,
        var(--color-primary),
        var(--color-primary-dark)
    );
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.65rem;
    font-weight: 600;
    flex-shrink: 0;
}

/* ============================================ */
/* ⭐ LOGO - DYNAMIQUE */
/* ============================================ */
.logo-display {
    display: flex;
    justify-content: center;
    padding: 1rem;
}
.logo-img {
    width: 5rem;
    height: 5rem;
    border-radius: 1rem;
    object-fit: cover;
}
.logo-placeholder {
    width: 5rem;
    height: 5rem;
    background: linear-gradient(
        135deg,
        var(--color-primary),
        var(--color-primary-dark)
    );
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.logo-placeholder i {
    font-size: 2rem;
    color: white;
}

/* ============================================ */
/* ⭐ PERSONNEL - DYNAMIQUE */
/* ============================================ */
.manage-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    background: linear-gradient(
        135deg,
        var(--color-primary),
        var(--color-primary-dark)
    );
    color: white;
    border-radius: 0.5rem;
    font-size: 0.7rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}
.manage-link:hover {
    background: linear-gradient(
        135deg,
        var(--color-primary-dark),
        var(--color-primary-dark)
    );
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
.table-wrapper {
    overflow-x: auto;
}
.personnel-table {
    width: 100%;
    font-size: 0.75rem;
    border-collapse: collapse;
}
.personnel-table th {
    text-align: left;
    padding: 0.5rem 0.75rem;
    background: #f9fafb;
    font-weight: 600;
    color: #6b7280;
    font-size: 0.65rem;
    text-transform: uppercase;
}
.dark .personnel-table th {
    background: #1f2937;
    color: #9ca3af;
}
.personnel-table td {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #f3f4f6;
}
.dark .personnel-table td {
    border-color: #374151;
}
.member-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}
.member-avatar {
    width: 1.5rem;
    height: 1.5rem;
    background: linear-gradient(
        135deg,
        var(--color-primary),
        var(--color-primary-dark)
    );
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.65rem;
    flex-shrink: 0;
}
.status-dot {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.7rem;
}
.empty-cell {
    text-align: center;
    color: #9ca3af;
    padding: 2rem;
}
@media (max-width: 640px) {
    .hide-mobile {
        display: none;
    }
}

/* ============================================ */
/* ⭐ GALLERY - DYNAMIQUE */
/* ============================================ */
.gallery-card {
    margin-top: 1rem;
}
.max-reached {
    font-size: 0.65rem;
    color: #f59e0b;
}
.images-gallery {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}
@media (min-width: 480px) {
    .images-gallery {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (min-width: 768px) {
    .images-gallery {
        grid-template-columns: repeat(5, 1fr);
    }
}
.gallery-item {
    position: relative;
    aspect-ratio: 4/3;
    border-radius: 0.75rem;
    overflow: hidden;
    border: 2px solid #e5e7eb;
    background: #f9fafb;
    cursor: pointer;
    transition: all 0.2s;
}
.dark .gallery-item {
    border-color: #374151;
    background: #1f2937;
}
.gallery-item:hover {
    border-color: var(--color-primary);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}
.gallery-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.gallery-overlay {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.gallery-item:hover .gallery-overlay {
    opacity: 1;
}
.gallery-name {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0.25rem 0.5rem;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    font-size: 0.55rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.gallery-add {
    border: 2px dashed #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
}
.dark .gallery-add {
    border-color: #4b5563;
}
.gallery-add:hover {
    border-color: var(--color-primary);
    background: var(--color-primary-light);
}
.add-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    color: var(--color-primary);
}
.add-content i {
    font-size: 2rem;
}
.add-content span {
    font-size: 0.75rem;
    font-weight: 500;
}
.empty-gallery {
    text-align: center;
    padding: 2rem;
    color: #9ca3af;
}
.empty-gallery i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* ============================================ */
/* ⭐ EDIT FORM - DYNAMIQUE */
/* ============================================ */
.edit-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.edit-fields {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.field label {
    font-size: 0.8rem;
    font-weight: 500;
    color: #374151;
}
.dark .field label {
    color: #d1d5db;
}
.required {
    color: #ef4444;
}
.error {
    color: #ef4444;
    font-size: 0.7rem;
}
.logo-upload {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.file-input {
    font-size: 0.75rem;
}
.logo-preview {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--color-primary);
}
.edit-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* ============================================ */
/* ⭐ DARK MODE - AJUSTEMENTS */
/* ============================================ */
.dark .desc {
    color: #9ca3af;
}
.dark .info-label {
    color: #9ca3af;
}

/* ============================================ */
/* ⭐ ANIMATIONS */
/* ============================================ */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stats-grid {
    animation: slideIn 0.4s ease-out;
}

.info-logo-grid {
    animation: slideIn 0.5s ease-out;
}

.gallery-card {
    animation: slideIn 0.6s ease-out;
}

/* ⭐ Icône thème texte dynamique */
.theme-text-primary {
    color: var(--color-primary) !important;
}

/* ⭐ Bouton d'édition - DYNAMIQUE */
.edit-btn {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    color: white !important;
    transition: all 0.2s ease;
}
.edit-btn:hover {
    background: var(--color-primary-dark) !important;
    border-color: var(--color-primary-dark) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.edit-btn:focus {
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ Ajustement du header subtitle */
.header-subtitle i {
    font-size: 0.875rem;
}
/* ============================================ */
/* ⭐ BOUTONS DYNAMIQUES */
/* ============================================ */

/* Icône thème texte */
.theme-text-primary {
    color: var(--color-primary) !important;
}

/* ⭐ Bouton primaire (fond plein) */
.btn-primary {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    color: white !important;
    transition: all 0.2s ease;
}
.btn-primary:hover {
    background: var(--color-primary-dark) !important;
    border-color: var(--color-primary-dark) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.btn-primary:focus {
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ Bouton outlined (bordure seule) */
.btn-outlined-primary {
    color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    transition: all 0.2s ease;
}
.btn-outlined-primary:hover {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
    border-color: var(--color-primary-dark) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.btn-outlined-primary:focus {
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ Bouton delete (danger avec bordure dynamique au focus) */
.btn-delete:focus {
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.3) !important;
}

/* ⭐ Ajustement header subtitle */
.header-subtitle i {
    font-size: 0.875rem;
}
</style>
