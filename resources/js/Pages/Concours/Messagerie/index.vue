<script setup>
import { ref, nextTick, onMounted, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Avatar from "primevue/avatar";
import Toast from "primevue/toast";
import Message from "primevue/message";
import { useToast } from "primevue/usetoast";
import axios from "axios";

const toast = useToast();
const props = defineProps({
    conversations: Array,
    listeConcours: Array,
    isAdmin: Boolean,
    user_role: String,
    is_superadmin: Boolean,
    is_gerant: Boolean,
    is_admin: Boolean,
});

const user = usePage().props.auth.user;
const selectedContact = ref(null);
const chatScroll = ref(null);
const localConversations = ref([]);
const loadingMore = ref(false);
const hasMoreMessages = ref({});
const sending = ref(false);
const lastMessageHash = ref("");
const lastSendTime = ref(0);
const MIN_SEND_INTERVAL = 2000;

const form = ref({
    destinataire_id: null,
    concour_id: null,
    texte: "",
});

const scrollToBottom = async () => {
    await nextTick();
    if (chatScroll.value) {
        chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
    }
};

const refreshConversations = async () => {
    try {
        const response = await axios.get(route("messagerie.refresh"));
        if (response.data.conversations) {
            const newConversations = Array.isArray(response.data.conversations)
                ? response.data.conversations
                : Object.values(response.data.conversations);

            if (selectedContact.value) {
                const updatedContact = newConversations.find(
                    (c) =>
                        c.id == selectedContact.value.id ||
                        c.candidat_id == selectedContact.value.candidat_id,
                );
                if (updatedContact && updatedContact.messages) {
                    selectedContact.value.messages = updatedContact.messages;
                }
            }

            localConversations.value = newConversations;
        }
    } catch (error) {
        console.error("Erreur rafraîchissement:", error);
    }
};

watch(
    () => props.conversations,
    (newVal) => {
        if (newVal && Array.isArray(newVal)) {
            localConversations.value = [...newVal];
        }
        scrollToBottom();
    },
    { immediate: true, deep: true },
);

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const candidatId = urlParams.get("candidat_id");
    const concourId = urlParams.get("concour_id");

    if (candidatId && concourId) {
        const tempConversation = {
            id: candidatId,
            concour_id: Number(concourId),
            candidat_id: Number(candidatId),
            candidat_nom: "Nouveau candidat",
            messages: [],
            unread_count: 0,
        };

        if (!localConversations.value.find((c) => c.id == candidatId)) {
            localConversations.value.unshift(tempConversation);
        }

        selectedContact.value = tempConversation;
        form.value.destinataire_id = Number(candidatId);
        form.value.concour_id = Number(concourId);

        setTimeout(scrollToBottom, 100);
    }
});

const isFromStaff = (msg) => {
    return (
        msg.emetteur?.roles?.some((r) =>
            ["admin", "superadmin", "gerant"].includes(r.name),
        ) || false
    );
};

const selectConversation = (contact) => {
    if (!contact) return;

    selectedContact.value = contact;

    const candidatId = contact.candidat_id || contact.id;
    const concourId = contact.concour_id;

    form.value.concour_id = Number(concourId);
    form.value.destinataire_id = Number(candidatId);

    if (contact.unread_count > 0) {
        router.patch(
            route("messagerie.read", candidatId),
            {},
            { preserveScroll: true },
        );
        contact.unread_count = 0;
    }
    scrollToBottom();
};

const getMessageHash = () => {
    return `${form.value.concour_id}_${form.value.destinataire_id}_${form.value.texte}`;
};

const submit = async () => {
    if (!form.value.texte.trim() || sending.value) return;

    const now = Date.now();
    if (now - lastSendTime.value < MIN_SEND_INTERVAL) return;

    const currentHash = getMessageHash();
    if (currentHash === lastMessageHash.value) return;

    if (!form.value.concour_id) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Aucun concours associé à cette discussion",
            life: 3000,
        });
        return;
    }

    if (!form.value.destinataire_id) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Destinataire non identifié",
            life: 3000,
        });
        return;
    }

    sending.value = true;
    lastSendTime.value = now;
    lastMessageHash.value = currentHash;

    try {
        const response = await axios.post(route("messagerie.store"), {
            concour_id: form.value.concour_id,
            destinataire_id: form.value.destinataire_id,
            texte: form.value.texte,
        });

        if (response.data.success) {
            form.value.texte = "";
            scrollToBottom();
            await refreshConversations();

            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Message envoyé avec succès",
                life: 3000,
            });
        } else if (response.data.error) {
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail: response.data.error,
                life: 5000,
            });
        }
    } catch (error) {
        console.error("Erreur envoi:", error);
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail:
                error.response?.data?.error ||
                "Impossible d'envoyer le message",
            life: 5000,
        });
    } finally {
        setTimeout(() => {
            sending.value = false;
        }, 1000);
    }
};

