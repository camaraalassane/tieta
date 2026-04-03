<script setup>
import { ref, computed, nextTick, onMounted, watch } from "vue";
import { useForm, usePage, Head, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Avatar from "primevue/avatar";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import axios from "axios";

const toast = useToast();
const props = defineProps({
    conversations: Object,
    listeConcours: Array,
    isAdmin: Boolean,
});

const user = usePage().props.auth.user;
const selectedContactId = ref(null);
const chatScroll = ref(null);
const localConversations = ref({ ...props.conversations });
const loadingMore = ref(false);
const hasMoreMessages = ref({});

// ⭐ Pagination des conversations
const currentPage = ref(1);
const loadingConversations = ref(false);
const hasMoreConversations = ref(true);

watch(
    () => props.conversations,
    (newVal) => {
        // Fusionner les nouvelles conversations avec les existantes
        localConversations.value = { ...localConversations.value, ...newVal };
        scrollToBottom();
    },
    { deep: true },
);

const form = useForm({
    destinataire_id: null,
    concour_id: null,
    texte: "",
});

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const candidatId = urlParams.get("candidat_id");
    const concourId = urlParams.get("concour_id");

    if (candidatId && concourId) {
        selectedContactId.value = candidatId;
        form.destinataire_id = Number(candidatId);
        form.concour_id = Number(concourId);

        if (!localConversations.value[candidatId]) {
            localConversations.value[candidatId] = {
                messages: [],
                unread_count: 0,
            };
        }

        setTimeout(scrollToBottom, 100);
    }
});

const isFromAdmin = (msg) => {
    return (
        msg.emetteur.roles?.some((r) =>
            ["admin", "superadmin"].includes(r.name),
        ) || false
    );
};

const selectConversation = (id) => {
    selectedContactId.value = id;
    const data = localConversations.value[id];

    if (data && data.messages.length > 0) {
        const firstMsg = data.messages[0];
        form.concour_id = Number(firstMsg.concour_id);
        form.destinataire_id = props.isAdmin
            ? Number(id)
            : firstMsg.emetteur_id;

        if (data.unread_count > 0) {
            router.patch(
                route("messagerie.read", id),
                {},
                { preserveScroll: true },
            );
            // Mettre à jour localement le compteur
            data.unread_count = 0;
        }
    }
    scrollToBottom();
};

const submit = () => {
    if (!form.texte.trim() || form.processing) return;
    if (!form.concour_id) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Aucun concours associé à cette discussion",
            life: 3000,
        });
        return;
    }

    form.post(route("messagerie.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.texte = "";
            scrollToBottom();
            // Rafraîchir les conversations après envoi
            refreshConversations();
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail: errors.message || "Impossible d'envoyer le message",
                life: 3000,
            });
        },
    });
};

const scrollToBottom = async () => {
    await nextTick();
    if (chatScroll.value)
        chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
};

// ⭐ Charger plus de messages pour une conversation
const loadMoreMessages = async () => {
    if (!selectedContactId.value || loadingMore.value) return;

    const conversation = localConversations.value[selectedContactId.value];
    const currentMessageCount = conversation?.messages.length || 0;

    // Vérifier s'il y a plus de messages à charger
    if (hasMoreMessages.value[selectedContactId.value] === false) return;

    loadingMore.value = true;

    try {
        const response = await axios.post(route("messagerie.load-more"), {
            interlocutor_id: selectedContactId.value,
            concour_id: form.concour_id,
            offset: currentMessageCount,
        });

        if (response.data.messages.length > 0) {
            // Ajouter les anciens messages au début (pour garder l'ordre chronologique)
            const existingMessages = conversation.messages;
            conversation.messages = [
                ...response.data.messages,
                ...existingMessages,
            ];
            hasMoreMessages.value[selectedContactId.value] =
                response.data.has_more;

            // Maintenir la position de scroll
            const oldScrollHeight = chatScroll.value.scrollHeight;
            await nextTick();
            const newScrollHeight = chatScroll.value.scrollHeight;
            chatScroll.value.scrollTop = newScrollHeight - oldScrollHeight;
        } else {
            hasMoreMessages.value[selectedContactId.value] = false;
        }
    } catch (error) {
        console.error("Erreur chargement messages:", error);
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Impossible de charger plus de messages",
            life: 3000,
        });
    } finally {
        loadingMore.value = false;
    }
};

// ⭐ Rafraîchir les conversations
const refreshConversations = async () => {
    try {
        const response = await axios.get(route("messagerie.refresh"));
        localConversations.value = response.data.conversations;
    } catch (error) {
        console.error("Erreur rafraîchissement:", error);
    }
};