const loadMoreMessages = async () => {
    if (!selectedContact.value || loadingMore.value) return;

    const currentMessageCount = selectedContact.value.messages?.length || 0;

    if (hasMoreMessages.value[selectedContact.value.id] === false) return;

    loadingMore.value = true;

    try {
        const response = await axios.post(route("messagerie.load-more"), {
            interlocutor_id:
                selectedContact.value.candidat_id || selectedContact.value.id,
            concour_id: selectedContact.value.concour_id,
            offset: currentMessageCount,
        });

        if (response.data.messages?.length > 0) {
            const existingMessages = selectedContact.value.messages || [];
            selectedContact.value.messages = [
                ...response.data.messages,
                ...existingMessages,
            ];
            hasMoreMessages.value[selectedContact.value.id] =
                response.data.has_more;

            const oldScrollHeight = chatScroll.value.scrollHeight;
            await nextTick();
            const newScrollHeight = chatScroll.value.scrollHeight;
            chatScroll.value.scrollTop = newScrollHeight - oldScrollHeight;
        } else {
            hasMoreMessages.value[selectedContact.value.id] = false;
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

const onChatScroll = (event) => {
    const element = event.target;
    if (element.scrollTop === 0 && !loadingMore.value) {
        loadMoreMessages();
    }
};

const getDisplayName = (contact) => {
    if (!contact) return "Conversation";
    if (!props.isAdmin) return "Support Administration";

    if (contact.candidat_nom) {
        return (
            `${contact.candidat_prenom || ""} ${contact.candidat_nom}`.trim() ||
            contact.candidat_nom
        );
    }

    const firstMsg = contact.messages?.[0];
    if (firstMsg) {
        if (firstMsg.emetteur_id == (contact.candidat_id || contact.id)) {
            return firstMsg.emetteur?.name || "Candidat";
        } else {
            return firstMsg.destinataire?.name || "Candidat";
        }
    }

    return "Candidat";
};

const handleKeyPress = (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        submit();
    }
};
</script>

<template>
    <AppLayout>
        <Toast />

        <!-- Message d'information selon le rôle -->
        <div v-if="is_gerant" class="info-message mx-4 mt-4">
            <Message severity="success" :closable="false">
                <div class="flex align-items-center gap-2">
                    <i class="pi pi-check-circle"></i>
                    <span
                        >Vous pouvez échanger avec les candidats des concours de
                        votre service.</span
                    >
                </div>
            </Message>
        </div>

        <div class="messagerie-wrapper">
            <!-- Sidebar des conversations -->
            <div
                class="sidebar-contacts"
                :class="{ 'hidden-mobile': selectedContact }"
            >
                <div class="header-sidebar">
                    <h5 class="m-0 font-bold text-primary text-center">
                        MESSAGES
                        <span class="conversation-count"
                            >({{ localConversations.length }})</span
                        >
                    </h5>
                </div>
                <div class="contacts-list">
                    <div
                        v-for="contact in localConversations"
                        :key="contact.id || contact.candidat_id"
                        @click="selectConversation(contact)"
                        :class="[
                            'contact-item',
                            selectedContact &&
                            (selectedContact.id == contact.id ||
                                selectedContact.candidat_id ==
                                    contact.candidat_id)
                                ? 'active-item'
                                : '',
                        ]"
                    >
                        <Avatar
                            icon="pi pi-user"
                            shape="circle"
                            class="bg-blue-100 text-blue-700"
                        />
                        <div class="contact-info">
                            <span class="contact-name">{{
                                getDisplayName(contact)
                            }}</span>
                            <span class="contact-concour">{{
                                contact.concour_intitule ||
                                "Concours #" + contact.concour_id
                            }}</span>
                            <p class="contact-preview">
                                {{
                                    contact.last_message?.texte ||
                                    contact.messages?.[
                                        contact.messages.length - 1
                                    ]?.texte ||
                                    "Nouvelle discussion..."
                                }}
                            </p>
                        </div>
                        <span
                            v-if="contact.unread_count > 0"
                            class="unread-badge"
                            >{{
                                contact.unread_count > 99
                                    ? "99+"
                                    : contact.unread_count
                            }}</span
                        >
                    </div>

                    <div
                        v-if="localConversations.length === 0"
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
                :class="{ 'hidden-mobile': !selectedContact }"
            >
                <template v-if="selectedContact">
                    <div class="chat-header">
                        <Button
                            icon="pi pi-arrow-left"
                            class="md:hidden p-button-text back-btn"
                            @click="selectedContact = null"
                        />
                        <Avatar
                            icon="pi pi-comments"
                            shape="circle"
                            class="bg-primary text-white mr-3"
                        />
                        <div class="flex flex-column">
                            <span class="font-bold">{{
                                getDisplayName(selectedContact)
                            }}</span>
                            <span class="chat-concour">{{
                                selectedContact.concour_intitule ||
                                "Concours #" + selectedContact.concour_id
                            }}</span>
                        </div>
                    </div>

                    <div
                        ref="chatScroll"
                        class="chat-messages-container"
                        @scroll="onChatScroll"
                    >
                        <div v-if="loadingMore" class="loading-messages">
                            <i class="pi pi-spin pi-spinner"></i> Chargement...
                        </div>

                        <div
                            v-for="msg in selectedContact.messages"
                            :key="msg.id"
                            :class="[
                                'message-row',
                                (
                                    isAdmin
                                        ? isFromStaff(msg)
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
                                                ? isFromStaff(msg)
                                                : msg.emetteur_id === user.id
                                        )
                                            ? 'bubble-me'
                                            : 'bubble-them',
                                    ]"
                                >
                                    <small
                                        v-if="isAdmin && isFromStaff(msg)"
                                        class="block font-bold mb-1 staff-badge"
                                    >
                                        {{ msg.emetteur?.name }} ({{
                                            msg.emetteur?.roles?.[0]?.name ||
                                            "Staff"
                                        }})
                                    </small>
                                    {{ msg.texte }}
                                    <small class="message-time">{{
                                        new Date(
                                            msg.created_at,
                                        ).toLocaleTimeString("fr-FR", {
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        })
                                    }}</small>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!selectedContact.messages?.length"
                            class="empty-messages"
                        >
                            <i class="pi pi-comment"></i>
                            <p>Aucun message, commencez la conversation</p>
                        </div>
                    </div>

                    <div class="chat-input-bar">
                        <div class="input-container">
                            <InputText
                                v-model="form.texte"
                                placeholder="Écrire un message..."
                                class="message-input"
                                @keyup.enter="handleKeyPress"
                                :disabled="sending"
                            />
                            <Button
                                icon="pi pi-send"
                                @click="submit"
                                :loading="sending"
                                :disabled="!form.texte.trim()"
                                class="send-btn"
                            />
                        </div>
                    </div>
                </template>
                <div v-else class="empty-state">
                    <i class="pi pi-comments"></i>
                    <p>Sélectionnez une discussion pour répondre</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Container principal */
.messagerie-wrapper {
    display: flex;
    height: calc(100vh - 120px);
    overflow: hidden;
    background: #fff;
    border-radius: 12px;
    margin: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Sidebar */
.sidebar-contacts {
    flex: 0 0 320px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e5e7eb;
    background: #fff;
}

.header-sidebar {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.header-sidebar h5 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #10b981;
}

.conversation-count {
    font-size: 0.7rem;
    font-weight: normal;
    color: #6b7280;
}

.contacts-list {
    flex: 1;
    overflow-y: auto;
}

.contact-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    gap: 0.75rem;
    transition: background 0.2s;
}

.contact-item:hover {
    background: #f9fafb;
}

.active-item {
    background: #ecfdf5 !important;
    border-left: 3px solid #10b981;
}

.contact-info {
    flex: 1;
    min-width: 0;
}

.contact-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1f2937;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-concour {
    font-size: 0.65rem;
    color: #6b7280;
    display: block;
    margin-top: 2px;
}

.contact-preview {
    font-size: 0.7rem;
    color: #9ca3af;
    margin: 4px 0 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.unread-badge {
    background: #ef4444;
    color: white;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    font-weight: 600;
    padding: 0 4px;
}

/* Chat window */
.chat-window {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
}

.chat-header {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    background: #fff;
}

.back-btn {
    margin-right: 0.5rem;
}

.chat-concour {
    font-size: 0.65rem;
    color: #6b7280;
    margin-top: 2px;
}

.chat-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    background: #f9fafb;
}