// ⭐ Détecter le scroll en haut pour charger plus de messages
const onChatScroll = (event) => {
    const element = event.target;
    if (element.scrollTop === 0 && !loadingMore.value) {
        loadMoreMessages();
    }
};

// ⭐ Obtenir le nom d'affichage
const getDisplayName = (data, id) => {
    if (!props.isAdmin) return "Support Administration";

    if (data.interlocutor_name) return data.interlocutor_name;

    const firstMsg = data.messages[0];
    if (!firstMsg) return "Nouveau Candidat";

    if (firstMsg.emetteur_id == id) {
        return (
            firstMsg.emetteur?.name || firstMsg.emetteur?.email || "Candidat"
        );
    } else {
        return (
            firstMsg.destinataire?.name ||
            firstMsg.destinataire?.email ||
            "Candidat"
        );
    }
};
</script>

<template>
    <AppLayout>
        <Toast />
        <div class="messagerie-wrapper card shadow-4">
            <!-- Sidebar des conversations -->
            <div
                class="sidebar-contacts"
                :class="{ 'hidden-mobile': selectedContactId }"
            >
                <div class="header-sidebar">
                    <h5 class="m-0 font-bold text-primary text-center">
                        MESSAGES
                        <span class="conversation-count">
                            ({{ Object.keys(localConversations).length }})
                        </span>
                    </h5>
                </div>
                <div class="contacts-list">
                    <div
                        v-for="(data, id) in localConversations"
                        :key="id"
                        @click="selectConversation(id)"
                        :class="[
                            'contact-item',
                            selectedContactId == id ? 'active-item' : '',
                        ]"
                    >
                        <Avatar
                            icon="pi pi-user"
                            shape="circle"
                            class="bg-blue-100 text-blue-700"
                        />
                        <div class="contact-info">
                            <span class="contact-name">
                                {{ getDisplayName(data, id) }}
                            </span>
                            <p class="contact-preview">
                                {{
                                    data.messages[data.messages.length - 1]
                                        ?.texte || "Nouvelle discussion..."
                                }}
                            </p>
                        </div>
                        <span
                            v-if="data.unread_count > 0"
                            class="unread-badge"
                            >{{
                                data.unread_count > 99
                                    ? "99+"
                                    : data.unread_count
                            }}</span
                        >
                    </div>

                    <!-- Message si aucune conversation -->
                    <div
                        v-if="Object.keys(localConversations).length === 0"
                        class="empty-contacts"
                    >
                        <i class="pi pi-inbox"></i>
                        <p>Aucune conversation</p>
                    </div>
                </div>
            </div>

            <!-- Fenêtre de chat -->
            <div
                class="chat-window"
                :class="{ 'hidden-mobile': !selectedContactId }"
            >
                <template v-if="selectedContactId">
                    <div
                        class="chat-header border-bottom-1 surface-border flex align-items-center"
                    >
                        <Button
                            icon="pi pi-arrow-left"
                            class="md:hidden p-button-text"
                            @click="selectedContactId = null"
                        />
                        <Avatar
                            icon="pi pi-comments"
                            shape="circle"
                            class="bg-primary text-white mr-3"
                        />
                        <div class="flex flex-column">
                            <span class="font-bold">
                                {{
                                    getDisplayName(
                                        localConversations[selectedContactId],
                                        selectedContactId,
                                    )
                                }}
                            </span>
                            <span class="chat-concour text-xs text-gray-500">
                                Concours #{{ form.concour_id }}
                            </span>
                        </div>
                    </div>

                    <div
                        ref="chatScroll"
                        class="chat-messages-container"
                        @scroll="onChatScroll"
                    >
                        <!-- Indicateur de chargement -->
                        <div v-if="loadingMore" class="loading-messages">
                            <i class="pi pi-spin pi-spinner"></i>
                            Chargement des messages...
                        </div>

                        <div
                            v-for="msg in localConversations[selectedContactId]
                                .messages"
                            :key="msg.id"
                            :class="[
                                'message-row',
                                (
                                    isAdmin
                                        ? isFromAdmin(msg)
                                        : msg.emetteur_id === user.id
                                )
                                    ? 'row-me'
                                    : 'row-them',
                            ]"
                        >
                            <div class="message-bubble-wrapper">
                                <div
                                    :class="[
                                        'bubble',
                                        (
                                            isAdmin
                                                ? isFromAdmin(msg)
                                                : msg.emetteur_id === user.id
                                        )
                                            ? 'bubble-me'
                                            : 'bubble-them',
                                    ]"
                                >
                                    <small
                                        v-if="isAdmin && isFromAdmin(msg)"
                                        class="block font-bold mb-1"
                                        style="font-size: 0.7rem; opacity: 0.8"
                                    >
                                        {{ msg.emetteur.name }} (Admin)
                                    </small>
                                    {{ msg.texte }}
                                    <small class="message-time">
                                        {{
                                            new Date(
                                                msg.created_at,
                                            ).toLocaleTimeString("fr-FR", {
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            })
                                        }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="chat-input-bar">
                        <div class="input-container surface-100">
                            <InputText
                                v-model="form.texte"
                                placeholder="Répondre..."
                                class="message-input"
                                @keyup.enter="submit"
                            />
                            <Button
                                icon="pi pi-send"
                                @click="submit"
                                :loading="form.processing"
                                class="p-button-text p-button-rounded"
                            />
                        </div>
                    </div>
                </template>
                <div v-else class="empty-state">
                    <i
                        class="pi pi-comments"
                        style="
                            font-size: 3rem;
                            opacity: 0.5;
                            margin-bottom: 1rem;
                        "
                    ></i>
                    <p>Sélectionnez une discussion pour répondre</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Ajouter ces styles complémentaires */
.conversation-count {
    font-size: 0.7rem;
    font-weight: normal;
    margin-left: 0.5rem;
    color: var(--text-color-secondary);
}

.empty-contacts {
    text-align: center;
    padding: 2rem;
    color: var(--text-color-secondary);
}

.empty-contacts i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.loading-messages {
    text-align: center;
    padding: 0.5rem;
    font-size: 0.7rem;
    color: var(--text-color-secondary);
}

.loading-messages i {
    margin-right: 0.25rem;
}

.chat-concour {
    font-size: 0.7rem;
    color: var(--text-color-secondary);
}

.message-time {
    display: block;
    font-size: 0.6rem;
    opacity: 0.6;
    margin-top: 0.25rem;
    text-align: right;
}

/* Animation pour les nouveaux messages */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-row {
    animation: fadeIn 0.2s ease;
}

.messagerie-wrapper {
    display: flex;
    height: calc(100vh - 170px);
    padding: 0 !important;
    overflow: hidden;
}

/* Le reste de vos styles existants... */
.sidebar-contacts {
    flex: 0 0 320px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--surface-border);
}