.loading-messages {
    text-align: center;
    padding: 0.5rem;
    font-size: 0.7rem;
    color: #6b7280;
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
    padding: 0.6rem 0.8rem;
    border-radius: 1rem;
    font-size: 0.85rem;
    line-height: 1.4;
    word-wrap: break-word;
    position: relative;
}

.bubble-me {
    background: #10b981;
    color: #fff;
    border-bottom-right-radius: 0.25rem;
}

.bubble-them {
    background: #fff;
    color: #1f2937;
    border-bottom-left-radius: 0.25rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
}

.staff-badge {
    font-size: 0.6rem;
    opacity: 0.8;
    margin-bottom: 4px;
}

.message-time {
    display: block;
    font-size: 0.55rem;
    opacity: 0.7;
    margin-top: 4px;
    text-align: right;
}

.empty-messages {
    text-align: center;
    padding: 2rem;
    color: #9ca3af;
}

.empty-messages i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.chat-input-bar {
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
    background: #fff;
}

.input-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f9fafb;
    border-radius: 2rem;
    border: 1px solid #e5e7eb;
}

.message-input {
    flex: 1;
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: 0.85rem;
}

.message-input:focus {
    outline: none;
}

.send-btn {
    background: transparent !important;
    color: #10b981 !important;
}

.send-btn:hover {
    background: #ecfdf5 !important;
}

.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f9fafb;
    color: #9ca3af;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-contacts {
    text-align: center;
    padding: 2rem;
    color: #9ca3af;
}

.empty-contacts i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* Message d'information */
.info-message {
    margin-bottom: 0;
}

/* Responsive */
@media screen and (max-width: 768px) {
    .messagerie-wrapper {
        margin: 0.5rem;
        height: calc(100vh - 100px);
    }

    .sidebar-contacts {
        flex: 1;
    }

    .hidden-mobile {
        display: none !important;
    }

    .chat-window {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 100;
        background: #fff;
        border-radius: 0;
        margin: 0;
    }

    .chat-header {
        padding: 0.75rem;
    }

    .message-bubble-wrapper {
        max-width: 85%;
    }

    .bubble {
        font-size: 0.8rem;
        padding: 0.5rem 0.7rem;
    }

    .contact-item {
        padding: 0.6rem;
    }

    .contact-name {
        font-size: 0.8rem;
    }

    .header-sidebar {
        padding: 0.75rem;
    }
}

@media screen and (min-width: 769px) {
    .chat-window.hidden-mobile {
        display: flex !important;
    }
}

/* Dark mode */
.dark .messagerie-wrapper,
.dark .sidebar-contacts,
.dark .chat-window,
.dark .chat-header,
.dark .chat-input-bar {
    background: #1f2937;
}

.dark .sidebar-contacts {
    border-right-color: #374151;
}

.dark .header-sidebar {
    background: #111827;
    border-bottom-color: #374151;
}

.dark .contact-item {
    border-bottom-color: #374151;
}

.dark .contact-item:hover {
    background: #374151;
}

.dark .active-item {
    background: #064e3b !important;
}

.dark .contact-name {
    color: #f3f4f6;
}

.dark .contact-concour,
.dark .contact-preview {
    color: #9ca3af;
}

.dark .bubble-them {
    background: #374151;
    color: #f3f4f6;
    border-color: #4b5563;
}

.dark .chat-messages-container {
    background: #111827;
}

.dark .input-container {
    background: #374151;
    border-color: #4b5563;
}

.dark .message-input {
    color: #f3f4f6;
}

.dark .empty-state,
.dark .empty-contacts,
.dark .empty-messages {
    color: #6b7280;
}
</style>