.header-sidebar {
    padding: 1.2rem;
    border-bottom: 1px solid var(--surface-border);
    background: var(--surface-50);
}

.contacts-list {
    flex: 1;
    overflow-y: auto;
    background: #fff;
}

.contact-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    cursor: pointer;
    border-bottom: 1px solid var(--surface-ground);
    gap: 12px;
    position: relative;
}

.active-item {
    background: var(--blue-50) !important;
    border-left: 4px solid var(--primary-color);
}

.contact-name {
    font-weight: bold;
    color: var(--surface-900);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
    font-size: 0.9rem;
}

.contact-preview {
    margin: 0;
    font-size: 0.8rem;
    color: var(--surface-600);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-window {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
}

.chat-header {
    padding: 1rem;
}

.chat-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
}

.message-row {
    display: flex;
    margin-bottom: 1rem;
    width: 100%;
}

.row-me {
    justify-content: flex-end;
}

.row-them {
    justify-content: flex-start;
}

.message-bubble-wrapper {
    max-width: 75%;
    min-width: 80px;
}

.bubble {
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    line-height: 1.4;
    word-wrap: break-word;
    word-break: break-word;
    white-space: pre-wrap;
    overflow-wrap: break-word;
    width: 100%;
    display: block;
}

.bubble-me {
    background: var(--primary-color);
    color: #fff;
    border-bottom-right-radius: 0.25rem;
}

.bubble-them {
    background: #ffffff;
    color: var(--surface-900);
    border-bottom-left-radius: 0.25rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.chat-input-bar {
    padding: 1rem;
    border-top: 1px solid var(--surface-border);
}

.input-container {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 15px;
    border-radius: 2.5rem;
}

.message-input {
    flex: 1;
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
}

.unread-badge {
    background-color: #ef4444;
    color: white;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    position: absolute;
    right: 10px;
    top: 15px;
}

.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #fafafa;
    color: #999;
}

@media screen and (max-width: 768px) {
    .hidden-mobile {
        display: none !important;
    }

    .sidebar-contacts {
        flex: 1;
    }

    .message-bubble-wrapper {
        max-width: 85%;
    }
}
</style>
